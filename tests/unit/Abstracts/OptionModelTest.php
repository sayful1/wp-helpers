<?php

namespace StackonetWPFrameworkTest\Abstracts;

use Stackonet\WP\Framework\Abstracts\OptionModel;

class OptionModelTest extends \WP_UnitTestCase {
	/**
	 * @var OptionModel
	 */
	protected $instance;

	public function setUp() {
		parent::set_up();

		$this->instance = new class extends OptionModel {
			protected $option_name = '_99_names_of_allah';

			protected $default_data = [
				'name_in_english'     => '',
				'name_in_arabic'      => '',
				'meaning'             => '',
				'benefits_of_reading' => '',
			];
		};
	}

	public function test_crud_operations() {
		// Test create record.
		$record = $this->instance->create( [ 'name_in_english' => 'Allah' ] );
		$this->assertEquals( 'Allah', $record['name_in_english'] );

		// Test update record.
		$updated_data = $this->instance->update( [ 'id' => $record['id'], 'name_in_english' => 'Razzak' ] );
		$this->assertEquals( 'Razzak', $updated_data['name_in_english'] );

		// Test find record
		$item = $this->instance->get_option( $record['id'] );
		$this->assertEquals( 'Razzak', $item['name_in_english'] );

		// Test delete record
		$delete_response = $this->instance->delete( $record['id'] );
		$this->assertTrue( $delete_response );
		$item = $this->instance->get_option( $record['id'] );
		$this->assertArrayNotHasKey( 'name_in_english', $item );

		$this->assertFalse( $this->instance->delete( $record['id'] ) );
	}
}