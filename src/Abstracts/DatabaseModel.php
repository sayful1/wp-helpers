<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayObject;
use BadMethodCallException;
use Stackonet\WP\Framework\Deprecated\DatabaseModelMethods;
use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use Stackonet\WP\Framework\Supports\QueryBuilder;
use Stackonet\WP\Framework\Traits\Cacheable;
use Stackonet\WP\Framework\Traits\TableInfo;

defined( 'ABSPATH' ) || exit;

/**
 * Class DatabaseModel
 * A thin layer using wpdb database class form rapid development
 *
 * @method static int[] create_multiple( array $data )
 * @method static bool update_multiple( array $data )
 * @method static DatabaseModel|ArrayObject find_by_id( int $id )
 * @method static DatabaseModel|ArrayObject find_single( int $id )
 * @method static array|DatabaseModel[] find( array $args = [] )
 * @method static array|DatabaseModel[] find_multiple( array $args = [] )
 * @method static int create( array $data = [] )
 * @method static bool update( array $data = [] )
 * @method static bool trash( int $id = 0 )
 * @method static bool restore( int $id = 0 )
 * @method static bool delete( int $id = 0 )
 * @method static mixed batch( string $action, array $data = [] )
 * @method static int[] batch_create( array $data = [] )
 * @method static bool batch_update( array $data = [] )
 * @method static bool batch_trash( array $ids = [] )
 * @method static bool batch_restore( array $ids = [] )
 * @method static bool batch_delete( array $ids = [] )
 * @method static array count_records( array $args = [] )
 * @method static array get_statuses_count( ?string $current_status = 'all' )
 * @method static mixed unserialize( $data )
 * @method static mixed serialize( $data )
 * @method static string get_table_name( ?string $table = null )
 * @method static string get_foreign_key_constant_name( string $table1, string $table2 )
 * @method static QueryBuilder get_query_builder
 * @method array get_pagination_and_order_data( array $args )
 * @method string get_order_by( array $args )
 * @method int calculate_offset( int $current_page = 1, int $per_page = 0 )
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
abstract class DatabaseModel extends Data {

	use Cacheable, TableInfo, DatabaseModelMethods;

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
	 * Model constructor.
	 *
	 * @param  mixed  $data  The data to be read.
	 */
	public function __construct( $data = [] ) {
		$this->primary_key      = static::get_primary_key( $this->get_table_name() );
		$this->primary_key_type = static::get_primary_key_data_format( $this->get_table_name() );
		if ( $data ) {
			$this->data = $this->read( $data );
			$this->set_id( $this->data[ $this->primary_key ] ?? 0 );
			$this->set_object_read();
		}
	}

	/**
	 * Find multiple records from database
	 *
	 * @param  array  $args  The arguments for query.
	 *
	 * @return array|static[]
	 */
	protected function __find_multiple( array $args = [] ): array {
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
	 * @param  int  $id  The id of the record.
	 *
	 * @return ArrayObject|static
	 */
	protected function __find_single( $id ) {
		$item = $this->get_data_store()->find_single( $id );

		return $item ? new static( $item ) : new ArrayObject();
	}

	/**
	 * Method to read a record.
	 *
	 * @param  mixed  $data  The data to be read.
	 *
	 * @return array
	 */
	public function read( $data ) {
		if ( $data instanceof Data ) {
			return $data->get_data();
		}

		$data_store = $this->get_data_store();

		if ( is_array( $data ) && count( $data ) ) {
			$data = $data_store->format_item_for_output( $data );
			if ( $data instanceof Data ) {
				return $data->get_data();
			} elseif ( is_array( $data ) ) {
				return $data;
			}
		}

		if ( is_numeric( $data ) ) {
			$data = $data_store->find_single( $data );
			if ( is_array( $data ) ) {
				return $data;
			} elseif ( $data instanceof Data ) {
				return $data->get_data();
			}
		}

		return $data_store->get_default_data( $data_store->get_table_name() );
	}

	/**
	 * Get data store class
	 *
	 * @return DataStoreInterface|DataStoreBase
	 */
	public function get_data_store() {
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
				'status'     => $this->status,
			],
			get_called_class()
		);
	}

	/**
	 * Handle store method call
	 *
	 * @param  string  $name  The method name.
	 * @param  array|mixed  $arguments  The method arguments.
	 *
	 * @return mixed
	 * @throws BadMethodCallException When method not found.
	 */
	public function __call( $name, $arguments ) {
		$old_to_new = [
			'update_multiple' => 'batch_update',
			'create_multiple' => 'batch_create',
			'find_by_id'      => 'find_single',
			'find'            => 'find_multiple',
		];

		if ( isset( $old_to_new[ $name ] ) ) {
			$name = $old_to_new[ $name ];
		}

		$data_store = $this->get_data_store();
		if ( in_array( $name, [ 'create', 'update' ], true ) ) {
			if ( count( $arguments ) > 0 ) {
				$this->set_props( $arguments[0] );
			}
			$this->apply_changes();

			return call_user_func_array( [ $data_store, $name ], [ $this->get_data() ] );
		}
		if ( count( $arguments ) === 0 ) {
			if ( 'trash' === $name ) {
				$this->set_prop( $this->deleted_at, current_time( 'mysql' ) );
				$this->apply_changes();

				return $data_store->trash( $this->get_id() );
			}
			if ( 'restore' === $name ) {
				$this->set_prop( $this->deleted_at, null );
				$this->apply_changes();

				return $data_store->restore( $this->get_id() );
			}
			if ( 'delete' === $name ) {
				$this->apply_changes();
				$response = $data_store->delete( $this->get_id() );
				if ( $response ) {
					$this->set_id( 0 );
				}

				return $response;
			}
		}
		if ( in_array( $name, [ 'find_multiple', 'find_single' ], true ) ) {
			$name = '__' . $name;

			return $this->$name( ...$arguments );
		}
		if ( method_exists( $data_store, $name ) ) {
			return call_user_func_array( [ $data_store, $name ], $arguments );
		}

		throw new BadMethodCallException( "Method {$name} does not exist." );
	}

	/**
	 * Handle store method call
	 *
	 * @param  string  $name  The method name.
	 * @param  array|mixed  $arguments  The method arguments.
	 *
	 * @return mixed
	 * @throws BadMethodCallException When method not found.
	 */
	public static function __callStatic( $name, $arguments ) {
		return ( new static() )->__call( $name, $arguments );
	}
}
