<?php

namespace Stackonet\WP\Framework\Auth;

use DateTime;
use DateTimeZone;
use Stackonet\WP\Framework\Auth\Admin\Admin;
use Stackonet\WP\Framework\Auth\Models\Token;
use WP_Error;

/**
 * Class TokenAuth
 */
class TokenAuth {

	/**
	 * CORS enabled by default
	 *
	 * @var bool
	 */
	protected static $enable_cors = true;

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * WordPress Error object
	 *
	 * @var WP_Error
	 */
	private static $jwt_error;

	/**
	 * Init frontend functionality
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_filter( 'rest_allowed_cors_headers', [ self::$instance, 'add_cors_support' ], 20 );
			add_filter( 'determine_current_user', [ self::$instance, 'determine_current_user' ], 20 );
			add_filter( 'rest_pre_dispatch', [ self::$instance, 'rest_pre_dispatch' ] );
			add_action( 'admin_init', [ self::$instance, 'create_table' ] );
			add_action( 'show_user_profile', [ Admin::class, 'token_ui' ] );
			add_action( 'edit_user_profile', [ Admin::class, 'token_ui' ] );
			add_action( 'personal_options_update', [ Admin::class, 'save_option' ] );
			add_action( 'edit_user_profile_update', [ Admin::class, 'save_option' ] );
			add_action( 'wp_ajax_revoke_auth_token', [ Admin::class, 'revoke_auth_token' ] );
		}

		return self::$instance;
	}

	/**
	 * Create table
	 *
	 * @return void
	 */
	public function create_table() {
		$version = get_option( 'users_auth_tokens_db_version' );
		if ( empty( $version ) ) {
			Token::create_table();
		}
	}

	/**
	 * Add CORS support to the request.
	 *
	 * @param  array  $allow_headers
	 *
	 * @return array
	 */
	public static function add_cors_support( array $allow_headers ): array {
		if ( static::$enable_cors ) {
			$allow_headers = array_unique(
				array_merge(
					$allow_headers,
					[ 'Authorization', 'X-WP-Nonce', 'X-Auth-Token', 'Content-Range' ]
				)
			);
		}

		return $allow_headers;
	}

	/**
	 * This is our Middleware to try to authenticate the user according to the
	 * token send.
	 *
	 * @param  int|bool  $user_id  Logged User ID.
	 *
	 * @return int|bool
	 */
	public static function determine_current_user( $user_id ) {

		// Don't authenticate twice.
		if ( ! empty( $user ) ) {
			return $user;
		}

		/**
		 * This hook only should run on the REST API requests to determine
		 * if the user in the Token (if any) is valid, for any other
		 * normal call ex. wp-admin/.* return the user.
		 */
		$rest_api_slug = rest_get_url_prefix();
		$valid_api_uri = strpos( $_SERVER['REQUEST_URI'], $rest_api_slug );
		if ( ! $valid_api_uri ) {
			return $user_id;
		}

		/*
		 * if the request URI is for validate the token don't do anything,
		 * this avoids double calls to the validate_token function.
		 */
		$validate_uri = strpos( $_SERVER['REQUEST_URI'], 'token/validate' );
		if ( $validate_uri > 0 ) {
			return $user_id;
		}

		// If REST nonce authentication available then use it.
		if ( ! empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			return $user_id;
		}

		$token = static::get_auth_token_from_user_request();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$token = self::validate_token( $token );

		if ( is_wp_error( $token ) ) {
			static::$jwt_error = $token;

			return $user_id;
		}

		/** Everything is ok, return the user ID stored in the token*/
		return intval( $token['user_id'] );
	}

	/**
	 * Filter to hook the rest_pre_dispatch, if this is an error in the request
	 * send it, if there is no error just continue with the current request.
	 *
	 * @param  mixed  $result  The result.
	 *
	 * @return mixed
	 */
	public static function rest_pre_dispatch( $result ) {
		if ( is_wp_error( static::$jwt_error ) ) {
			return apply_filters( 'token_auth_error_response', static::$jwt_error );
		}

		return $result;
	}

	/**
	 * Generate token
	 *
	 * @param  string  $username  The user name.
	 * @param  string  $password  The password.
	 *
	 * @return array|WP_Error
	 */
	public static function generate_token( string $username, string $password ) {
		/** Try to authenticate the user with the passed credentials*/
		$user = wp_authenticate( $username, $password );

		/** If the authentication fails return a error*/
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$token = Token::get_token_for_user( $user->ID );

		return [
			'token' => $token,
			'user'  => $user,
		];
	}


	/**
	 * Main validation function, this function try to get the Authentication
	 * headers and decoded.
	 *
	 * @param  string|mixed  $token  The auth token.
	 *
	 * @return array|WP_Error
	 */
	public static function validate_token( $token ) {
		if ( empty( $token ) ) {
			return new WP_Error(
				'bad_auth_header',
				'Authorization header malformed.',
				[ 'status' => 403 ]
			);
		}

		$item = Token::find_single_for_token( $token );
		if ( empty( $item ) ) {
			return new WP_Error( 'invalid_auth_token', 'Invalid token.', [ 'status' => 403 ] );
		}

		$timezone   = new DateTimeZone( 'UTC' );
		$not_before = DateTime::createFromFormat( 'Y-m-d H:i:s', $item['not_before'], $timezone );
		$expired_at = DateTime::createFromFormat( 'Y-m-d H:i:s', $item['expired_at'], $timezone );

		$date_time_now = new DateTime();
		if ( $date_time_now > $expired_at ) {
			return new WP_Error( 'invalid_auth_token', 'Token expired.', [ 'status' => 403 ] );
		}

		if ( $date_time_now < $not_before ) {
			return new WP_Error( 'invalid_auth_token', 'Token not active yet.', [ 'status' => 403 ] );
		}

		return $item;
	}

	/**
	 * Get auth token
	 *
	 * @return string|WP_Error
	 */
	public static function get_auth_token_from_user_request() {
		// Looking for the HTTP_AUTHORIZATION header, if not present just return the user.
		$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? false;

		// Double check for different auth header string (server dependent).
		if ( ! $auth ) {
			$auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? false;
		}

		$data_location = 'header';

		// Check alternative header.
		if ( ! $auth ) {
			$data_location = 'request_url';

			$auth = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? false;
		}

		// Add support for auth key from query string.
		if ( ! $auth ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$auth          = $_REQUEST['_auth_token'] ?? false;
			$data_location = 'request_url';
		}

		if ( ! $auth ) {
			return new WP_Error( 'jwt_auth_no_auth_header', 'Authorization header not found.', [ 'status' => 403 ] );
		}

		if ( 'request_url' === $data_location ) {
			$token = $auth;
		} else {
			// The HTTP_AUTHORIZATION is present verify the format if the format is wrong return the user.
			list( $token ) = sscanf( $auth, 'Bearer %s' );
		}
		if ( ! $token ) {
			return new WP_Error( 'jwt_auth_bad_auth_header', 'Authorization header malformed.', [ 'status' => 403 ] );
		}

		return $token;
	}
}
