<?php

namespace Stackonet\WP\Framework\Auth\REST;

use Exception;
use Stackonet\WP\Framework\Auth\Emails\EmailOtp;
use Stackonet\WP\Framework\Auth\SmsOtpAuth;
use Stackonet\WP\Framework\Supports\Validate;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

/**
 * EmailAuthController class
 */
class EmailAuthController extends BaseController {
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
			'/auth/email/status',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => '__return_true',
				'args'                => $this->get_status_params(),
			]
		);
		register_rest_route(
			$this->namespace,
			'/auth/email/otp/generate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_otp' ],
				'args'                => $this->get_status_params(),
				'permission_callback' => '__return_true',
			]
		);
		register_rest_route(
			$this->namespace,
			'/auth/email/otp/validate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'validate_otp' ],
				'args'                => $this->get_status_params(),
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Generate OTP
	 *
	 * @param  WP_REST_Request $request  The WP_REST_Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		$number = trim( $request->get_param( 'phone_number' ) );
		$number = strpos( $number, '+', 0 ) === false ? '+' . $number : $number;
		$email  = trim( $request->get_param( 'email' ) );
		$errors = [];
		if ( ! Validate::phone( $number ) ) {
			$errors['phone_number'] = 'Please provide a valid phone number in E164 format.';
		}
		if ( ! Validate::email( $email ) ) {
			$errors['email'] = 'Please provide a valid email address.';
		}

		if ( count( $errors ) ) {
			return $this->respondUnprocessableEntity( null, null, $errors );
		}

		$user    = get_user_by( 'email', $email );
		$user_id = SmsOtpAuth::get_user_id( $number );
		// If both phone and email exists for same account, log in user
		// If both phone and email exists for separate account, log in user
		// If email exists but phone number not exist, add phone to email (send otp to email)
		// If phone number exist but email not exist, add email to phone (send otp to email)

		$response = [
			'user_email' => 0,
			'user_phone' => 0,
		];
		if ( $user instanceof WP_User ) {
			$response['user_email'] = $user->ID;
		}
		if ( $user_id ) {
			$response['user_phone'] = $user_id;
		}

		if ( 0 === $response['user_email'] && 0 === $response['user_phone'] ) {
			$response['action'] = 'register';
		} elseif ( $response['user_email'] === $response['user_phone'] ) {
			$response['action'] = 'login';
		} elseif ( 0 === $response['user_email'] || 0 === $response['user_phone'] ) {
			$response['action'] = 'merge';
		} else {
			$response['action'] = 'conflict';
		}

		return $this->respondOK( $response );
	}

	/**
	 * Generate otp via email
	 *
	 * @param  WP_REST_Request $request  The request details.
	 *
	 * @return WP_REST_Response
	 */
	public function generate_otp( WP_REST_Request $request ): WP_REST_Response {
		$number = trim( $request->get_param( 'phone_number' ) );
		$number = strpos( $number, '+', 0 ) === false ? '+' . $number : $number;
		$email  = trim( $request->get_param( 'email' ) );
		$errors = [];
		if ( ! Validate::phone( $number ) ) {
			$errors['phone_number'] = 'Please provide a valid phone number in E164 format.';
		}
		if ( ! Validate::email( $email ) ) {
			$errors['email'] = 'Please provide a valid email address.';
		}

		if ( count( $errors ) ) {
			return $this->respondUnprocessableEntity( null, null, $errors );
		}

		$interval       = 60;
		$current_time   = time();
		$transient_name = 'email_otp_' . md5( $email );
		$data           = get_transient( $transient_name );
		$data_exists    = true;
		if ( false === $data ) {
			$data_exists = false;
			$data        = [
				'time'  => $current_time,
				'email' => $email,
				'otp'   => wp_generate_password( 6, false, false ),
			];
		}

		if ( $data_exists && $current_time < ( $data['time'] + $interval ) ) {
			$remaining = ( $data['time'] + $interval ) - $current_time;
			$message   = 'You can resend request after ' . $remaining . ' seconds.';

			return $this->respondUnprocessableEntity( 'too_many_request', $message );
		}

		try {
			( new EmailOtp( $email, (string) $data['otp'] ) )->send();
		} catch ( Exception $e ) {
			return $this->respondInternalServerError();
		}

		$data['time'] = $current_time;
		set_transient( $transient_name, $data, ( MINUTE_IN_SECONDS * 5 ) );

		$message = $data_exists ? 'OTP resent to the email.' : 'OTP sent to the email.';

		return $this->respondOK( [ 'message' => $message ] );
	}

	/**
	 * Validate otp via email
	 *
	 * @param  WP_REST_Request $request  The request details.
	 *
	 * @return WP_REST_Response
	 */
	public function validate_otp( WP_REST_Request $request ): WP_REST_Response {
		$number = trim( $request->get_param( 'phone_number' ) );
		$number = strpos( $number, '+', 0 ) === false ? '+' . $number : $number;
		$email  = trim( $request->get_param( 'email' ) );
		$otp    = $request->get_param( 'otp' );
		$errors = [];
		if ( ! Validate::phone( $number ) ) {
			$errors['phone_number'] = 'Please provide a valid phone number in E164 format.';
		}
		if ( ! Validate::email( $email ) ) {
			$errors['email'] = 'Please provide a valid email address.';
		}
		if ( 6 !== strlen( (string) $otp ) ) {
			$errors['otp'] = 'Invalid code';
		}

		if ( count( $errors ) ) {
			return $this->respondUnprocessableEntity( null, null, $errors );
		}

		$transient_name = 'email_otp_' . md5( $email );
		$data           = get_transient( $transient_name );
		if ( false === $data ) {
			return $this->respondUnprocessableEntity( 'otp_expired', 'OTP has been expired.' );
		}

		if ( isset( $data['otp'] ) && $data['otp'] !== $otp ) {
			return $this->respondNotFound( 'invalid_otp', 'Invalid OTP.' );
		}

		$message  = 'OTP has been validated successfully.';
		$response = null;

		$user_id       = SmsOtpAuth::get_user_id( $number );
		$user_by_phone = get_user_by( 'id', $user_id );
		$user_by_email = get_user_by( 'email', $email );
		if ( $user_by_phone instanceof WP_User ) {
			$user_by_phone->user_email = $email;
			wp_update_user( $user_by_phone );
		}

		if ( $user_by_email instanceof WP_User ) {
			SmsOtpAuth::update_phone_number( $user_by_email->ID, $number );
		}

		if ( ! ( $user_by_phone instanceof WP_User || $user_by_email instanceof WP_User ) ) {
			$user_id = $this->create_new_user( $request );
			if ( ! is_wp_error( $user_id ) ) {
				$response['user_id'] = $user_id;
			}
		}

		// remove Transient.
		delete_transient( $transient_name );

		return $this->respondOK( $response, $message );
	}

	/**
	 * Get status parameters
	 *
	 * @return array[]
	 */
	public function get_status_params(): array {
		return [
			'phone_number' => [
				'description'       => 'User phone number.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'email'        => [
				'description'       => 'User email address. Must be unique.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Create new user.
	 *
	 * @param  WP_REST_Request $request  The request details.
	 *
	 * @return int|\WP_Error
	 */
	private function create_new_user( WP_REST_Request $request ) {
		$name         = sanitize_text_field( $request->get_param( 'name' ) );
		$email        = sanitize_text_field( $request->get_param( 'email' ) );
		$phone_number = sanitize_text_field( $request->get_param( 'phone_number' ) );

		$name_parts = explode( ' ', $name );
		$last_name  = array_pop( $name_parts );
		$first_name = count( $name_parts ) > 0 ? implode( ' ', $name_parts ) : '';

		$user_data = array(
			'user_login' => sanitize_user( $phone_number ),
			'user_email' => is_email( $email ) ? $email : '',
			'user_pass'  => wp_generate_password(),
		);

		if ( ! empty( $name ) ) {
			$user_data['first_name']   = $first_name;
			$user_data['last_name']    = $last_name;
			$user_data['display_name'] = $name;
		}

		$user_id = wp_insert_user( $user_data );

		if ( ! is_wp_error( $user_id ) ) {
			SmsOtpAuth::update_phone_number( $user_id, $phone_number );
		}

		return $user_id;
	}
}
