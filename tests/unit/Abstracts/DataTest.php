<?php

namespace StackonetWPFrameworkTest\Abstracts;

use Stackonet\WP\Framework\Abstracts\Data;

class DataTest extends \WP_UnitTestCase {
	/**
	 * @var Data
	 */
	protected $instance;

	public function set_up() {
		parent::set_up();

		$this->instance = new class extends Data {
			public function __construct( $data = [ 'initial_data' => 'Initial Data' ] ) {
				parent::__construct( $data );
			}

			public function set_name( $value ) {
				$this->data['name'] = $value;
			}
		};
	}

	public function test_change_data() {
		$dataFromDb   = [
			'option_id'    => 3201,
			'option_name'  => 'stackonet_support_ticket_table_version',
			'option_value' => '1.0.1',
			'autoload'     => 'yes',
		];
		$dataInstance = new class extends Data {
			public function set_autoload( $value ) {
				$this->data['autoload'] = $value;
			}
		};
		$dataInstance->set_id( $dataFromDb['option_id'] );
		$dataInstance->set_props( $dataFromDb );
		$dataInstance->set_object_read();

		$dataInstance->set_prop( 'option_value', '1.1.0' );
		$dataInstance->set_prop( 'version_number', '1.1.0' );

		$dataInstance->remove_prop( 'version_number' );

		$this->assertEquals( $dataFromDb['option_id'], $dataInstance->get_id() );
		$this->assertEquals( '1.1.0', $dataInstance->get_prop( 'option_value' ) );
		$this->assertEquals( [ 'option_value' => '1.1.0' ], $dataInstance->get_changes() );
		$this->assertEquals( '1.1.0', $dataInstance->to_array()['option_value'] );

		$dataInstance->apply_changes();
		$this->assertEquals( '1.1.0', $dataInstance->to_array()['option_value'] );
	}

	public function test_set_data() {
		$this->instance->set_prop( 'prop1', 'prop1 value' );
		$this->instance['prop_int_str']   = '10';
		$this->instance['prop_float_str'] = '10.549';

		$this->assertEquals( 'prop1 value', $this->instance->prop1 );
		$this->assertEquals( 'prop1 value', $this->instance['prop1'] );
		$this->assertEquals( 'prop1 value', $this->instance->get_prop( 'prop1' ) );
		$this->assertEquals( '10', $this->instance->get_prop( 'prop_int_str' ) );
		$this->assertEquals( '10.549', $this->instance->get_prop( 'prop_float_str' ) );
		$this->assertEquals( 'Initial Data', $this->instance->get_prop( 'initial_data' ) );
	}

	public function test_has_data() {
		$this->instance->set_prop( 'prop2', 'prop2 value' );

		$this->assertTrue( $this->instance->has_prop( 'prop2' ) );
		$this->assertTrue( isset( $this->instance['prop2'] ) );
		$this->assertTrue( isset( $this->instance->prop2 ) );
		$this->assertFalse( $this->instance->has_prop( 'prop3' ) );
	}

	public function test_remove_data() {
		$this->instance->set_prop( 'prop1', 'prop1 value' );
		$this->instance->set_prop( 'prop2', 'prop2 value' );
		$this->instance->set_prop( 'prop3', 'prop3 value' );

		$this->instance->remove_prop( 'prop1' );
		unset( $this->instance['prop2'] );
		unset( $this->instance['prop3'] );

		$this->assertFalse( $this->instance->has_prop( 'prop1' ) );
		$this->assertFalse( $this->instance->has_prop( 'prop2' ) );
		$this->assertFalse( $this->instance->has_prop( 'prop3' ) );
	}

	public function test_it_return_default_value_if_props_not_exits() {
		$this->assertEquals( 'default value', $this->instance->get_prop( 'key_not_exists', 'default value' ) );
	}

	public function test_it_returns_json_string_of_data_when_echo_class() {
		$this->instance->set_prop( 'prop5', 'prop5 value' );
		$this->assertEquals( $this->instance, wp_json_encode( $this->instance->to_array() ) );
		$this->assertEquals( wp_json_encode( $this->instance ), wp_json_encode( $this->instance->to_array() ) );
	}

	public function test_deprecated_methods_work_as_expected() {
		$this->instance->set( 'prop10', 'prop10 value' );
		$this->instance->set_data( [ 'prop11' => 'prop11 value', 'prop12' => 'prop12 value' ] );

		$this->assertEquals( 'prop12 value', $this->instance->get( 'prop12' ) );

		$this->assertTrue( $this->instance->has( 'prop10' ) );
		$this->assertEquals( 'prop10 value', $this->instance->get( 'prop10' ) );

		$this->instance->remove( 'prop10' );
		$this->assertFalse( $this->instance->has( 'prop10' ) );
	}

	public function test_get_data() {
		$dataFromDb   = [
			'id'           => 3201,
			'option_name'  => 'stackonet_support_ticket_table_version',
			'option_value' => '1.0.1',
			'autoload'     => 'yes',
		];
		$dataInstance = new Data();
		$dataInstance->set_props( $dataFromDb );

		$this->assertEquals( 3201, $dataInstance->get_id() );
		$this->assertEquals( 3201, $dataInstance->get_data()['id'] );
	}
}
