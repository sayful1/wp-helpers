<?php

namespace Stackonet\WP\Framework\Deprecated;

trait DatabaseModelMethods {
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
		$pre_offset             = ( $data['currentPage'] - 2 ) * $data['limit'];
		$next_offset            = $data['currentPage'] * $data['limit'];
		$data['previousOffset'] = ( $pre_offset < 0 || $pre_offset > $data['totalCount'] ) ? null : $pre_offset;
		$data['nextOffset']     = ( $next_offset < 0 || $next_offset > $data['totalCount'] ) ? null : $next_offset;
		$data['pageCount']      = ceil( $data['totalCount'] / $data['limit'] );

		return $data;
	}
}
