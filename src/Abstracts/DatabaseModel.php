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
			return $data->get_data();
		}

		$data_store = $this->get_data_store();

		if ( is_array( $data ) ) {
			return $data_store->format_item_for_output( $data );
		}

		if ( is_numeric( $data ) ) {
			$data = $data_store->find_single( $data );
			if ( is_array( $data ) ) {
				return $data;
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
			]
		);
	}

	/**
	 * Handle store method call
	 *
	 * @param string      $name The method name.
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
}
