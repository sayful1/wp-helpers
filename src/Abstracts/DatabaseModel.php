<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayObject;
use BadMethodCallException;
use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use Stackonet\WP\Framework\Supports\QueryBuilder;
use Stackonet\WP\Framework\Traits\Cacheable;
use Stackonet\WP\Framework\Traits\TableInfo;

defined( 'ABSPATH' ) || exit;

/**
 * Class DatabaseModel
 * A thin layer using wpdb database class form rapid development
 *
 * @method array count_records( array $args = [] )
 * @method int[] create_multiple( array $data )
 * @method bool update_multiple( array $data )
 * @method Data|ArrayObject find_by_id( int $id )
 * @method array|Data[] find( array $args = [] )
 * @method string get_table_name( ?string $table = null )
 * @method string get_foreign_key_constant_name( string $table1, string $table2 )
 * @method QueryBuilder get_query_builder
 * @method bool trash( int $id )
 * @method bool restore( int $id )
 * @method bool delete( int $id )
 * @method mixed batch( string $action, array $data = [] )
 * @method int[] batch_create( array $data = [] )
 * @method bool batch_update( array $data = [] )
 * @method bool batch_trash( array $ids = [] )
 * @method bool batch_restore( array $ids = [] )
 * @method bool batch_delete( array $ids = [] )
 * @method mixed unserialize( $data )
 * @method mixed serialize( $data )
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
abstract class DatabaseModel extends Data {

	use Cacheable, TableInfo;

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @var object
	 */
	protected $data_store;

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
	 * @param array $args The arguments for query.
	 *
	 * @return array|static[]
	 */
	public function find_multiple( array $args = [] ): array {
		$items = $this->get_data_store()->find_multiple( $args );
		$data  = [];
		foreach ( $items as $item ) {
			$data[] = new static( $item );
		}

		return $data;
	}

	/**
	 * Find record by id
	 *
	 * @param int $id The id of the record.
	 *
	 * @return ArrayObject|static
	 */
	public function find_single( $id ) {
		$item = $this->get_data_store()->find_single( $id );

		return $item ? new static( $item ) : new ArrayObject();
	}

	/**
	 * Update data
	 *
	 * @param array $data The data to be updated.
	 *
	 * @return bool
	 */
	public function create( array $data = [] ) {
		if ( empty( $data ) ) {
			$data = $this->get_data();
		}

		return $this->get_data_store()->create( $data );
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
			$this->apply_changes();
			$data = $this->get_data();
		}

		return $this->get_data_store()->update( $data );
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
			$item = $this->get_data_store()->read( $data );
			if ( is_array( $item ) ) {
				$data = $item;
			}
		}

		$table_name = $this->get_table_name();
		$default    = static::get_default_data( $table_name );

		if ( is_array( $data ) ) {
			$item = [];
			foreach ( $default as $column_name => $default_value ) {
				$temp_data            = $data[ $column_name ] ?? $default_value;
				$item[ $column_name ] = $this->unserialize( $temp_data );
			}

			return static::format_data_by_type( $table_name, $item );
		}

		return $default;
	}

	/**
	 * Get data store class
	 *
	 * @return DataStoreInterface|DataStoreBase
	 */
	protected function get_data_store() {
		if ( property_exists( $this, 'data_store' ) && $this->data_store ) {
			$store = new $this->data_store();
			if ( $store instanceof DataStoreBase ) {
				return $store;
			}
		}

		return DataStoreBase::get_instance(
			$this->table,
			[
				'created_by' => $this->created_by,
				'created_at' => $this->created_at,
				'updated_at' => $this->updated_at,
				'deleted_at' => $this->deleted_at,
			]
		);
	}

	/**
	 * Handle store method call
	 *
	 * @param string $name The method name.
	 * @param array|mixed $arguments The method arguments.
	 *
	 * @return mixed
	 * @throws BadMethodCallException When method not found.
	 */
	public function __call( $name, $arguments ) {
		if ( method_exists( $this->get_data_store(), $name ) ) {
			return call_user_func_array( [ $this->get_data_store(), $name ], $arguments );
		}

		$old_to_new = [
			'update_multiple' => 'batch_update',
			'create_multiple' => 'batch_create',
			'find_by_id'      => 'find_single',
			'find'            => 'find_many',
		];

		if ( isset( $old_to_new[ $name ] ) ) {
			return call_user_func_array( [ $this, $old_to_new[ $name ] ], $arguments );
		}

		throw new BadMethodCallException( "Method {$name} does not exist." );
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
		_deprecated_function( __METHOD__, '1.7.0' );
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
}
