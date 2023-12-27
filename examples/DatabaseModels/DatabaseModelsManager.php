<?php

namespace Stackonet\WP\Examples\DatabaseModels;

class DatabaseModelsManager {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'admin_init', [ UserAddress::class, 'create_table' ] );
			add_action( 'wp_ajax_database_model_test', [ self::$instance, 'database_model_test' ] );
		}

		return self::$instance;
	}

	public function database_model_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry. This link only for developer to do some testing.', 'wp-helper' ) );
		}
		// Create some fake date.
//		$ids = UserAddress::batch_create( [
//			[ 'user_id' => 1, 'name' => 'Sayful Islam', 'country_code' => 'BD' ],
//			[ 'user_id' => 1, 'name' => 'Aklima Islam', 'country_code' => 'BD' ],
//			[ 'user_id' => 1, 'name' => 'Saif Al Araf', 'country_code' => 'BD' ],
//			[ 'user_id' => 1, 'name' => 'Jara Islam', 'country_code' => 'BD' ],
//		] );
//		$ids = UserAddress::batch_update( [
//			[ 'id' => 1, 'name' => 'Sayful Islam', 'country_code' => 'BD' ],
//			[ 'id' => 2, 'name' => 'Aklima Islam', 'country_code' => 'BD' ],
//			[ 'id' => 2, 'name' => 'Saif Al Araf', 'country_code' => 'BD' ],
//			[ 'id' => 2, 'name' => 'Jara Islam', 'country_code' => 'BD' ],
//		] );
		$count = UserAddress::get_statuses_count();
		var_dump( $count );
		wp_die();
	}
}
