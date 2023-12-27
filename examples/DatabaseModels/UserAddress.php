<?php

namespace Stackonet\WP\Examples\DatabaseModels;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class UserAddress extends DatabaseModel {

	/**
	 * @var string
	 */
	protected $table = 'user_addresses';

	/**
	 * Column name for holding author id
	 *
	 * @var string
	 */
	protected $created_by = 'user_id';

	/**
	 * User/Customer full name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->get_prop( 'name' );
	}

	/**
	 * Get user/customer last name
	 *
	 * @return string
	 */
	public function get_last_name(): string {
		$name_parts = explode( " ", trim( $this->get_name() ) );

		return array_pop( $name_parts );
	}

	/**
	 * Get user/customer first name
	 *
	 * @return string
	 */
	public function get_first_name(): string {
		return trim( str_replace( $this->get_last_name(), '', $this->get_name() ) );
	}

	/**
	 * The entire phone number without the country code
	 *
	 * @return string
	 */
	public function get_phone_number(): string {
		return $this->get_prop( 'phone_number' );
	}

	/**
	 * A street address and can be multiple line of text
	 *
	 * @return string
	 */
	public function get_street_address(): string {
		return '';
	}

	/**
	 * Each individual line of the street address
	 * (Flat / House no. / Building / Company / Apartment)
	 *
	 * @return string
	 */
	public function get_address_line1(): string {
		return $this->get_prop( 'address_line1' );
	}

	/**
	 * Each individual line of the street address
	 * (Area / Colony / Street / Sector / Village)
	 *
	 * @return string
	 */
	public function get_address_line2(): string {
		return $this->get_prop( 'address_line2' );
	}

	/**
	 * The second administrative level in the address.
	 * (City / Town)
	 *
	 * @return string
	 */
	public function get_address_level2(): string {
		return $this->get_prop( 'address_level2' );
	}

	/**
	 * The first administrative level in the address.
	 * (State / Province / Region)
	 *
	 * @return string
	 */
	public function get_address_level1(): string {
		return $this->get_prop( 'address_level1' );
	}

	/**
	 * A two character ISO country code.
	 *
	 * @return string
	 */
	public function get_country_code(): string {
		return $this->get_prop( 'country_code' );
	}

	/**
	 * A country name.
	 *
	 * @return string
	 */
	public function get_country_name(): string {
		return $this->get_prop( 'country_name' );
	}

	/**
	 * Get postal code
	 *
	 * @return string
	 */
	public function get_postal_code(): string {
		return $this->get_prop( 'postal_code' );
	}

	/**
	 * A name of nearby famous place
	 *
	 * @return string
	 */
	public function get_landmark(): string {
		return $this->get_prop( 'landmark' );
	}

	/**
	 * The type of address. (Home / Office)
	 *
	 * @return string
	 */
	public function get_address_type(): string {
		return $this->get_prop( 'address_type' );
	}

	/**
	 * User given custom label for tha address
	 *
	 * @return string
	 */
	public function get_address_label(): string {
		return $this->get_prop( 'address_label' );
	}

	/**
	 * Create table
	 */
	public static function create_table() {
		global $wpdb;
		$self       = new static;
		$table_name = $self->get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) unsigned NOT NULL,
                `name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Full Name',
                `phone_number` VARCHAR(20) NULL DEFAULT NULL COMMENT 'The entire phone number without the country code',
                `address_line1` VARCHAR(255) NULL DEFAULT NULL COMMENT 'The first line of the street address',
                `address_line2` VARCHAR(255) NULL DEFAULT NULL COMMENT 'The second line of the street address',
                `address_level2` VARCHAR(100) NULL DEFAULT NULL COMMENT 'The second administrative level in the address. (City / Town)',
                `address_level1` VARCHAR(100) NULL DEFAULT NULL COMMENT 'The first administrative level in the address. (State / Province / Region)',
                `country_code` CHAR(2) NULL DEFAULT NULL COMMENT 'A two character ISO country code',
                `country_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'A country name',
                `postal_code` VARCHAR(20) NULL DEFAULT NULL COMMENT 'A postal code or a ZIP code.',
                `landmark` VARCHAR(255) NULL DEFAULT NULL COMMENT 'A name of nearby famous place',
                `address_type` VARCHAR(50) NULL DEFAULT NULL COMMENT 'The type of address. (Home / Office)',
                `address_label` VARCHAR(50) NULL DEFAULT NULL COMMENT 'User given address label',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
    			INDEX `country_code` (`country_code`),
    			INDEX `postal_code` (`postal_code`),
    			INDEX `address_level1` (`address_level1`),
    			INDEX `address_level2` (`address_level2`)
            ) $collate;";

		$version = get_option( $table_name . '-version' );
		if ( false === $version ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $table_schema );

			$constant_name = $self->get_foreign_key_constant_name( $table_name, $wpdb->users );
			$sql           = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`user_id`)";
			$sql           .= " REFERENCES `{$wpdb->users}`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.0', false );
		}
	}
}
