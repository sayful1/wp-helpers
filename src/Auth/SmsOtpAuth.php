<?php

namespace Stackonet\WP\Framework\Auth;

use Stackonet\WP\Framework\Auth\Interfaces\OtpSmsProviderInterface;
use Stackonet\WP\Framework\Auth\Providers\AwsSnsClient;
use Stackonet\WP\Framework\Auth\Providers\SystemLog;
use Stackonet\WP\Framework\Auth\Providers\TwilioClient;

/**
 * Class SmsOtpAuth
 *
 * @package Stackonet\WP\Framework\Auth
 */
class SmsOtpAuth {
	/**
	 * Get user id
	 *
	 * @param  string|mixed  $number  The phone number in E164 format.
	 *
	 * @return  int
	 */
	public static function get_user_id( $number ): int {
		global $wpdb;
		$abs_number = intval( $number );
		$sql        = "SELECT {$wpdb->users}.ID AS user_id FROM {$wpdb->users}";
		$sql        .= " INNER JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id";
		$sql        .= ' WHERE 1 = 1 AND';
		$sql        .= $wpdb->prepare( " ({$wpdb->users}.user_login = %s)", $abs_number );
		$sql        .= $wpdb->prepare( " OR ({$wpdb->users}.user_login = %s)", $number );
		$sql        .= $wpdb->prepare( " OR ({$wpdb->users}.user_email = %s)", $abs_number );
		$sql        .= $wpdb->prepare( " OR ({$wpdb->users}.user_email = %s)", $number );
		$sql        .= $wpdb->prepare(
			" OR ({$wpdb->usermeta}.meta_key = %s AND {$wpdb->usermeta}.meta_value = %s)",
			static::get_meta_key(),
			$number
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return isset( $result['user_id'] ) ? intval( $result['user_id'] ) : 0;
	}

	/**
	 * Send OTP
	 *
	 * @param  string|mixed  $phone  The phone number in E164 format.
	 * @param  int|string  $otp  The OTP to be sent.
	 *
	 * @return bool|\WP_Error
	 */
	public static function send_opt( $phone, $otp ) {
		$providers        = static::get_otp_service_providers();
		$service_provider = Config::get_sms_provider();
		$provider         = new $providers[ $service_provider ]();
		if ( ! $provider instanceof OtpSmsProviderInterface ) {
			return new \WP_Error( 'invalid_otp_provider', 'Invalid OTP provider' );
		}

		$text = '{{otp_number}} is your verification code for {{blogname}}. ';
		$text .= 'Please do not share this with anyone. ';
		$text .= '{{blogname}} will never call to confirm your verification code.';

		$message = str_replace( '{{otp_number}}', $otp, $text );
		$message = str_replace( '{{blogname}}', get_option( 'blogname' ), $message );

		$result = $provider->send( $phone, $otp );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Validate OTP
	 *
	 * @param  string  $phone_number  The phone number.
	 * @param  int  $otp  The otp.
	 *
	 * @return bool
	 */
	public static function validate_otp( string $phone_number, int $otp ): bool {
		$data = get_transient( $phone_number );
		if ( false === $data ) {
			return false;
		}

		return ( isset( $data['otp'] ) && intval( $data['otp'] ) === $otp );
	}

	/**
	 * Generate OTP data
	 *
	 * @param  string  $number  The phone number.
	 * @param  array  $params  Additional parameters.
	 *
	 * @return array|mixed
	 */
	public static function generate_otp_data( string $number, array $params = [] ) {
		$data = get_transient( $number );
		if ( false === $data ) {
			$data = [
				'phone'  => $number,
				'otp'    => wp_rand( 100000, 999999 ),
				'params' => $params,
			];
		}

		$data['time'] = time();
		set_transient( $number, $data, ( MINUTE_IN_SECONDS * 5 ) );

		return $data;
	}

	/**
	 * Get meta key name for a provider
	 *
	 * @return string
	 */
	public static function get_meta_key(): string {
		return '__otp_phone_number';
	}

	/**
	 * Get phone number
	 *
	 * @param  int  $user_id  The user id.
	 *
	 * @return string
	 */
	public static function get_phone_number( int $user_id ): string {
		$phone_number = get_user_meta( $user_id, static::get_meta_key(), true );

		return ! empty( $phone_number ) ? $phone_number : '';
	}

	/**
	 * Update phone number
	 *
	 * @param  int  $user_id  The user id.
	 * @param  string  $phone_number  The phone number.
	 *
	 * @return bool
	 */
	public static function update_phone_number( int $user_id, string $phone_number ): bool {
		update_user_meta( $user_id, '_billing_phone', $phone_number );

		return (bool) update_user_meta( $user_id, static::get_meta_key(), $phone_number );
	}

	/**
	 * Delete phone number
	 *
	 * @param  int  $user_id  The user id.
	 *
	 * @return bool
	 */
	public static function delete_phone_number( int $user_id ): bool {
		return delete_user_meta( $user_id, static::get_meta_key() );
	}

	/**
	 * Get user ids registered via SMS OTP
	 *
	 * @return array
	 */
	public static function get_users_has_otp_auth(): array {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s ORDER BY user_id ASC",
				self::get_meta_key()
			),
			ARRAY_A
		);

		$ids = [];
		if ( $results ) {
			$ids = array_map( 'intval', wp_list_pluck( $results, 'user_id' ) );
		}

		return $ids;
	}

	/**
	 * Get OTP service providers
	 *
	 * @return array
	 */
	protected static function get_otp_service_providers(): array {
		$providers = [
			'system-log' => SystemLog::class,
		];
		if ( Config::has_aws_settings() ) {
			$providers['aws-sns'] = AwsSnsClient::class;
		}
		if ( Config::has_twilio_settings() ) {
			$providers['twilio'] = TwilioClient::class;
		}

		return $providers;
	}
}
