<?php

namespace Stackonet\WP\Framework\Traits;

use WP_Error;
use WP_REST_Request;

trait ApiPermissionChecker {

	/**
	 * List of capabilities based on the request method.
	 *
	 * @var array
	 */
	protected $capabilities = [
		'read_items' => false,
		'read_item'  => false,
		'create'     => 'publish_pages',
		'update'     => 'edit_others_pages',
		'delete'     => 'delete_others_pages',
		'batch'      => 'delete_others_pages',
	];

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @param  WP_REST_Request $request  Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return $this->permissions_check( 'read_items' );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param  WP_REST_Request $request  Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		return $this->permissions_check( 'read_item' );
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @param  WP_REST_Request $request  Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		return $this->permissions_check( 'create' );
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @param  WP_REST_Request $request  Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		return $this->permissions_check( 'update' );
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param  WP_REST_Request $request  Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->permissions_check( 'delete' );
	}

	/**
	 * Batch operation permission check
	 *
	 * @param  WP_REST_Request $request  Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function batch_operation_permissions_check( $request ) {
		return $this->permissions_check( 'batch' );
	}

	/**
	 * Get permission error message
	 *
	 * @param  string $operation Operation name.
	 *
	 * @return true|WP_Error True on success, WP_Error object otherwise.
	 */
	protected function permissions_check( string $operation ) {
		if (
			isset( $this->capabilities[ $operation ] ) &&
			$this->capabilities[ $operation ] &&
			! current_user_can( $this->capabilities[ $operation ] )
		) {
			return new WP_Error(
				'rest_forbidden_context',
				'Sorry, you are not allowed to access this resource.',
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}
