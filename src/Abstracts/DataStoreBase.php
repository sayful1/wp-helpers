<?php

namespace Stackonet\WP\Framework\Abstracts;

use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use Stackonet\WP\Framework\Supports\QueryBuilder;
use Stackonet\WP\Framework\Traits\Cacheable;
use Stackonet\WP\Framework\Traits\TableInfo;
use WP_Error;

/**
 * DataStoreBase class
 * Base class for data store to handle CRUD operations
 */
class DataStoreBase implements DataStoreInterface {
	use Cacheable, TableInfo;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * The type of the primary key
	 * '%s' for string and '%d' for integer
	 *
	 * @var string
	 */
	protected $primary_key_type = '%d';

	/**
	 * Column name for holding author id
	 *
	 * @var string
	 */
	protected $created_by = 'created_by';

	/**
	 * Column name for holding date time when creating record
	 *
	 * @var string
	 */
	protected $created_at = 'created_at';

	/**
	 * Column name for holding date time when updating record
	 *
	 * @var string
	 */
	protected $updated_at = 'updated_at';

	/**
	 * Column name for holding date time when soft deleting record
	 *
	 * @var string
	 */
	protected $deleted_at = 'deleted_at';

	/**
	 * Column name for holding record status
	 *
	 * @var string
	 */
	protected $status = 'status';

	/**
	 * The number of models to return for pagination.
	 *
	 * @var int
	 */
	protected $per_page = 20;

	/**
	 * Data Model linked to store
	 *
	 * @var null
	 */
	protected $model = null;

	/**
	 * Get instance of the store
	 *
	 * @param  string $table  The table name.
	 * @param  array  $props  The properties.
	 *
	 * @return DataStoreBase|DataStoreInterface
	 */
	public static function get_instance( string $table, array $props = [] ) {
		$class = new static();

		$class->table = $table;
		foreach ( $props as $prop_name => $prop_value ) {
			if ( property_exists( $class, $prop_name ) ) {
				$class->{$prop_name} = $prop_value;
			}
		}
		$class->primary_key      = static::get_primary_key( $class->get_table_name() );
		$class->primary_key_type = static::get_primary_key_data_format( $class->get_table_name() );

		return $class;
	}

	/**
	 * Method to create a new record
	 *
	 * @param  array $data  Array of data to be stored (optional).
	 *
	 * @return int The ID of the created record.
	 */
	public function create( array $data = [] ): int {
		global $wpdb;
		$table = $this->get_table_name();

		list( $sanitized_data, $format ) = $this->format_item_for_db( $data, static::get_default_data( $table ) );

		$wpdb->insert( $table, $sanitized_data, $format );

		// Update cache change.
		$this->set_cache_last_changed();

		return $wpdb->insert_id;
	}

	/**
	 * Method to read record.
	 *
	 * @param  int|array $data  Primary key value to get single record. or array of arguments to get multiple records.
	 *
	 * @return array|array[]
	 */
	public function read( $data ) {
		if ( is_numeric( $data ) ) {
			return $this->find_single( $data );
		}

		return $this->find_multiple( $data );
	}

	/**
	 * Update data
	 *
	 * @param  array $data  The data to be updated.
	 *
	 * @return bool
	 */
	public function update( array $data = [] ) {
		global $wpdb;
		$table        = $this->get_table_name();
		$id           = isset( $data[ $this->primary_key ] ) ? intval( $data[ $this->primary_key ] ) : 0;
		$current_time = current_time( 'mysql', true );

		$item = $this->find_single( $id );
		if ( empty( $item ) ) {
			return false;
		}

		// Database table columns.
		$columns_names = static::get_columns_names( $table );

		$_data = [];
		foreach ( $data as $column_name => $naw_value ) {
			if ( in_array( $column_name, $columns_names, true ) ) {
				$current_data = $item[ $column_name ] ?? null;
				$temp_data    = $naw_value ?? $current_data;
				if ( $temp_data !== $current_data ) {
					$_data[ $column_name ] = $this->serialize( $temp_data );
				}
			}
		}
		$_data[ $this->primary_key ] = $id;

		// Update updated time.
		if ( in_array( $this->updated_at, $columns_names, true ) ) {
			$_data[ $this->updated_at ] = $current_time;
		}

		// Update deleted time.
		if ( in_array( $this->deleted_at, $columns_names, true ) ) {
			$_data[ $this->deleted_at ] = null;
		}

		$data_format = static::get_data_format_for_db( $table, $_data );

		$is_updated = (bool) $wpdb->update(
			$table,
			$_data,
			[ $this->primary_key => $id ],
			$data_format,
			$this->primary_key_type
		);
		// Delete cache on update.
		if ( $is_updated ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return $is_updated;
	}

	/**
	 * Delete data
	 *
	 * @param  int $id  The id of the data to be deleted.
	 *
	 * @return bool
	 */
	public function delete( $id = 0 ): bool {
		global $wpdb;
		$table = $this->get_table_name();

		$query = $wpdb->delete( $table, [ $this->primary_key => $id ], $this->primary_key_type );

		// Delete cache.
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Send an item to trash
	 *
	 * @param  int $id  The id of the data to be trashed.
	 *
	 * @return bool
	 */
	public function trash( $id ): bool {
		global $wpdb;
		$table = $this->get_table_name();
		$query = $wpdb->update(
			$table,
			[ $this->deleted_at => current_time( 'mysql', true ) ],
			[ $this->primary_key => $id ]
		);

		// Delete cache.
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Restore an item from trash
	 *
	 * @param  int $id  The id of the data to be restored.
	 *
	 * @return bool
	 */
	public function restore( $id ): bool {
		global $wpdb;
		$table = $this->get_table_name();
		$query = $wpdb->update( $table, [ $this->deleted_at => null ], [ $this->primary_key => $id ] );

		// Delete cache.
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Perform batch action
	 *
	 * @param  string $action  Batch action. Example: 'create', 'update', 'delete', 'trash', 'restore'.
	 * @param  array  $data  The data for batch operation.
	 *
	 * @return mixed
	 */
	public function batch( string $action, array $data ) {
		$method = 'batch_' . $action;
		if ( method_exists( $this, $method ) ) {
			return $this->{$method}( $data );
		}

		return new WP_Error( 'no_batch_found', 'No batch action found.' );
	}

	/**
	 * Create multiple record
	 *
	 * @param  array $data  The data to be created.
	 *
	 * @return int[]
	 */
	public function batch_create( array $data ): array {
		global $wpdb;
		$table         = $this->get_table_name();
		$current_time  = current_time( 'mysql', true );
		$columns_names = static::get_columns_names( $table );
		$default       = static::get_default_data( $table );
		$primary_key   = static::get_primary_key( $table );

		$last_row    = $wpdb->get_row(
			"SELECT {$primary_key} FROM {$table} ORDER BY {$primary_key} DESC LIMIT 1;",
			ARRAY_A
		);
		$last_row_id = isset( $last_row[ $primary_key ] ) ? intval( $last_row[ $primary_key ] ) : 0;

		$values = [];
		foreach ( $data as $item ) {
			list( $_data, $_format ) = $this->format_item_for_db( $item, $default, $current_time );

			$sanitize_data = [];
			foreach ( $_data as $column_name => $column_value ) {
				if ( ! is_null( $column_value ) ) {
					$sanitize_data[ $column_name ] = $column_value;
				}
			}

			$values[] = $wpdb->prepare( '(' . implode( ', ', $_format ) . ')', $sanitize_data );
		}

		if ( in_array( $this->primary_key, $columns_names, true ) ) {
			$index = array_search( $this->primary_key, $columns_names, true );
			unset( $columns_names[ $index ] );
		}

		$sql   = "INSERT INTO `{$table}` (" . implode( ', ', $columns_names ) . ") VALUES \n" . implode(
			",\n",
			$values
		) . ';';
		$query = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Update cache change.
		$this->set_cache_last_changed();

		$ids = [];
		if ( $query ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$primary_key} FROM {$table} WHERE {$primary_key} > %s ORDER BY {$primary_key} ASC;",
					$last_row_id
				),
				ARRAY_A
			);
			$ids     = array_map( 'intval', wp_list_pluck( $results, $primary_key ) );
		}

		return $ids;
	}

	/**
	 * Update multiple record
	 *
	 * @param  array $data  The data to be updated.
	 *
	 * @return bool
	 */
	public function batch_update( array $data ): bool {
		$to_update = [];
		$ids       = [];
		foreach ( $data as $item ) {
			// Continue if primary key is not set.
			if ( ! isset( $item[ $this->primary_key ] ) ) {
				continue;
			}
			$to_update[] = $item;
			$ids[]       = $item[ $this->primary_key ];
		}
		$default       = $this->find_multiple(
			[
				$this->primary_key . '__in' => $ids,
				'per_page'                  => count( $ids ),
			]
		);
		$default_items = [];
		foreach ( $default as $item ) {
			$default_items[ $item[ $this->primary_key ] ] = $item;
		}

		global $wpdb;
		$table         = $this->get_table_name();
		$current_time  = current_time( 'mysql', true );
		$columns_names = static::get_columns_names( $table );

		$values = [];
		foreach ( $to_update as $index => $item ) {
			$default = $default_items[ $item[ $this->primary_key ] ] ?? [];
			// Continue if record is not found on database.
			if ( ! empty( $default ) ) {
				list( $_data, $_format ) = $this->format_item_for_db( $item, $default, $current_time );

				$sanitize_data = [];
				foreach ( $_data as $column_name => $column_value ) {
					if ( is_null( $column_value ) ) {
						continue;
					}
					$sanitize_data[ $column_name ] = $column_value;
				}
				$values[ $index ] = $wpdb->prepare( '(' . implode( ', ', $_format ) . ')', $sanitize_data );
			}
		}

		$update_columns = [];
		foreach ( $columns_names as $columns_name ) {
			if ( $columns_name !== $this->primary_key ) {
				$update_columns[] = "{$columns_name}=VALUES({$columns_name})";
			}
		}

		$sql  = "INSERT INTO `{$table}` (" . implode( ', ', $columns_names ) . ") VALUES \n" . implode( ",\n", $values );
		$sql .= "ON DUPLICATE KEY UPDATE \n" . implode( ', ', $update_columns );

		$query = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Delete cache.
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Delete multiple records
	 *
	 * @param  array $data  The ids of the data to be deleted.
	 *
	 * @return bool
	 */
	public function batch_delete( array $data ): bool {
		global $wpdb;
		$table = $this->get_table_name();
		$data  = array_map( 'absint', $data );
		$sql   = "DELETE FROM `{$table}` WHERE {$this->primary_key} IN(" . implode( ',', $data ) . ')';

		$query = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Delete cache.
		foreach ( $data as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Batch trash items
	 *
	 * @param  array $data  The batch data to be trashed.
	 *
	 * @return bool
	 */
	public function batch_trash( array $data ): bool {
		global $wpdb;
		$table = $this->get_table_name();
		$data  = array_map( 'absint', $data );

		$sql = $wpdb->prepare(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"UPDATE `{$table}` SET `{$this->deleted_at}` = %s",
			current_time( 'mysql', true )
		);
		$sql .= " WHERE {$this->primary_key} IN(" . implode( ',', $data ) . ')';

		$query = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Delete cache.
		foreach ( $data as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Batch restore items
	 *
	 * @param  array $data  The batch data to be restored.
	 *
	 * @return bool
	 */
	public function batch_restore( array $data ): bool {
		global $wpdb;
		$table = $this->get_table_name();
		$data  = array_map( 'absint', $data );

		$sql  = "UPDATE `{$table}` SET `{$this->deleted_at}` = NULL";
		$sql .= " WHERE {$this->primary_key} IN(" . implode( ',', $data ) . ')';

		$query = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Delete cache.
		foreach ( $data as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Find single item by primary key
	 *
	 * @param  int $id  The record primary id.
	 *
	 * @return false|array|Data
	 */
	public function find_single( $id ) {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_single_item( $id );
		$item      = $this->get_cache( $cache_key );
		if ( false === $item ) {
			$sql = "SELECT * FROM {$table} WHERE {$this->primary_key} = {$this->primary_key_type}";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$item = $wpdb->get_row( $wpdb->prepare( $sql, $id ), ARRAY_A );

			// prepare item for output.
			if ( is_array( $item ) ) {
				$item = $this->format_item_for_output( $item );
			}

			// Set cache.
			$this->set_cache( $cache_key, $item );
		}

		if ( is_array( $item ) || $item instanceof Data ) {
			return $item;
		}

		return false;
	}

	/**
	 * Find multiple records from database
	 *
	 * @param  array $args  {
	 * The arguments for query.
	 *
	 * @type int $page Current page number. Default: 1
	 * @type int $per_page Number of result to fetch per query.
	 * @type array $orders_by Array of [ ['field' => 'table_column', 'order' => 'ASC|DESC' ] ]
	 * @type string $status The status to fetch for the query.
	 * @type int $created_by The user id who created the record.
	 * @type int[] $id__in Array of record id.
	 * }
	 *
	 * @return array
	 */
	public function find_multiple( array $args = [] ): array {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_collection( $args );
		$items     = $this->get_cache( $cache_key );
		if ( false === $items ) {
			$columns                   = static::get_columns_names( $table );
			list( $per_page, $offset ) = $this->get_pagination_and_order_data( $args );
			$order_by                  = $this->get_order_by( $args );
			$status                    = $args[ $this->status ] ?? null;

			$query = "SELECT * FROM {$table} WHERE 1=1";

			if ( isset( $args[ $this->created_by ] ) && is_numeric( $args[ $this->created_by ] ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $args[ $this->created_by ] ) );
			}

			if ( isset( $args[ $this->primary_key . '__in' ] ) && is_array( $args[ $this->primary_key . '__in' ] ) ) {
				$ids__in = array_map( 'intval', $args[ $this->primary_key . '__in' ] );
				$query  .= " AND {$this->primary_key} IN(" . implode( ',', $ids__in ) . ')';
			}

			if ( in_array( $this->deleted_at, $columns, true ) ) {
				if ( 'trash' === $status ) {
					$query .= " AND {$this->deleted_at} IS NOT NULL";
				} else {
					$query .= " AND {$this->deleted_at} IS NULL";
				}
			}

			if ( in_array( $this->status, $columns, true ) && ! empty( $status ) && 'trash' !== $status ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query .= $wpdb->prepare( " AND {$this->status} = %s", $status );
			}

			$query .= " ORDER BY {$order_by}";
			if ( $per_page > 0 ) {
				$query .= $wpdb->prepare( ' LIMIT %d', $per_page );
			}
			if ( $offset >= 0 ) {
				$query .= $wpdb->prepare( ' OFFSET %d', $offset );
			}
			$items = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( count( $items ) ) {
				$items = array_map( [ $this, 'format_item_for_output' ], $items );
			}

			// Set cache for one day.
			$this->set_cache( $cache_key, $items, DAY_IN_SECONDS );
		}

		return $items;
	}

	/**
	 * Count record from database
	 *
	 * @param  array $args  The optional arguments.
	 *
	 * @return array {
	 * Number of found records for each group.
	 *
	 * @type int $all The total number of records except trashed.
	 * @type int $trash The total number of trashed records.
	 * }
	 */
	public function count_records( array $args = [] ): array {
		$cache_key = $this->get_cache_key_for_count_records( $args );
		$counts    = $this->get_cache( $cache_key );

		if ( false === $counts ) {
			global $wpdb;
			$table   = $this->get_table_name();
			$columns = static::get_columns_names( $table );

			$counts = [];
			$sql    = "SELECT COUNT(*) AS total_records FROM {$table} WHERE 1 = 1";

			if ( in_array( $this->deleted_at, $columns, true ) ) {
				$sql .= " AND {$this->deleted_at} IS NULL";
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$row = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT( * ) AS total_trash FROM {$table} WHERE {$this->deleted_at} IS NOT NULL",
					ARRAY_A
				);
				$counts['trash'] = intval( $row['total_trash'] );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$row           = $wpdb->get_row( $sql, ARRAY_A );
			$counts['all'] = isset( $row['total_records'] ) ? intval( $row['total_records'] ) : 0;

			if ( in_array( $this->status, $columns, true ) ) {
				$query_status = "SELECT {$this->status}, COUNT( * ) AS num_rows FROM {$table} WHERE 1 = 1";
				if ( in_array( $this->deleted_at, $columns, true ) ) {
					$query_status .= " AND {$this->deleted_at} IS NULL";
				}
				$query_status .= " GROUP BY {$this->status}";

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$results = (array) $wpdb->get_results( $query_status, ARRAY_A );

				foreach ( $results as $row ) {
					$counts[ $row[ $this->status ] ] = $row['num_rows'];
				}
			}

			// Set cache for one day.
			$this->set_cache( $cache_key, $counts, DAY_IN_SECONDS );
		}

		return $counts;
	}

	/**
	 * Get table name
	 *
	 * @param  string|null $table  Table name.
	 *
	 * @return string
	 */
	public function get_table_name( ?string $table = null ): ?string {
		if ( empty( $table ) ) {
			$table = $this->table;
		}
		global $wpdb;

		return false !== strpos( $table, $wpdb->prefix ) ? $table : $wpdb->prefix . $table;
	}

	/**
	 * Get foreign key constant name
	 *
	 * @param  string $table1  One table name.
	 * @param  string $table2  Another table name.
	 *
	 * @return string
	 */
	public function get_foreign_key_constant_name( string $table1, string $table2 ): string {
		global $wpdb;
		$tables = [
			str_replace( $wpdb->prefix, '', $table1 ),
			str_replace( $wpdb->prefix, '', $table2 ),
		];
		asort( $tables );

		return substr( sprintf( 'fk_%s_%s', $tables[0], $tables[1] ), 0, 64 );
	}

	/**
	 * Serialize array and object data
	 *
	 * @param  mixed $data  The data to be serialized.
	 *
	 * @return string|int|float|bool|null
	 */
	public function serialize( $data ) {
		return maybe_serialize( $data );
	}

	/**
	 * Un-serialize value only if it was serialized.
	 *
	 * @param  mixed $data  Maybe un-serialized original, if is needed.
	 *
	 * @return mixed Un-serialized data can be any type.
	 */
	public function unserialize( $data ) {
		return maybe_unserialize( $data );
	}

	/**
	 * Format data read from database
	 *
	 * @param  array $raw_data  Non formatted data.
	 *
	 * @return array|Data
	 */
	public function format_item_for_output( array $raw_data ) {
		$table_name = $this->get_table_name();
		$defaults   = static::get_default_data( $table_name );
		$data       = [];
		foreach ( $defaults as $column_name => $default_value ) {
			$temp_data            = $raw_data[ $column_name ] ?? $default_value;
			$data[ $column_name ] = $this->unserialize( $temp_data );
		}

		$final_data = static::format_data_by_type( $table_name, $data );

		if ( ! empty( $this->model ) ) {
			$model = new $this->model();
			if ( $model instanceof Data ) {
				$model->set_props( $final_data );
				$model->set_object_read();
			}

			return $model;
		}

		return $final_data;
	}

	/**
	 * Format item for database
	 *
	 * @param  array       $data  User provided data.
	 * @param  array       $defaults  Default data. Previous data for existing record.
	 * @param  string|null $current_time  Current datetime.
	 *
	 * @return array
	 */
	public function format_item_for_db( array $data, array $defaults, ?string $current_time = null ): array {
		if ( empty( $current_time ) ) {
			$current_time = current_time( 'mysql', true );
		}

		$mode = ! empty( $data[ $this->primary_key ] ) ? 'update' : 'create';

		$sanitized_data = [];
		foreach ( $defaults as $key => $value ) {
			$temp_data              = $data[ $key ] ?? $value;
			$sanitized_data[ $key ] = $this->serialize( $temp_data );
		}

		// Update updated time.
		if ( array_key_exists( $this->updated_at, $defaults ) ) {
			$sanitized_data[ $this->updated_at ] = $current_time;
		}

		if ( 'create' === $mode ) {
			// Update Author ID.
			if ( array_key_exists( $this->created_by, $defaults ) && ! isset( $data[ $this->created_by ] ) ) {
				$sanitized_data[ $this->created_by ] = get_current_user_id();
			}

			// Update created time.
			if ( array_key_exists( $this->created_at, $defaults ) ) {
				$sanitized_data[ $this->created_at ] = $current_time;
			}

			// Set deleted at time as null.
			if ( array_key_exists( $this->deleted_at, $defaults ) ) {
				$sanitized_data[ $this->deleted_at ] = null;
			}

			// Remove primary key.
			if ( array_key_exists( $this->primary_key, $sanitized_data ) ) {
				unset( $sanitized_data[ $this->primary_key ] );
			}
		}

		$format = static::get_data_format_for_db( $this->get_table_name(), $sanitized_data );

		return [ $sanitized_data, $format ];
	}

	/**
	 * Get pagination and order data
	 *
	 * @param  array $args  Query args.
	 *
	 * @return array
	 */
	public function get_pagination_and_order_data( array $args ): array {
		$paged        = isset( $args['paged'] ) ? absint( $args['paged'] ) : 1;
		$current_page = isset( $args['page'] ) ? absint( $args['page'] ) : $paged;

		$per_page = isset( $args['per_page'] ) ? intval( $args['per_page'] ) : $this->per_page;
		$offset   = $this->calculate_offset( $current_page, $per_page );

		$columns_names = static::get_columns_names( $this->get_table_name() );
		$order         = isset( $args['order'] ) && 'ASC' === $args['order'] ? 'ASC' : 'DESC';
		$orderby       = isset( $args['orderby'] ) && in_array( $args['orderby'], $columns_names, true )
			? $args['orderby'] : $this->primary_key;

		return [ $per_page, $offset, $orderby, $order ];
	}

	/**
	 * Calculate offset
	 *
	 * @param  int $current_page  Current page.
	 * @param  int $per_page  Per page.
	 *
	 * @return int
	 */
	public function calculate_offset( int $current_page = 1, int $per_page = 0 ) {
		$page = max( 1, $current_page );

		return (int) ( $page - 1 ) * ( $per_page > 0 ? $per_page : $this->per_page );
	}

	/**
	 * Get order_by data
	 *
	 * @param  array $args  Query args.
	 *
	 * @return string
	 */
	public function get_order_by( array $args ): string {
		$columns_names = static::get_columns_names( $this->get_table_name() );
		$orders_by     = $args['order_by'] ?? [];
		$orders_by     = is_string( $orders_by ) ? explode( ',', $orders_by ) : $orders_by;
		$valid_orders  = [ 'ASC', 'DESC' ];

		if ( count( $orders_by ) < 1 ) {
			// For backward compatibility.
			$column_name = isset( $args['orderby'] ) && in_array( $args['orderby'], $columns_names, true ) ?
				$args['orderby'] : $this->primary_key;
			$order       = isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
			$orders_by[] = $column_name . ' ' . $order;
		}

		$final_order_by = [];
		foreach ( $orders_by as $order_by ) {
			if ( is_array( $order_by ) && isset( $order_by['field'], $order_by['order'] ) ) {
				$column_name = $order_by['field'];
				$order       = $order_by['order'];
			} else {
				$_order      = explode( ' ', trim( $order_by ) );
				$column_name = ( isset( $_order[0] ) && in_array(
					$_order[0],
					$columns_names,
					true
				) ) ? $_order[0] : '';
				$order       = ( isset( $_order[1] ) && in_array(
					strtoupper( $_order[1] ),
					$valid_orders,
					true
				) ) ? $_order[1] : '';
			}
			if ( $column_name || $order ) {
				$final_order_by[] = $column_name . ' ' . $order;
			}
		}

		return implode( ', ', $final_order_by );
	}

	/**
	 * Get query builder
	 *
	 * @return QueryBuilder
	 */
	public function get_query_builder(): QueryBuilder {
		return QueryBuilder::table( $this->get_table_name() );
	}
}
