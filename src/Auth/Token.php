<?php

namespace Stackonet\WP\Framework\Auth;

use Stackonet\WP\Framework\Abstracts\DataStoreBase;
use WP_Error;

/**
 * Token class
 */
class Token extends DataStoreBase {
	/**
	 * The table name
	 *
	 * @var string
	 */
	protected $table = 'users_auth_tokens';

	/**
	 * Get record by token
	 *
	 * @param  string  $token  The token to validate.
	 *
	 * @return array|null
	 */
	public static function find_single_for_token( string $token ): ?array {
		return ( new static() )->get_query_builder()->where( 'token', $token )->first();
	}

	/**
	 * Get existing token for a user, generate if not exists.
	 *
	 * @param  int  $user_id  The user id.
	 *
	 * @return string|WP_Error
	 */
	public static function get_token_for_user( int $user_id ) {
		$record = ( new static() )->get_query_builder()->where( 'user_id', $user_id )->first();
		if ( empty( $record ) ) {
			return self::generate_token_for_user( $user_id );
		}

		return $record['token'];
	}

	/**
	 * Generate a new token for a user
	 *
	 * @param  int  $user_id  The user id.
	 * @param  int  $month  The month. Default is 1. Max is 12.
	 *
	 * @return string|WP_Error
	 */
	public static function generate_token_for_user( int $user_id, int $month = 1, ?string $note = '' ) {
		// Month value can be from 1 to 12.
		$month      = min( 12, max( 1, $month ) );
		$now        = time();
		$issued_at  = gmdate( 'Y-m-d H:i:s', $now );
		$expired_at = gmdate( 'Y-m-d H:i:s', $now + ( MONTH_IN_SECONDS * $month ) );

		$prefix = wp_generate_password( 5, false );
		$number = uniqid();
		$suffix = wp_generate_password( 32 - ( strlen( $number ) + 5 ), false );
		$token  = $prefix . $number . $suffix;

		$data = [
			'user_id'    => $user_id,
			'token'      => $token,
			'issued_at'  => $issued_at,
			'not_before' => $issued_at,
			'expired_at' => $expired_at,
		];

		if ( ! empty( $note ) ) {
			$data['note'] = $note;
		}

		$record_id = ( new static() )->create( $data );
		if ( ! $record_id ) {
			return new WP_Error( 'token_create_failed', 'Token creation failed.' );
		}

		return $token;
	}

	/**
	 * Create table
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;
		$self         = new static();
		$table        = $self->get_table_name();
		$collate      = $wpdb->get_charset_collate();
		$table_schema = "CREATE TABLE IF NOT EXISTS {$table} (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`user_id` bigint(20) unsigned NOT NULL,
			`token` CHAR(32) NOT NULL,
			`note` VARCHAR(255) NULL DEFAULT NULL COMMENT 'What’s this token for?',
            `last_ip` TEXT NULL DEFAULT NULL COMMENT 'Last used from IP.',
            `last_used_at` datetime DEFAULT NULL COMMENT 'Last used datetime.',
            `issued_at` datetime DEFAULT NULL,
            `not_before` datetime DEFAULT NULL,
            `expired_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
		) {$collate};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );

		$version = get_option( 'users_auth_tokens_db_version' );
		if ( false === $version ) {
			$constant_name = $self->get_foreign_key_constant_name( $table, $wpdb->users );
			$sql           = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`user_id`)";
			$sql           .= " REFERENCES `{$wpdb->users}`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $sql );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "ALTER TABLE `{$table}` ADD UNIQUE `token` (`token`(32));" );
			update_option( 'users_auth_tokens_db_version', '1.0.0', false );
		}
	}
}
