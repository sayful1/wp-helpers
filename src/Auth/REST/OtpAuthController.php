<?php

namespace Stackonet\WP\Framework\Auth\REST;

use Stackonet\WP\Framework\Auth\Models\Token;
use Stackonet\WP\Framework\Auth\Models\User;
use Stackonet\WP\Framework\Auth\SmsOtpAuth;
use Stackonet\WP\Framework\Supports\Validate;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * OtpAuthController class
 */
class OtpAuthController extends BaseController {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/otp/generate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_otp' ],
				'permission_callback' => '__return_true',
			]
		);
		register_rest_route(
			$this->namespace,
			'/otp/validate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'validate_otp' ],
				'permission_callback' => '__return_true',
			]
		);
		register_rest_route(
			$this->namespace,
			'/otp/registration',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'registration' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => '__return_true',
			]
		);
		register_rest_route(
			$this->namespace,
			'/otp/link-unlink',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'link_unlink' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Generate OTP
	 *
	 * @param  WP_REST_Request  $request  The WP_REST_Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function generate_otp( $request ) {
		$number = trim( $request->get_param( 'phone_number' ) );
		$number = preg_replace( '/[^0-9]/', '', $number );
		$number = strpos( $number, '+' ) === false ? '+' . $number : $number;

		if ( ! Validate::phone( $number ) ) {
			return $this->respondUnprocessableEntity( 'invalid_phone_number', 'Invalid phone number.' );
		}

		$user_only = Validate::checked( $request->get_param( 'user_only' ) );
		if ( $user_only ) {
			$user_id = SmsOtpAuth::get_user_id( $number );
			$user    = get_user_by( 'id', $user_id );
			if ( ! $user instanceof WP_User ) {
				return $this->respondNotFound( null, 'No user found for the phone number.' );
			}
		}

		$interval     = 60;
		$params       = map_deep( $request->get_params(), 'sanitize_text_field' );
		$current_time = time();
		$data         = get_transient( $number );
		$data_exists  = true;
		if ( false === $data ) {
			$data_exists = false;
			$data        = [
				'time'   => $current_time,
				'phone'  => $number,
				'otp'    => wp_rand( 100000, 999999 ),
				'params' => $params,
			];
		}

		if ( $data_exists && $current_time < ( $data['time'] + $interval ) ) {
			$remaining = ( $data['time'] + $interval ) - $current_time;
			$message   = 'You can resend request after ' . $remaining . ' seconds.';

			return $this->respondUnprocessableEntity( 'too_many_request', $message );
		}

		$response = SmsOtpAuth::send_opt( $number, $data['otp'] );

		if ( is_wp_error( $response ) ) {
			return $this->respondWithError( $response );
		}

		$data['time'] = $current_time;
		set_transient( $number, $data, ( MINUTE_IN_SECONDS * 5 ) );

		$message = $data_exists ? 'OTP resent to the number.' : 'OTP sent to the number.';

		return $this->respondOK( [ 'message' => $message ] );
	}

	/**
	 * Validate OTP
	 *
	 * @param  WP_REST_Request  $request  The WP_REST_Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function validate_otp( $request ) {
		$number = trim( $request->get_param( 'phone_number' ) );
		$number = preg_replace( '/[^0-9]/', '', $number );
		$number = strpos( $number, '+', 0 ) === false ? '+' . $number : $number;

		if ( ! Validate::number( $number ) ) {
			return $this->respondUnprocessableEntity( 'invalid_phone_number', 'Invalid phone number.' );
		}

		$data = get_transient( $number );
		if ( false === $data ) {
			return $this->respondUnprocessableEntity( 'otp_expired', 'OTP has been expired.' );
		}

		$otp = (int) $request->get_param( 'otp' );
		if ( isset( $data['otp'] ) && $data['otp'] !== $otp ) {
			return $this->respondNotFound( 'invalid_otp', 'Invalid OTP.' );
		}

		$message  = 'OTP has been validated successfully.';
		$response = new \ArrayObject();

		$user_id = SmsOtpAuth::get_user_id( $number );
		$user    = get_user_by( 'id', $user_id );
		if ( $user instanceof WP_User ) {
			$_user    = new User( $user );
			$response = [
				'action' => 'reload',
				'user'   => $_user->to_array(),
			];
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID, false );
		}
		// remove Transient.
		delete_transient( $number );

		return $this->respondOK( $response, $message );
	}

	/**
	 * Registration new user
	 *
	 * @param  WP_REST_Request  $request  The WP_REST_Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function registration( $request ) {
		$number = trim( $request->get_param( 'phone_number' ) );
		$number = preg_replace( '/[^0-9]/', '', $number );
		$number = strpos( $number, '+', 0 ) === false ? '+' . $number : $number;
		$otp    = $request->get_param( 'otp' );

		if ( ! Validate::number( $number ) ) {
			return $this->respondUnprocessableEntity( '', 'Not a Valid Phone number' );
		}

		$is_otp_valid = SmsOtpAuth::validate_otp( $number, $otp );

		if ( ! $is_otp_valid ) {
			return $this->respondNotFound( 'invalid_otp', 'Invalid OTP.' );
		}

		$user_id = SmsOtpAuth::get_user_id( $number );
		$user    = get_user_by( 'id', $user_id );

		// If user exists, login the user.
		if ( $user_id && $user instanceof WP_User ) {
			return $this->validate_otp( $request );
		}

		$username = $request->get_param( 'username' );
		$username = ! empty( $username ) ? $username : $number;
		$username = sanitize_user( $username );

		$email = $request->get_param( 'email' );

		$name       = sanitize_text_field( $request->get_param( 'name' ) );
		$name_parts = explode( ' ', $name );
		$last_name  = array_pop( $name_parts );
		$first_name = count( $name_parts ) > 0 ? implode( ' ', $name_parts ) : '';

		$user_data = array(
			'user_login' => $username,
			'user_email' => is_email( $email ) ? $email : '',
			'user_pass'  => wp_generate_password(),
		);

		if ( ! empty( $name ) ) {
			$user_data['first_name']   = $first_name;
			$user_data['last_name']    = $last_name;
			$user_data['display_name'] = $name;
		}

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $this->respondUnprocessableEntity( $user_id->get_error_code(), $user_id->get_error_message() );
		}

		SmsOtpAuth::update_phone_number( $user_id, $number );

		$user  = get_user_by( 'id', $user_id );
		$token = Token::get_token_for_user( $user );

		if ( is_wp_error( $token ) ) {
			return $this->respondWithError( $token );
		}

		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID, false );

		return $this->respondOK( $this->format_response( $token, $user ) );
	}

	/**
	 * Link or unlink a phone number.
	 *
	 * @param  WP_REST_Request  $request  The WP_REST_Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function link_unlink( $request ) {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}

		$unlink = Validate::checked( $request->get_param( 'unlink' ) );

		$number = trim( $request->get_param( 'phone_number' ) );
		$number = strpos( $number, '+', 0 ) === false ? '+' . $number : $number;
		$otp    = $request->get_param( 'otp' );

		if ( $unlink ) {
			SmsOtpAuth::delete_phone_number( $user->ID );
		} elseif ( SmsOtpAuth::validate_otp( $number, $otp ) ) {
			SmsOtpAuth::update_phone_number( $user->ID, $number );
		}

		$token = Token::get_token_for_user( $user->ID );

		if ( is_wp_error( $token ) ) {
			return $this->respondUnprocessableEntity( $token->get_error_code(), $token->get_error_message() );
		}

		return $this->respondOK( $this->format_response( $token, $user ) );
	}

	/**
	 * @param  string  $token
	 * @param  WP_User  $user
	 *
	 * @return array
	 */
	public function format_response( string $token, WP_User $user ): array {
		$data          = ( new User( $user ) )->to_array();
		$data['token'] = $token;

		return $data;
	}

	/**
	 * Get collection parameters
	 *
	 * @return array|array[]
	 */
	public function get_collection_params(): array {
		return [
			'phone_number' => array(
				'description'       => 'User phone number.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'otp'          => array(
				'description'       => 'OTP code.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'email'        => array(
				'description'       => 'User email address. Must be unique.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'username'     => array(
				'description'       => 'User username. Must be unique.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'name'         => array(
				'description'       => 'User name.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'password'     => array(
				'description'       => 'User password.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		];
	}
}
