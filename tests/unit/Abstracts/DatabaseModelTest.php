<?php

namespace StackonetWPFrameworkTest\Abstracts;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class UserEarningModel extends DatabaseModel {
	protected $table = 'user_earnings';
	protected $created_by = 'user_id';
}

class DatabaseModelTest extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		$this->create_table();
	}

	public function tear_down() {
		parent::tear_down();

		global $wpdb;
		$table_name = UserEarningModel::get_table_name();
		// $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}

	public function _create_temporary_tables( $query ) {
		return $query;
	}

	public function _drop_temporary_tables( $query ) {
		return $query;
	}

	public function create_table() {
		global $wpdb;
		$table_name    = UserEarningModel::get_table_name();
		$constant_name = UserEarningModel::get_foreign_key_constant_name( $table_name, $wpdb->users );
		$collate       = $wpdb->get_charset_collate();

		$tables = "CREATE TABLE IF NOT EXISTS {$table_name} (
					id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					user_id bigint(20) UNSIGNED NULL DEFAULT NULL,
					status varchar(20) NOT NULL DEFAULT 'processing',
					commission float(20) NOT NULL DEFAULT '0',
					comment TEXT NULL DEFAULT NULL,
					created_at DATETIME NULL DEFAULT NULL,
					updated_at DATETIME NULL DEFAULT NULL,
					deleted_at DATETIME NULL DEFAULT NULL,
					PRIMARY KEY  (id),
    				CONSTRAINT `$constant_name` FOREIGN KEY (`user_id`) REFERENCES `$wpdb->users`(`ID`)
    				ON DELETE SET NULL ON UPDATE CASCADE
				) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tables );
	}

	public function test_create_records() {
		wp_set_current_user( 1 );
		$id  = UserEarningModel::create( [ 'commission' => 1.5, 'comment' => 'Optional comments' ] );
		$ids = UserEarningModel::batch_create( [
			[ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 2' ],
			[ 'user_id' => 1, 'commission' => 1.6, 'comment' => 'Optional comments 3' ]
		] );

		$model = new UserEarningModel();
		$model->set_prop( 'user_id', 1 );
		$model->set_prop( 'commission', 2.5 );
		$model->set_prop( 'comment', [ 'Optional comments 4', 'Are re serialize' ] );
		$id3 = $model->create();

		$this->assertTrue( $id > 0 );
		$this->assertTrue( $id3 > 0 );
		$this->assertIsArray( $ids );
	}

	public function test_update_record() {
		wp_set_current_user( 1 );

		// Create some fake date.
		$ids = UserEarningModel::batch_create( [
			[ 'user_id' => 1, 'commission' => 0.5, 'comment' => 'Optional comments 1' ],
			[ 'user_id' => 1, 'commission' => 1.6, 'comment' => 'Optional comments 2' ],
			[ 'commission' => 3.5, 'comment' => 'Optional comments 3' ]
		] );

		$this->assertEquals( 3, count( $ids ) );
		$item1 = UserEarningModel::find_single( $ids[0] );
		$this->assertEquals( 0.5, $item1->get_prop( 'commission' ) );

		$item1->set_prop( 'commission', 3.33 );
		$item1->set_prop( 'user_id', null );
		$is_updated = $item1->update();
		$this->assertTrue( $is_updated );
		$item1_updated = UserEarningModel::find_single( $ids[0] );
		$this->assertEquals( 3.33, $item1_updated->get_prop( 'commission' ) );

		$is_updated = UserEarningModel::batch_update( [
			[ 'commission' => 4.5, 'comment' => 'This one is not going to update as there is no id.' ],
			[ 'id' => $ids[1], 'commission' => 4.5 ],
			[ 'id' => $ids[2], 'commission' => 5.5 ],
		] );
		$this->assertTrue( $is_updated );

		$this->assertEquals( 4.5, UserEarningModel::find_single( $ids[1] )->get_prop( 'commission' ) );
		$this->assertEquals( 5.5, UserEarningModel::find_single( $ids[2] )->get_prop( 'commission' ) );

		// It cannot update value without id
		$is_updated = UserEarningModel::update( [
			'commission' => 4.5,
			'comment'    => 'This one is not going to update as there is not id.'
		] );
		$this->assertFalse( $is_updated );
	}

	public function test_trash_and_restore_and_delete_record() {
		$id1 = UserEarningModel::create( [ 'user_id' => 1, 'commission' => 0.5, 'comment' => 'Optional comments 11' ] );
		$id2 = UserEarningModel::create( [ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 12' ] );

		$item1 = UserEarningModel::find_single( $id1 );
		$this->assertNull( $item1->get_prop( 'deleted_at', null ) );
		$item1->trash();
		$this->assertNotNull( $item1->get_prop( 'deleted_at' ) );

		UserEarningModel::restore( $id1 );
		$item1 = UserEarningModel::find_single( $id1 );
		$this->assertNull( $item1->get_prop( 'deleted_at', null ) );

		UserEarningModel::batch_trash( [ $id1, $id2 ] );
		$item1 = UserEarningModel::find_single( $id1 );
		$item2 = UserEarningModel::find_single( $id2 );
		$this->assertNotNull( $item1->get_prop( 'deleted_at' ) );
		$this->assertNotNull( $item2->get_prop( 'deleted_at' ) );
		UserEarningModel::batch_restore( [ $id1, $id2 ] );

		$item2 = UserEarningModel::find_single( $id2 );
		UserEarningModel::delete( $id2 );
		$item2 = UserEarningModel::find_single( $id2 );
		$this->assertTrue( $item2 instanceof \ArrayObject );

		UserEarningModel::batch_delete( [ $id1, $id2 ] );
	}

	public function test_count_records() {
		UserEarningModel::batch_create( [
			[ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 2' ],
			[ 'user_id' => 1, 'commission' => 1.6, 'comment' => 'Optional comments 3' ]
		] );

		$counts = UserEarningModel::count_records();

		$this->assertArrayHasKey( 'all', $counts );
	}

	public function test_batch_crud_operations() {
		$response  = UserEarningModel::batch( 'create', [
			[ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 2' ],
			[ 'user_id' => 1, 'commission' => 1.6, 'comment' => 'Optional comments 3' ]
		] );
		$response2 = UserEarningModel::batch( 'invalid_batch', [
			[ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 2' ],
			[ 'user_id' => 1, 'commission' => 1.6, 'comment' => 'Optional comments 3' ]
		] );

		$this->assertIsArray( $response );
		$this->assertTrue( $response2 instanceof \WP_Error );
	}

	public function test_find_multiple() {
		$ids = UserEarningModel::batch( 'create', [
			[ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 2' ],
			[ 'user_id' => 1, 'commission' => 1.6, 'comment' => 'Optional comments 3' ]
		] );

		foreach ( $ids as $id ) {
			UserEarningModel::trash( $id );
		}

		$data = UserEarningModel::find_multiple( [ 'user_id' => 1, 'status' => 'trash' ] );

		$this->assertIsArray( $data );

		$data = UserEarningModel::find_multiple( [ 'user_id' => 1, 'status' => 'trash' ] );
		$this->assertIsArray( $data );
	}

	public function test_read() {
		$id = UserEarningModel::create( [ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 2' ] );

		$item = ( new UserEarningModel() )->get_data_store()->read( $id );

		// $this->assertIsArray( $item );
		$this->assertArrayHasKey( 'user_id', $item );

		$item2 = ( new UserEarningModel() )->get_data_store()->read( [ 'id__in' => [ $id ] ] );
		$this->assertIsArray( $item2 );
//		$this->assertIsArray( $item2[0] );
	}

	public function test_statuses_count() {
		$status = UserEarningModel::get_statuses_count();
		$this->assertIsArray( $status );
	}

	public function test_query_builder() {
		$id = UserEarningModel::create( [ 'user_id' => 1, 'commission' => 1.5, 'comment' => 'Optional comments 2' ] );

		$orm = ( new UserEarningModel() )->get_query_builder();
		$orm->where( 'user_id', $id );
		$orm->limit( 1 );
		$orm->first();

		$dd = sprintf( "SELECT * FROM wp_user_earnings WHERE user_id = %d LIMIT 1 OFFSET 0", $id );

		$this->assertEquals( $dd, $orm->get_query_sql() );
	}
}
