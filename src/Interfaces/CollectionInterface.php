<?php

namespace Stackonet\WP\Framework\Interfaces;

use ArrayAccess;
use Countable;
use IteratorAggregate;

defined( 'ABSPATH' ) || exit;

/**
 * Interface CollectionInterface
 *
 * @package Stackonet\WP\Framework\Interfaces
 */
interface CollectionInterface extends ArrayAccess, Countable, IteratorAggregate {

	/**
	 * Does this collection have a given key?
	 *
	 * @param string $key The data key
	 *
	 * @return bool
	 */
	public function has( string $key ): bool;

	/**
	 * Set collection item
	 *
	 * @param string $key The data key
	 * @param mixed  $value The data value
	 */
	public function set( string $key, $value );

	/**
	 * Get collection item for key
	 *
	 * @param string $key The data key
	 * @param mixed  $default The default value to return if data key does not exist
	 *
	 * @return mixed The key's value, or the default value
	 */
	public function get( string $key, $default = null );

	/**
	 * Add item to collection, replacing existing items with the same data key
	 *
	 * @param array $items Key-value array of data to append to this collection
	 */
	public function replace( array $items );

	/**
	 * Get all items in collection
	 *
	 * @return array The collection's source data
	 */
	public function all(): array;

	/**
	 * Remove item from collection
	 *
	 * @param string $key The data key
	 */
	public function remove( string $key );

	/**
	 * Remove all items from collection
	 */
	public function clear();
}
