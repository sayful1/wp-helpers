<?php

namespace Stackonet\WP\Framework\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface DataStoreInterface
 *
 * @package Stackonet\WP\Framework\Interfaces
 */
interface DataStoreInterface {

	/**
	 * Method to create a new record
	 *
	 * @param array $data Array of data to be stored (optional).
	 *
	 * @return mixed
	 */
	public function create( array $data = [] );

	/**
	 * Method to read a record.
	 *
	 * @param mixed $data The data to read.
	 *
	 * @return mixed
	 */
	public function read( $data );

	/**
	 * Updates a record in the database.
	 *
	 * @param array $data Array of data to be updated (optional).
	 *
	 * @return mixed
	 */
	public function update( array $data = [] );

	/**
	 * Deletes a record from the database.
	 *
	 * @param mixed $data Array of data to be stored (optional).
	 *
	 * @return mixed
	 */
	public function delete( $data = null );

	/**
	 * Perform batch action
	 *
	 * @param string $action Batch action. Example: 'create', 'update', 'delete', 'trash', 'restore'.
	 * @param array  $data The data for batch operation.
	 *
	 * @return mixed
	 */
	public function batch( string $action, array $data );

	/**
	 * Batch create items
	 *
	 * @param array $data The batch data to be created.
	 *
	 * @return mixed
	 */
	public function batch_create( array $data );

	/**
	 * Batch update items
	 *
	 * @param array $data The batch data to be updated.
	 *
	 * @return mixed
	 */
	public function batch_update( array $data );

	/**
	 * Batch delete items
	 *
	 * @param array $data The batch data to be deleted.
	 *
	 * @return mixed
	 */
	public function batch_delete( array $data );

	/**
	 * Batch trash items
	 *
	 * @param array $data The batch data to be trashed.
	 *
	 * @return mixed
	 */
	public function batch_trash( array $data );

	/**
	 * Batch restore items
	 *
	 * @param array $data The batch data to be restored.
	 *
	 * @return mixed
	 */
	public function batch_restore( array $data );

	/**
	 * Find multiple items
	 *
	 * @param array $args The optional arguments.
	 *
	 * @return array|mixed
	 */
	public function find_multiple( array $args = [] );

	/**
	 * Find single item by primary key
	 *
	 * @param int|string $data The record primary id.
	 *
	 * @return mixed
	 */
	public function find_single( $data );

	/**
	 * Count record from database
	 *
	 * @param array $args The optional arguments.
	 *
	 * @return int|array Number of found records
	 */
	public function count_records( array $args = [] );
}
