<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayAccess;
use JsonSerializable;

defined( 'ABSPATH' ) || exit;

/**
 * Class Data
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
class Data implements ArrayAccess, JsonSerializable {

	/**
	 * ID for this object.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Core data for this object. Name value pairs (name + default value).
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Core data changes for this object.
	 *
	 * @var array
	 */
	protected $changes = [];

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @var object
	 */
	protected $data_store;

	/**
	 * This is false until the object is read from the DB.
	 *
	 * @var bool
	 */
	protected $object_read = false;

	/**
	 * Data constructor.
	 *
	 * @param mixed $data The data to be read.
	 */
	public function __construct( $data = [] ) {
		if ( is_array( $data ) && count( $data ) > 0 ) {
			$this->set_props( $data );
		}
	}

	/**
	 * Returns the unique ID for this object.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Set ID.
	 *
	 * @param int|string $id ID.
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * String representation of the class
	 *
	 * @return string
	 */
	public function __toString() {
		return wp_json_encode( $this->to_array() );
	}

	/**
	 * Get collection item for key
	 *
	 * @param string $name The data key.
	 *
	 * @return mixed
	 */
	public function __get( string $name ) {
		return $this->get_prop( $name );
	}

	/**
	 * Does this collection have a given key?
	 *
	 * @param string $name The data key.
	 *
	 * @return bool
	 */
	public function __isset( string $name ) {
		return $this->has_prop( $name );
	}

	/**
	 * Array representation of the class
	 *
	 * @return array
	 */
	public function to_array(): array {
		return $this->get_data();
	}

	/**
	 * Does this collection have a given key?
	 *
	 * @param string $prop The data key.
	 *
	 * @return bool
	 */
	public function has_prop( string $prop ): bool {
		return array_key_exists( $prop, $this->data ) || array_key_exists( $prop, $this->changes );
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * This stores changes in a special array, so we can track what needs saving the DB later.
	 *
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	public function set_prop( string $prop, $value ) {
		if ( true === $this->object_read ) {
			if ( $value !== $this->get_prop( $prop ) ) {
				$this->changes[ $prop ] = $value;
			}
		} else {
			$this->data[ $prop ] = $value;
		}
	}

	/**
	 * Get collection item for key
	 *
	 * @param string $prop The data key.
	 * @param mixed  $default The default value to return if data key does not exist.
	 *
	 * @return mixed The key's value, or the default value
	 */
	public function get_prop( string $prop, $default = '' ) {
		if ( $this->has_prop( $prop ) ) {
			return array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : $this->data[ $prop ];
		}

		return $default;
	}

	/**
	 * Remove item from collection
	 *
	 * @param string $prop The data key.
	 */
	public function remove_prop( string $prop ) {
		if ( array_key_exists( $prop, $this->changes ) ) {
			unset( $this->changes[ $prop ] );
		}
		if ( array_key_exists( $prop, $this->data ) ) {
			unset( $this->data[ $prop ] );
		}
	}

	/**
	 * Set a collection of props in one go.
	 *
	 * @param array $props Key value pairs to set.
	 */
	public function set_props( array $props ) {
		foreach ( $props as $prop => $value ) {
			$setter = "set_$prop";
			if ( is_callable( [ $this, $setter ] ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_prop( $prop, $value );
			}
		}
	}

	/**
	 * Return data changes only.
	 *
	 * @return array
	 */
	public function get_changes(): array {
		return $this->changes;
	}

	/**
	 * Merge changes with data and clear.
	 */
	public function apply_changes() {
		$this->data    = array_replace_recursive( $this->data, $this->changes );
		$this->changes = [];
	}

	/**
	 * Set object read property.
	 *
	 * @param boolean $read Should read?.
	 */
	public function set_object_read( bool $read = true ) {
		$this->object_read = $read;
	}

	/**
	 * Returns all data for this object.
	 *
	 * @return array
	 */
	public function get_data(): array {
		return array_merge( [ 'id' => $this->get_id() ], $this->data );
	}

	/**
	 * Whether an offset exists
	 *
	 * @param mixed $offset An offset to check for.
	 *
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists( $offset ): bool {
		return $this->has_prop( $offset );
	}

	/**
	 * Offset to retrieve
	 *
	 * @param mixed $offset The offset to retrieve.
	 *
	 * @return mixed Can return all value types.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->get_prop( $offset );
	}

	/**
	 * Offset to set
	 *
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->set_prop( $offset, $value );
	}

	/**
	 * Offset to unset
	 *
	 * @param mixed $offset The offset to unset.
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->remove_prop( $offset );
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @return array data which can be serialized by json_encode
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/********************************************************************************
	 * Deprecated Methods
	 *******************************************************************************/

	/**
	 * Does this collection have a given key?
	 *
	 * @param string $key The data key.
	 *
	 * @return bool
	 *
	 * @deprecated 1.7.0
	 * @see \Stackonet\WP\Framework\Abstracts\Data::has_prop()
	 */
	public function has( string $key ): bool {
		return $this->has_prop( $key );
	}

	/**
	 * Set collection item
	 *
	 * @param string $key The data key.
	 * @param mixed  $value The data value.
	 *
	 * @deprecated 1.7.0
	 * @see \Stackonet\WP\Framework\Abstracts\Data::set_prop()
	 */
	public function set( string $key, $value ) {
		$this->set_prop( $key, $value );
	}

	/**
	 * Get collection item for key
	 *
	 * @param string $prop The data key.
	 * @param mixed  $default The default value to return if data key does not exist.
	 *
	 * @return mixed The key's value, or the default value
	 *
	 * @deprecated 1.7.0
	 * @see \Stackonet\WP\Framework\Abstracts\Data::get_prop()
	 */
	public function get( string $prop, $default = '' ) {
		return $this->get_prop( $prop, $default );
	}

	/**
	 * Remove item from collection
	 *
	 * @param string $key The data key.
	 *
	 * @deprecated 1.7.0
	 * @see \Stackonet\WP\Framework\Abstracts\Data::remove_prop()
	 */
	public function remove( string $key ) {
		$this->remove_prop( $key );
	}

	/**
	 * Set data
	 *
	 * @param array $props The data to be set.
	 *
	 * @deprecated 1.7.0
	 * @see \Stackonet\WP\Framework\Abstracts\Data::set_props()
	 */
	public function set_data( array $props ) {
		$this->set_props( $props );
	}
}
