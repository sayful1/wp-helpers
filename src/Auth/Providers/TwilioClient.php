<?php

namespace Stackonet\WP\Framework\Auth\Providers;

use Exception;
use Stackonet\WP\Framework\Auth\Config;
use Stackonet\WP\Framework\Auth\Interfaces\OtpSmsProviderInterface;
use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;

/**
 * Class TwilioClient
 *
 * @package Stackonet\WP\Framework\Auth\Providers
 */
class TwilioClient extends RestClient implements OtpSmsProviderInterface {

	/**
	 * Get settings
	 *
	 * @return array {
	 * Array of settings.
	 *
	 * @type string $from_number The phone number to send from.
	 * @type string $account_sid The Twilio account SID.
	 * @type string $auth_token The Twilio auth token.
	 * }
	 * @throws Exception Throw exception if constant not found.
	 */
	public static function get_settings(): array {
		return Config::get_twilio_settings();
	}

	/**
	 * Get setting
	 *
	 * @param  string  $key  The setting key.
	 * @param  mixed  $default  The default value.
	 *
	 * @return false|mixed
	 * @throws Exception Throw exception if constant not found.
	 */
	public static function get_setting( string $key, $default = false ) {
		$settings = static::get_settings();

		return $settings[ $key ] ?? $default;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$sid   = self::get_setting( 'account_sid' );
		$token = self::get_setting( 'auth_token' );
		$this->add_auth_header( base64_encode( sprintf( '%s:%s', $sid, $token ) ) ); // phpcs:ignore
		parent::__construct( "https://api.twilio.com/2010-04-01/Accounts/$sid" );
	}

	/**
	 * Send OTP to phone number
	 *
	 * @param  string  $phone_e164  Phone number in E164 format.
	 * @param  string  $message  The message to be sent.
	 *
	 * @return array|WP_Error
	 */
	public function send( string $phone_e164, string $message ) {
		try {
			return $this->post(
				'/Messages.json',
				[
					'Body' => $message,
					'To'   => $phone_e164,
					'From' => self::get_setting( 'from_number' ),
				]
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'twilio_error', $e->getMessage() );
		}
	}
}
