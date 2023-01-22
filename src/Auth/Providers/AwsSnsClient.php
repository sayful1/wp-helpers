<?php

namespace Stackonet\WP\Framework\Auth\Providers;

use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Sns\Exception\SnsException;
use Aws\Sns\SnsClient;
use Exception;
use Stackonet\WP\Framework\Auth\Config;
use Stackonet\WP\Framework\Auth\Interfaces\OtpSmsProviderInterface;
use Stackonet\WP\Framework\Supports\Logger;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * AwsSnsClient class
 * Amazon Web Services - Simple Notification Service client
 */
class AwsSnsClient implements OtpSmsProviderInterface {
	/**
	 * The instance of the class
	 *
	 * @var SnsClient
	 */
	private static $sns_client = null;

	/**
	 * Get settings
	 *
	 * @return array {
	 * Array of settings.
	 *
	 * @type string $key The AWS access key.
	 * @type string $secret The AWS secret key.
	 * @type string $region The AWS region.
	 * @type string $version The AWS api version.
	 * }
	 * @throws Exception Throw exception if constant not found.
	 */
	public static function get_settings(): array {
		return Config::get_aws_settings();
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
	 * Get credentials
	 *
	 * @throws Exception|SnsException Throw exception if setting not found or invalid configuration.
	 */
	public static function get_credentials(): Credentials {
		return new Credentials(
			static::get_setting( 'key' ),
			static::get_setting( 'secret' )
		);
	}

	/**
	 * Get SnsClient client
	 *
	 * @return SnsClient
	 * @throws Exception Throw exception if constant not found.
	 */
	public static function get(): SnsClient {
		if ( ! static::$sns_client instanceof SnsClient ) {
			static::$sns_client = new SnsClient(
				[
					'version'     => static::get_setting( 'version' ),
					'region'      => static::get_setting( 'region' ),
					'credentials' => static::get_credentials(),
				]
			);
		}

		return static::$sns_client;
	}

	/**
	 * Send OTP to phone number
	 *
	 * @param  string  $phone_e164  Phone number in E164 format.
	 * @param  string  $message  The message to be sent.
	 *
	 * @return Result|WP_Error
	 */
	public function send( string $phone_e164, string $message ) {
		try {
			return self::get()->publish(
				[
					'SMSType'     => 'Transactional',
					'Message'     => $message,
					'PhoneNumber' => $phone_e164,
				]
			);
		} catch ( SnsException|AwsException|Exception $e ) {
			// output error message if fails.
			Logger::log( $e->getAwsErrorMessage() );

			return new WP_Error( $e->getAwsErrorCode(), $e->getAwsErrorMessage() );
		}
	}
}
