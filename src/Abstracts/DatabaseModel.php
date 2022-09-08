<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayObject;
use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use Stackonet\WP\Framework\Supports\QueryBuilder;
use Stackonet\WP\Framework\Traits\Cacheable;
use Stackonet\WP\Framework\Traits\TableInfo;

defined( 'ABSPATH' ) || exit;

/**
 * Class DatabaseModel
 * A thin layer using wpdb database class form rapid development
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
abstract class DatabaseModel extends Data implements DataStoreInterface {

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
	 * Column name for holding date time when updating record
	 *
	 * @var string
	 */
	protected $deleted_at = 'deleted_at';

	/**
	 * The number of models to return for pagination.
	 *
	 * @var int
	 */
	protected $per_page = 20;

	/**
	 * Model constructor.
	 *
	 * @param mixed $data The data to be read.
	 */
	public function __construct( $data = [] ) {
		if ( $data ) {
			$this->data = $this->read( $data );
		}
		$this->primary_key      = static::get_primary_key( $this->get_table_name() );
		$this->primary_key_type = static::get_primary_key_data_format( $this->get_table_name() );
	}

	/**
	 * Find multiple records from database
	 *
	 * @param array $args
	 *
	 * @return array|static[]
	 */
	public function find_multiple( $args = [] ) {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_collection( $args );
		$items     = $this->get_cache( $cache_key );
		if ( false === $items ) {
			list( $per_page, $offset ) = $this->get_pagination_and_order_data( $args );
			$order_by = $this->get_order_by( $args );
			$status   = $args['status'] ?? null;

			$query = "SELECT * FROM {$table} WHERE 1=1";

			if ( isset( $args[ $this->created_by ] ) && is_numeric( $args[ $this->created_by ] ) ) {
				$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $args[ $this->created_by ] ) );
			}

			if ( isset( $args[ $this->primary_key . '__in' ] ) && is_array( $args[ $this->primary_key . '__in' ] ) ) {
				if ( $this->primary_key_type == '%d' ) {
					$ids__in = array_map( 'intval', $args[ $this->primary_key . '__in' ] );
					$query   .= " AND {$this->primary_key} IN(" . implode( ',', $ids__in ) . ')';
				} else {
					$ids__in = array_map( 'esc_sql', $args[ $this->primary_key . '__in' ] );
					$query   .= " AND {$this->primary_key} IN('" . implode( "', '", $ids__in ) . "')";
				}
			}

			if ( in_array( $this->deleted_at, static::get_columns_names( $table ) ) ) {
				if ( 'trash' == $status ) {
					$query .= " AND {$this->deleted_at} IS NOT NULL";
				} else {
					$query .= " AND {$this->deleted_at} IS NULL";
				}
			}

			$query .= " ORDER BY {$order_by}";
			if ( $per_page > 0 ) {
				$query .= $wpdb->prepare( ' LIMIT %d', $per_page );
			}
			if ( $offset >= 0 ) {
				$query .= $wpdb->prepare( ' OFFSET %d', $offset );
			}
			$items = $wpdb->get_results( $query, ARRAY_A );

			// Set cache for one day
			$this->set_cache( $cache_key, $items, DAY_IN_SECONDS );
		}

		$data = [];
		foreach ( $items as $item ) {
			$data[] = new static( $item );
		}

		return $data;
	}

	/**
	 * Find record by id
	 *
	 * @param int|string $id
	 *
	 * @return ArrayObject|static
	 */
	public function find_single( $id ) {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_single_item( $id );
		$item      = $this->get_cache( $cache_key );
		if ( false === $item ) {
			$sql  = "SELECT * FROM {$table} WHERE {$this->primary_key} = {$this->primary_key_type}";
			$item = $wpdb->get_row( $wpdb->prepare( $sql, $id ), ARRAY_A );

			// Set cache.
			$this->set_cache( $cache_key, $item );
		}

		return $item ? new static( $item ) : new ArrayObject();
	}

	/**
	 * Create data
	 *
	 * @param array $data The data to be created.
	 *
	 * @return int
	 */
	public function create( array $data = [] ) {
		if ( empty( $data ) ) {
			$data = $this->data;
		}
		global $wpdb;
		$table = $this->get_table_name();

		list( $_data, $_format ) = $this->format_item_for_db( $data, static::get_default_data( $table ) );

		$wpdb->insert( $table, $_data, $_format );

		// Update cache change.
		$this->set_cache_last_changed();

		return $wpdb->insert_id;
	}

	/**
	 * Create multiple record
	 *
	 * @param array $data The data to be created.
	 *
	 * @return int[]
	 */
	public function batch_create( array $data ) {
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
		foreach ( $data as $index => $item ) {
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

		$sql   = "INSERT INTO `{$table}` (" . implode( ', ', $columns_names ) . ") VALUES \n" . implode( ",\n", $values ) . ';';
		$query = $wpdb->query( $sql );

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
	 * Update data
	 *
	 * @param array $data The data to be updated.
	 *
	 * @return bool
	 */
	public function update( array $data = [] ) {
		if ( empty( $data ) ) {
			$data = $this->data;
		}
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
				$current_data = isset( $item[ $column_name ] ) ? $item[ $column_name ] : null;
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

		if ( $wpdb->update( $table, $_data, [ $this->primary_key => $id ], $data_format, $this->primary_key_type ) ) {
			// Delete cache.
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

			return true;
		}

		return false;
	}

	/**
	 * Update multiple record
	 *
	 * @param array $data The data to be updated.
	 *
	 * @return bool
	 */
	public function batch_update( array $data ) {
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
				$default = $default instanceof Data ? $default->data : $default;
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

		$sql = "INSERT INTO `{$table}` (" . implode( ', ', $columns_names ) . ") VALUES \n" . implode( ",\n", $values );
		$sql .= "ON DUPLICATE KEY UPDATE \n" . implode( ', ', $update_columns );

		$query = $wpdb->query( $sql );

		// Delete cache.
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Delete data
	 *
	 * @param int $id The id of the data to be deleted.
	 *
	 * @return bool
	 */
	public function delete( $id = 0 ) {
		global $wpdb;
		$table = $this->get_table_name();

		$query = $wpdb->delete( $table, [ $this->primary_key => $id ], $this->primary_key_type );

		// Delete cache.
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Delete multiple records
	 *
	 * @param array $ids The ids of the data to be deleted.
	 *
	 * @return bool
	 */
	public function batch_delete( array $ids = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$ids   = array_map( 'absint', $ids );
		$sql   = "DELETE FROM `{$table}` WHERE {$this->primary_key} IN(" . implode( ',', $ids ) . ')';

		$query = $wpdb->query( $sql );

		// Delete cache
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Send an item to trash
	 *
	 * @param int $id The id of the data to be trashed.
	 *
	 * @return bool
	 */
	public function trash( $id ) {
		global $wpdb;
		$table = $this->get_table_name();
		$query = $wpdb->update(
			$table,
			[ $this->deleted_at => current_time( 'mysql', true ) ],
			[ $this->primary_key => $id ]
		);

		// Delete cache
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Trash multiple records
	 *
	 * @param array $ids The ids of the data to be trashed.
	 *
	 * @return bool
	 */
	public function batch_trash( array $ids = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$ids   = array_map( 'absint', $ids );
		$sql   = $wpdb->prepare( "UPDATE `{$table}` SET `{$this->deleted_at}` = %s", current_time( 'mysql', true ) );
		$sql   .= " WHERE {$this->primary_key} IN(" . implode( ',', $ids ) . ')';

		$query = $wpdb->query( $sql );

		// Delete cache
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Restore an item from trash
	 *
	 * @param int $id The id of the data to be restored.
	 *
	 * @return bool
	 */
	public function restore( $id ) {
		global $wpdb;
		$table = $this->get_table_name();
		$query = $wpdb->update( $table, [ $this->deleted_at => null ], [ $this->primary_key => $id ] );

		// Delete cache
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Restore multiple records
	 *
	 * @param array $ids The ids of the data to be restored.
	 *
	 * @return bool
	 */
	public function batch_restore( array $ids = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$ids   = array_map( 'absint', $ids );
		$sql   = "UPDATE `{$table}` SET `{$this->deleted_at}` = NULL";
		$sql   .= " WHERE {$this->primary_key} IN(" . implode( ',', $ids ) . ')';

		$query = $wpdb->query( $sql );

		// Delete cache
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Method to read a record.
	 *
	 * @param mixed $data The data to be read.
	 *
	 * @return array
	 */
	public function read( $data ) {
		if ( $data instanceof Data ) {
			return $data->data;
		}

		if ( is_numeric( $data ) ) {
			$item = $this->find_single( $data );
			if ( $item instanceof Data ) {
				return $item->data;
			}

			if ( is_array( $item ) ) {
				$data = $item;
			}
		}

		$table_name = $this->get_table_name();
		$default    = static::get_default_data( $table_name );

		if ( is_array( $data ) ) {
			$item = [];
			foreach ( $default as $column_name => $default_value ) {
				$temp_data            = isset( $data[ $column_name ] ) ? $data[ $column_name ] : $default_value;
				$item[ $column_name ] = $this->unserialize( $temp_data );
			}

			return static::format_data_by_type( $table_name, $item );
		}

		return $default;
	}

	/**
	 * Serialize array and object data
	 *
	 * @param mixed $data The data to be serialized.
	 *
	 * @return string
	 */
	protected function serialize( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			return serialize( $data );
		}

		return $data;
	}

	/**
	 * Unserialize value only if it was serialized.
	 *
	 * @param mixed $data Maybe unserialized original, if is needed.
	 *
	 * @return mixed Unserialized data can be any type.
	 */
	protected function unserialize( $data ) {
		if ( is_serialized( $data ) ) {
			return @unserialize( $data );
		}

		return $data;
	}

	/**
	 * Get pagination and order data
	 *
	 * @param array $args Query args.
	 *
	 * @return array
	 */
	protected function get_pagination_and_order_data( array $args ) {
		$paged        = isset( $args['paged'] ) ? absint( $args['paged'] ) : 1;
		$current_page = isset( $args['page'] ) ? absint( $args['page'] ) : $paged;

		$per_page = isset( $args['per_page'] ) ? intval( $args['per_page'] ) : $this->per_page;
		$offset   = $this->calculate_offset( $current_page, $per_page );

		$orderby = isset( $args['orderby'] ) && in_array( $args['orderby'], static::get_columns_names( $this->get_table_name() ) )
			? $args['orderby'] : $this->primary_key;
		$order   = isset( $args['order'] ) && 'ASC' === $args['order'] ? 'ASC' : 'DESC';

		return array( $per_page, $offset, $orderby, $order );
	}

	/**
	 * Calculate offset
	 *
	 * @param int $current_page Current page.
	 * @param int $per_page Per page.
	 *
	 * @return int
	 */
	protected function calculate_offset( $current_page = 1, $per_page = 0 ) {
		if ( empty( $per_page ) ) {
			$per_page = $this->per_page;
		}

		$page = max( 1, $current_page );

		return (int) ( $page - 1 ) * $per_page;
	}

	/**
	 * Get order_by data
	 *
	 * @param array $args Query args.
	 *
	 * @return string
	 */
	protected function get_order_by( array $args ): string {
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
			$_order      = explode( ' ', trim( $order_by ) );
			$column_name = ( isset( $_order[0] ) && in_array( $_order[0], $columns_names, true ) ) ? $_order[0] : '';
			$order       = ( isset( $_order[1] ) && in_array( strtoupper( $_order[1] ), $valid_orders, true ) ) ? $_order[1] : '';

			if ( $column_name || $order ) {
				$final_order_by[] = $column_name . ' ' . $order;
			}
		}

		return implode( ', ', $final_order_by );
	}

	/**
	 * Get table name
	 *
	 * @param string|null $table Table name.
	 *
	 * @return string
	 */
	public function get_table_name( ?string $table = null ): ?string {
		if ( empty( $table ) ) {
			$table = $this->table;
		}
		global $wpdb;
		if ( false !== strpos( $table, $wpdb->prefix ) ) {
			return $table;
		}

		return $wpdb->prefix . $table;
	}

	/**
	 * Get foreign key constant name
	 *
	 * @param string $table1 One table name.
	 * @param string $table2 Another table name.
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
	 * Format item for database
	 *
	 * @param array $data User provided data.
	 * @param array $defaults Default data. Previous data for existing record.
	 * @param string|null $current_time Current datetime.
	 *
	 * @return array
	 */
	protected function format_item_for_db( array $data, array $defaults, ?string $current_time = null ): array {
		if ( empty( $current_time ) ) {
			$current_time = current_time( 'mysql', true );
		}

		$mode = ! empty( $data[ $this->primary_key ] ) ? 'update' : 'create';

		$_data = [];
		foreach ( $defaults as $key => $value ) {
			$temp_data     = $data[ $key ] ?? $value;
			$_data[ $key ] = $this->serialize( $temp_data );
		}

		// Update updated time.
		if ( array_key_exists( $this->updated_at, $defaults ) ) {
			$_data[ $this->updated_at ] = $current_time;
		}

		if ( 'create' === $mode ) {
			// Update Author ID.
			if ( array_key_exists( $this->created_by, $defaults ) && ! isset( $data[ $this->created_by ] ) ) {
				$_data[ $this->created_by ] = get_current_user_id();
			}

			// Update created time.
			if ( array_key_exists( $this->created_at, $defaults ) ) {
				$_data[ $this->created_at ] = $current_time;
			}

			// Set deleted at time as null.
			if ( array_key_exists( $this->deleted_at, $defaults ) ) {
				$_data[ $this->deleted_at ] = null;
			}

			// Remove primary key.
			if ( array_key_exists( $this->primary_key, $_data ) ) {
				unset( $_data[ $this->primary_key ] );
			}
		}

		$_format = static::get_data_format_for_db( $this->get_table_name(), $_data );

		return array( $_data, $_format );
	}

	/**
	 * @inheritDoc
	 */
	public function batch( string $action, array $data ) {
		$method = 'batch_' . $action;
		if ( method_exists( $this, $method ) ) {
			return $this->{$method}( $data );
		}

		return new \WP_Error( 'no_batch_found', 'No batch action found.' );
	}

	/**
	 * @inheritDoc
	 */
	public function count_records( array $args = [] ) {
		return 0;
	}

	/**
	 * Get query builder
	 *
	 * @return QueryBuilder
	 */
	public function get_query_builder(): QueryBuilder {
		return QueryBuilder::table( $this->get_table_name() );
	}

	/********************************************************************************
	 * Deprecated Methods
	 *******************************************************************************/

	/**
	 * Get pagination data
	 *
	 * @param int $total_items Total items.
	 * @param int $per_page Per page.
	 * @param int $current_page Current page.
	 *
	 * @return array
	 *
	 * @deprecated 1.7.0
	 */
	public static function get_pagination( $total_items = 0, $per_page = 10, $current_page = 1 ) {
		$per_page = max( intval( $per_page ), 1 );

		return array(
			'total_items'  => $total_items,
			'per_page'     => $per_page,
			'current_page' => $current_page,
			'total_pages'  => ceil( $total_items / $per_page ),
		);
	}

	/**
	 * Generate pagination metadata
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 * @deprecated 1.7.0
	 */
	public static function getPaginationMetadata( array $args ) {
		_deprecated_function( __METHOD__, '1.1.5', __CLASS__ . '::get_pagination()' );
		$data = wp_parse_args(
			$args,
			array(
				'totalCount'     => 0,
				'limit'          => 10,
				'currentPage'    => 1,
				'offset'         => 0,
				'previousOffset' => null,
				'nextOffset'     => null,
				'pageCount'      => 0,
			)
		);
		if ( ! isset( $args['currentPage'] ) && isset( $args['offset'] ) ) {
			$data['currentPage'] = ( $args['offset'] / $data['limit'] ) + 1;
		}
		if ( ! isset( $args['offset'] ) && isset( $args['currentPage'] ) ) {
			$offset         = ( $data['currentPage'] - 1 ) * $data['limit'];
			$data['offset'] = max( $offset, 0 );
		}
		$previousOffset         = ( $data['currentPage'] - 2 ) * $data['limit'];
		$nextOffset             = $data['currentPage'] * $data['limit'];
		$data['previousOffset'] = ( $previousOffset < 0 || $previousOffset > $data['totalCount'] ) ? null : $previousOffset;
		$data['nextOffset']     = ( $nextOffset < 0 || $nextOffset > $data['totalCount'] ) ? null : $nextOffset;
		$data['pageCount']      = ceil( $data['totalCount'] / $data['limit'] );

		return $data;
	}

	/**
	 * Find multiple items
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 *
	 * @deprecated 1.7.0
	 */
	public function find( $args = [] ) {
		return $this->find_multiple( $args );
	}

	/**
	 * Find by primary key value
	 *
	 * @param int|string $id Primary key value.
	 *
	 * @return ArrayObject|static
	 *
	 * @deprecated 1.7.0
	 * @see DatabaseModel::find_single();
	 */
	public function find_by_id( $id ) {
		return $this->find_single( $id );
	}

	/**
	 * Update batch items
	 *
	 * @param array $data Data to update.
	 *
	 * @return int[]
	 *
	 * @deprecated 1.7.0
	 * @see DatabaseModel::batch_create();
	 */
	public function create_multiple( array $data ) {
		return $this->batch_create( $data );
	}

	/**
	 * Update batch items
	 *
	 * @param array $data Data to update.
	 *
	 * @return bool
	 *
	 * @deprecated 1.7.0
	 * @see DatabaseModel::batch_update();
	 */
	public function update_multiple( array $data ) {
		return $this->batch_update( $data );
	}
}
