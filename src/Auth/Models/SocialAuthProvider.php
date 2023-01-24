<?php

namespace Stackonet\WP\Framework\Auth\Models;

use ArrayObject;
use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_User;

/**
 * Class SocialAuthProvider
 *
 * @package Stackonet\WP\Framework\Auth\Models
 */
class SocialAuthProvider extends DatabaseModel {

	/**
	 * The table name
	 *
	 * @var string
	 */
	protected $table = 'users_social_auth_provider';

	/**
	 * Supported providers
	 *
	 * @var string[]
	 */
	protected static $providers = [ 'apple', 'google', 'facebook', 'twitter' ];

	/**
	 * Get providers
	 *
	 * @return string[]
	 */
	public static function get_providers(): array {
		return self::$providers;
	}

	/**
	 * Get array representation of the model
	 */
	public function to_array(): array {
		return [
			'email_address' => $this->get_prop( 'email_address', '' ),
			'phone_number'  => $this->get_prop( 'phone_number', '' ),
			'display_name'  => $this->get_display_name(),
			'is_active'     => $this->is_active(),
		];
	}

	/**
	 * Is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return Validate::checked( $this->get_prop( 'is_active' ) );
	}

	/**
	 * Get display name
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		$first_name = $this->get_prop( 'first_name', '' );
		$last_name  = $this->get_prop( 'last_name', '' );

		if ( ! empty( $first_name ) ) {
			return "{$first_name} {$last_name}";
		}

		return $last_name;
	}

	/**
	 * Get provider info
	 *
	 * @param  string $provider  Provider name.
	 * @param  string $provider_id  Provider ID.
	 * @param  int    $user_id  User ID.
	 *
	 * @return ArrayObject|static
	 */
	public static function find_for( string $provider, string $provider_id, $user_id = 0 ) {
		global $wpdb;
		$table = ( new static() )->get_table_name();
		$sql   = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} WHERE provider = %s AND provider_id = %s",
			$provider,
			sha1( $provider_id )
		);

		if ( $user_id ) {
			$sql .= $wpdb->prepare( ' AND user_id = %d', intval( $user_id ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? new static( $result ) : new ArrayObject();
	}

	/**
	 * Find for user
	 *
	 * @param  int         $user_id  User ID.
	 * @param  string|null $provider  Provider name.
	 *
	 * @return static[]|array
	 */
	public static function find_for_user( int $user_id, ?string $provider = null ): array {
		global $wpdb;
		$table = ( new static() )->get_table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id );

		if ( ! empty( $provider ) ) {
			$sql .= $wpdb->prepare( ' AND provider = %s', $provider );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql, ARRAY_A );
		$items   = [];
		foreach ( $results as $result ) {
			$items[] = new static( $result );
		}

		return $items;
	}

	/**
	 * Create or update a social auth provider
	 *
	 * @param  array $data  The data.
	 *
	 * @return int
	 */
	public static function create_or_update( array $data ): int {
		$item                = static::find_for( $data['provider'], $data['provider_id'] );
		$data['provider_id'] = sha1( $data['provider_id'] );
		if ( $item instanceof static ) {
			$data['id'] = (int) $item->get_prop( 'id' );
			( new static() )->update( $data );

			return (int) $item->get_prop( 'id' );
		}

		return ( new static() )->create( $data );
	}

	/**
	 * Unlink a social auth provider
	 *
	 * @param  array $data  The data.
	 *
	 * @return bool
	 */
	public static function unlink( array $data ): bool {
		$item = static::find_for( $data['provider'], $data['provider_id'] );
		if ( $item instanceof self ) {
			return $item->delete( (int) $item->get_prop( 'id' ) );
		}

		return false;
	}

	/**
	 * Find user by provider and provider id
	 *
	 * @param  string|mixed $provider  Provider name.
	 * @param  string|mixed $provider_unique_id  Provider unique id.
	 *
	 * @return WP_Error|WP_User
	 */
	public static function authenticate( $provider, $provider_unique_id ) {
		if ( empty( $provider_unique_id ) ) {
			return new WP_Error( 'missing_required_parameter', 'provider_id is required.' );
		}

		if ( ! in_array( $provider, static::get_providers(), true ) ) {
			return new WP_Error( 'unsupported_provider', 'Provider does not support.', array( 'status' => 403 ) );
		}

		$social_auth_provider = static::find_for( $provider, $provider_unique_id );
		if ( $social_auth_provider instanceof self ) {
			$user = get_user_by( 'id', $social_auth_provider->get_prop( 'user_id' ) );
		}

		if ( ! ( isset( $user ) && $user instanceof WP_User ) ) {
			return new WP_Error( 'user_not_found', 'No user found.', array( 'status' => 404 ) );
		}

		return $user;
	}

	/**
	 * Get providers
	 *
	 * @param  string|mixed $email  Email address.
	 *
	 * @return bool
	 */
	public static function email_exists( $email ): bool {
		global $wpdb;
		$table = static::get_table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare( "SELECT * FROM $table WHERE email_address = %s", $email );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_row( $sql, ARRAY_A );

		return is_array( $result ) && ( isset( $result['email_address'] ) && $result['email_address'] === $email );
	}

	/**
	 * Create table
	 */
	public static function create_table() {
		global $wpdb;
		$self       = new static();
		$table_name = $self->get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) unsigned NOT NULL,
                `provider` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Social provider name (apple, google, facebook, etc)',
                `provider_id` CHAR(40) NULL DEFAULT NULL COMMENT 'Social provider sha1 hash value',
                `email_address` VARCHAR(100) NULL DEFAULT NULL,
                `phone_number` VARCHAR(20) NULL DEFAULT NULL,
                `first_name` VARCHAR(100) NULL DEFAULT NULL,
                `last_name` VARCHAR(50) NULL DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
    			INDEX `provider` (`provider`),
    			INDEX `provider_id` (`provider_id`)
            ) $collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );

		$version = get_option( 'users_social_auth_provider_db_version', '0.1.0' );
		if ( version_compare( $version, '1.0.0', '<' ) ) {
			$constant_name = $self->get_foreign_key_constant_name( $table_name, $wpdb->users );
			$sql           = "ALTER TABLE `$table_name` ADD CONSTRAINT `$constant_name` FOREIGN KEY (`user_id`)";
			$sql          .= " REFERENCES `$wpdb->users`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			update_option( 'users_social_auth_provider_db_version', '1.0.0', false );
		}
	}
}
