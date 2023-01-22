<?php

namespace Stackonet\WP\Framework\Auth;

use Exception;

/**
 * Class Config
 *
 * @package Stackonet\WP\Framework\Auth
 */
class Config {

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	private static function defaults(): array {
		return [
			'sms_provider'   => 'system-log',
			'rest_namespace' => 'stackonet-auth/v1',
			'aws'            => [
				'key'     => '',
				'secret'  => '',
				'region'  => '',
				'version' => '',
			],
			'twilio'         => [
				'account_sid' => '',
				'auth_token'  => '',
				'from_number' => '',
			],
		];
	}

	/**
	 * Get settings
	 *
	 * @return array The settings.
	 * @throws Exception Throw exception if constant not found.
	 */
	public static function get_settings(): array {
		if ( defined( 'STACKONET_AUTH_SETTINGS' ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			$settings = unserialize( STACKONET_AUTH_SETTINGS );

			return array_merge( static::defaults(), $settings );
		}

		throw new Exception( 'Settings are not available. Define STACKONET_AUTH_SETTINGS constant in wp-config.php file.' );
	}

	/**
	 * Get SMS provider
	 *
	 * @return string The SMS provider.
	 */
	public static function get_sms_provider(): string {
		try {
			$settings = static::get_settings();

			return $settings['sms_provider'];
		} catch ( Exception $e ) {
			return 'system-log';
		}
	}

	/**
	 * Get SMS provider
	 *
	 * @return string The SMS provider.
	 */
	public static function get_rest_namespace(): string {
		try {
			$settings = static::get_settings();

			return $settings['rest_namespace'];
		} catch ( Exception $e ) {
			return 'stackonet-auth/v1';
		}
	}

	/**
	 * Get AWS settings
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
	public static function get_aws_settings(): array {
		$settings = static::get_settings();

		return $settings['aws'];
	}

	/**
	 * Check if AWS settings are available
	 *
	 * @return bool
	 */
	public static function has_aws_settings(): bool {
		try {
			$settings = self::get_aws_settings();

			return ! empty( $settings['key'] ) && ! empty( $settings['secret'] && ! empty( $settings['region'] ) && ! empty( $settings['version'] ) );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get Twilio settings
	 *
	 * @return array {
	 * Array of settings.
	 *
	 * @type string $account_sid The Twilio account sid.
	 * @type string $auth_token The Twilio auth token.
	 * @type string $from_number The Twilio from number.
	 * }
	 * @throws Exception Throw exception if constant not found.
	 */
	public static function get_twilio_settings(): array {
		$settings = static::get_settings();

		return $settings['twilio'];
	}

	/**
	 * Check if Twilio settings are available
	 *
	 * @return bool
	 */
	public static function has_twilio_settings(): bool {
		try {
			$settings = static::get_twilio_settings();

			return ! empty( $settings['account_sid'] ) && ! empty( $settings['auth_token'] ) && ! empty( $settings['from_number'] );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
