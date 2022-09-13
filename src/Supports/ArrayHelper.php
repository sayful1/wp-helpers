<?php

namespace Stackonet\WP\Framework\Supports;

defined( 'ABSPATH' ) || exit;

/**
 * Class ArrayHelper
 *
 * @package Stackonet\WP\Framework\Supports
 */
class ArrayHelper {
	/**
	 * Insert the given element after the given key in the array
	 *
	 * Sample usage:
	 *
	 * given:
	 * array( 'item_1' => 'foo', 'item_2' => 'bar' )
	 *
	 * array_insert_after( $array, 'item_1', array( 'item_1.5' => 'w00t' ) )
	 *
	 * becomes:
	 * array( 'item_1' => 'foo', 'item_1.5' => 'w00t', 'item_2' => 'bar' )
	 *
	 * @param array      $array array to insert the given element into.
	 * @param string|int $insert_key key to insert given element after.
	 * @param array      $element element to insert into array.
	 *
	 * @return array
	 */
	public static function insert_after( array $array, $insert_key, array $element ): array {
		$new_array = [];

		foreach ( $array as $key => $value ) {

			$new_array[ $key ] = $value;
			if ( $insert_key === $key ) {

				foreach ( $element as $k => $v ) {
					$new_array[ $k ] = $v;
				}
			}
		}

		return $new_array;
	}

	/**
	 * Create multidimensional array unique for any single key index.
	 *
	 * Sample usage:
	 *
	 * given:
	 *
	 * $details = array(
	 *      array("id"=>"1", "name"=>"Mike",    "num"=>"9876543210"),
	 *      array("id"=>"2", "name"=>"Carissa", "num"=>"08548596258"),
	 *      array("id"=>"1", "name"=>"Mathew",  "num"=>"784581254"),
	 * )
	 *
	 * ArrayHelper::unique_multidim_array( $details, 'id' )
	 *
	 * becomes:
	 * array(
	 *      array("id"=>"1","name"=>"Mike","num"=>"9876543210"),
	 *      array("id"=>"2","name"=>"Carissa","num"=>"08548596258"),
	 * )
	 *
	 * @param array  $array array to make unique.
	 * @param string $key key to make unique.
	 *
	 * @return array
	 */
	public static function unique_multidim_array( array $array, string $key ): array {
		$temp_array = [];
		$i          = 0;
		$key_array  = [];

		foreach ( $array as $val ) {
			if ( ! in_array( $val[ $key ], $key_array, true ) ) {
				$key_array[ $i ]  = $val[ $key ];
				$temp_array[ $i ] = $val;
			}
			$i ++;
		}

		return $temp_array;
	}

	/**
	 * Computes the difference of arrays
	 *
	 * Sample usage:
	 *
	 * given:
	 * $array1 = [
	 *      'marcie' => [ 'banana' => 1, 'orange' => 1, 'pasta' => 1 ],
	 *      'kenji'  => [ 'apple' => 1, 'pie' => 1, 'pasta' => 1 ],
	 * ];
	 *
	 * $array2 = [
	 *      'marcie' => [ 'banana' => 1, 'orange' => 1 ],
	 * ];
	 *
	 * becomes:
	 * [
	 *      'kenji'  => [ 'apple' => 1, 'pie' => 1, 'pasta' => 1 ],
	 *      'marcie' => [ 'pasta' => 1 ],
	 * ];
	 *
	 * @param array $array1 The array to compare from.
	 * @param array $array2 An array to compare against.
	 *
	 * @return array
	 */
	public static function array_diff_recursive( array $array1, array $array2 ): array {
		$to_return = [];

		foreach ( $array1 as $m_key => $m_value ) {
			if ( array_key_exists( $m_key, $array2 ) ) {
				if ( is_array( $m_value ) ) {
					$array_diff_recursive = static::array_diff_recursive( $m_value, $array2[ $m_key ] );
					if ( count( $array_diff_recursive ) ) {
						$to_return[ $m_key ] = $array_diff_recursive;
					}
				} else {
					if ( $m_value !== $array2[ $m_key ] ) {
						$to_return[ $m_key ] = $m_value;
					}
				}
			} else {
				$to_return[ $m_key ] = $m_value;
			}
		}

		return $to_return;
	}
}
