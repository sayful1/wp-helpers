<?php

namespace StackonetWPFrameworkTest\Supports;

use Stackonet\WP\Framework\Supports\ArrayHelper;

class ArrayHelperTest extends \WP_UnitTestCase {
	public function test_insert_after() {
		$initial_array  = [ 'item_1' => 'foo', 'item_2' => 'bar' ];
		$expected_array = [ 'item_1' => 'foo', 'item_1.5' => 'w00t', 'item_2' => 'bar' ];

		$new_array = ArrayHelper::insert_after( $initial_array, 'item_1', [ 'item_1.5' => 'w00t' ] );

		$this->assertEquals( $expected_array, $new_array );
	}

	public function test_unique_multidim_array() {
		$details = [
			[ 'id' => '1', 'name' => 'Mike', 'num' => '9876543210' ],
			[ 'id' => '2', 'name' => 'Carissa', 'num' => '08548596258' ],
			[ 'id' => '1', 'name' => 'Mathew', 'num' => '784581254' ],
		];

		$expected_array = [
			[ 'id' => '1', 'name' => 'Mike', 'num' => '9876543210' ],
			[ 'id' => '2', 'name' => 'Carissa', 'num' => '08548596258' ],
		];

		$new_array = ArrayHelper::unique_multidim_array( $details, 'id' );

		$this->assertEquals( $expected_array, $new_array );
	}

	public function test_array_diff_recursive() {
		$array1 = [
			'marcie' => [ 'banana' => 1, 'orange' => 1, 'pasta' => 1 ],
			'kenji'  => [ 'apple' => 1, 'pie' => 1, 'pasta' => 1 ],
		];

		$array2 = [
			'marcie' => [ 'banana' => 1, 'orange' => 1 ],
		];

		$expected_array = [
			'kenji'  => [ 'apple' => 1, 'pie' => 1, 'pasta' => 1 ],
			'marcie' => [ 'pasta' => 1 ],
		];


		$aDiff['marcie'] = array( 'pasta' => 1 );
		$aDiff['kenji']  = array( 'apple' => 1, 'pie' => 1, 'pasta' => 1 );

		$new_array = ArrayHelper::array_diff_recursive( $array1, $array2 );

		$this->assertEquals( $expected_array, $new_array );
	}
}