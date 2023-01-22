<?php

namespace Stackonet\WP\Framework\Auth\REST;

use Stackonet\WP\Framework\Auth\Models\User;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * UserController class
 */
class UserController extends BaseController {
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
			'/me',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
			]
		);
	}

	/**
	 * Get user profile data
	 *
	 * @param  WP_REST_Request  $request  The details of request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$user = new User( $current_user );

		return $this->respondOK(
			[
				'user' => $user->to_array(),
			]
		);
	}
}
