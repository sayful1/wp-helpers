<?php

namespace Stackonet\WP\Framework\Traits;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Abstracts\DataStoreBase;
use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

trait ApiCrudOperations {
	use ApiResponse, ApiUtils, ApiPermissionChecker;

	/**
	 * List of capabilities based on the request method.
	 *
	 * @var array
	 */
	protected $exclude_operations = [];

	/**
	 * Get store class
	 *
	 * @return DataStoreInterface|DataStoreBase
	 */
	abstract public function get_store();

	/** ==== Start - Methods from \WP_REST_Controller::class ==== **/
	abstract public function prepare_response_for_collection( $response );

	abstract public function get_context_param( $args = array() );

	/** ==== End - Methods from \WP_REST_Controller::class ==== **/

	/**
	 * Check if REST operation is allowed
	 *
	 * @param  string  $operation  The operation name.
	 *
	 * @return bool
	 */
	protected function is_operation_allowed( string $operation ): bool {
		$operations = [ 'read_items', 'read_item', 'create', 'update', 'delete', 'trash', 'restore', 'batch' ];
		if ( in_array( $operation, $operations, true ) ) {
			return ! in_array( $operation, $this->exclude_operations, true );
		}

		return false;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = $this->namespace ?? '';
		$rest_base = isset( $this->rest_base ) ? trim( $this->rest_base, '/' ) : '';
		if ( empty( $namespace ) || empty( $rest_base ) ) {
			_doing_it_wrong( __FUNCTION__, 'namespace and rest_base are required.', '2.1.0' );

			return;
		}

		$args = [];
		if ( $this->is_operation_allowed( 'read_items' ) ) {
			$args[] = [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			];
		}
		if ( $this->is_operation_allowed( 'create' ) ) {
			$args[] = [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			];
		}

		if ( count( $args ) ) {
			register_rest_route( $namespace, $rest_base, $args );
		}

		$args2 = [
			'args' => [
				'id' => [
					'description'       => 'Item unique id.',
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
					'minimum'           => 1,
				],
			],
		];

		if ( $this->is_operation_allowed( 'read_item' ) ) {
			$args2[] = [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
			];
		}

		if ( $this->is_operation_allowed( 'update' ) ) {
			$args2[] = [
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			];
		}

		if ( $this->is_operation_allowed( 'delete' ) ) {
			$args2[] = [
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
			];
		}

		if ( count( $args2 ) > 1 ) {
			register_rest_route( $namespace, $rest_base . '/(?P<id>\d+)', $args2 );
		}

		if ( $this->is_operation_allowed( 'trash' ) ) {
			register_rest_route(
				$namespace,
				$rest_base . '/(?P<id>\d+)/trash',
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'trash_item' ],
						'permission_callback' => [ $this, 'update_item_permissions_check' ],
						'args'                => [
							'id' => [
								'description'       => 'Item unique id.',
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
								'minimum'           => 1,
							],
						],
					],
				]
			);
		}

		if ( $this->is_operation_allowed( 'restore' ) ) {
			register_rest_route(
				$namespace,
				$rest_base . '/(?P<id>\d+)/restore',
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'restore_item' ],
						'permission_callback' => [ $this, 'update_item_permissions_check' ],
						'args'                => [
							'id' => [
								'description'       => 'Item unique id.',
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
								'minimum'           => 1,
							],
						],
					],
				]
			);
		}

		if ( $this->is_operation_allowed( 'batch' ) ) {
			register_rest_route(
				$namespace,
				$rest_base . '/batch',
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'batch_operation' ],
						'args'                => [
							'action'  => [
								'type'              => 'string',
								'enum'              => [ 'create', 'update', 'trash', 'restore', 'delete' ],
								'validate_callback' => 'rest_validate_request_arg',
							],
							'payload' => [
								'type'              => 'array',
								'validate_callback' => 'rest_validate_request_arg',
							],
						],
						'permission_callback' => [ $this, 'batch_operation_permissions_check' ],
					],
				]
			);
		}
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param  WP_REST_Request  $request  Request object.
	 *
	 * @return mixed|WP_Error The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_item_for_database( $request ) {
		return $request->get_params();
	}

	/**
	 * Prepares the collection item for the REST response.
	 *
	 * @param  mixed|Data  $item  The collection item.
	 *
	 * @return array|mixed Response object on success.
	 */
	public function prepare_collection_item_for_response( $item ) {
		if ( $item instanceof Data ) {
			return $item->to_array();
		}

		return $item;
	}

	/**
	 * Prepares the collection item for the REST response.
	 *
	 * @param  mixed|Data  $item  The collection item.
	 *
	 * @return array|mixed Response object on success.
	 */
	public function prepare_single_item_for_response( $item ) {
		if ( $item instanceof Data ) {
			return $item->to_array();
		}

		return $item;
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$status   = (string) $request->get_param( 'status' );

		$items      = $this->get_store()->find_multiple( $request->get_params() );
		$counts     = $this->get_store()->count_records( $request->get_params() );
		$count      = $counts[ $status ] ?? $counts['all'];
		$pagination = static::get_pagination_data( $count, $per_page, $page );

		$items = array_map( [ $this, 'prepare_collection_item_for_response' ], $items );

		$statuses = [];
		if ( method_exists( $this->get_store(), 'get_statuses_count' ) ) {
			$statuses = $this->get_store()->get_statuses_count( $status );
		}

		return $this->respondOK(
			[
				'items'      => $items,
				'pagination' => $pagination,
				'statuses'   => $statuses,
			]
		);

	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function create_item( $request ) {
		$data = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondUnprocessableEntity();
		}

		$id   = $this->get_store()->create( $data );
		$item = $this->get_store()->find_single( $id );

		return $this->respondCreated( $item );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		return $this->respondOK( $this->prepare_single_item_for_response( $item ) );
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		$data = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondUnprocessableEntity();
		}

		$this->get_store()->update( $data );
		$item = $this->get_store()->find_single( $id );

		return $this->respondOK( $item );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		$this->get_store()->delete( $id );

		return $this->respondOK( $item );
	}

	/**
	 * Trash one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function trash_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		if ( $this->get_store()->trash( $id ) ) {
			return $this->respondOK();
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Restore one item from the trash collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function restore_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		if ( $this->get_store()->restore( $id ) ) {
			return $this->respondOK();
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Batch operation
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function batch_operation( $request ) {
		$supported_actions = [ 'create', 'update', 'trash', 'restore', 'delete' ];
		if ( $request->has_param( 'action' ) && $request->has_param( 'payload' ) ) {
			$action  = $request->get_param( 'action' );
			$payload = $request->get_param( 'payload' );
			if ( in_array( $action, $supported_actions, true ) ) {
				$this->get_store()->batch( $action, $payload );
			}

			return $this->respondAccepted();
		}

		// For backward compatibility.
		foreach ( $request->get_params() as $action => $data ) {
			if ( in_array( $action, $supported_actions, true ) ) {
				$this->get_store()->batch( $action, $data );
			}
		}

		return $this->respondAccepted();
	}
}
