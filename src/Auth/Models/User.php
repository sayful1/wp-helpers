<?php

namespace Stackonet\WP\Framework\Auth\Models;

use JsonSerializable;
use WP_User;

/**
 * Class User
 *
 * @package Stackonet\WP\Framework\Auth\Models
 */
class User implements JsonSerializable {
	/**
	 * User object
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * User constructor.
	 *
	 * @param  int|string|\stdClass|WP_User  $user  User ID, a WP_User object, or a user object from the DB.
	 */
	public function __construct( $user = null ) {
		$this->user = new WP_User( $user );
	}

	/**
	 * Get array representation of the model
	 *
	 * @return array
	 */
	public function to_array(): array {
		$user = $this->get_user();

		return [
			'id'           => $user->ID,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'avatar_url'   => get_avatar_url( $user, [ 'default' => 'mm' ] ),
			'is_verified'  => $this->is_registration_verified(),
		];
	}

	/**
	 * Get user object
	 *
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		return $this->user;
	}

	/**
	 * Check if registration verified
	 *
	 * @return bool
	 */
	public function is_registration_verified(): bool {
		$is_verified = get_user_meta( $this->get_user()->ID, '_is_registration_verified', true );

		return 'yes' === $is_verified;
	}

	/**
	 * Get user ID
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}
}
