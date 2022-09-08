<?php

namespace Stackonet\WP\Framework\Supports;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 *
 * @package Stackonet\WP\Framework\Supports
 */
class Logger {
	/**
	 * Log error to error log
	 *
	 * @param mixed $log The data to be logged.
	 *
	 * @return bool
	 */
	public static function log( $log ) {
		// Log Exception.
		if ( $log instanceof Exception ) {
			return error_log( $log ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		// Log array and object.
		if ( is_array( $log ) || is_object( $log ) ) {
			return error_log( print_r( $log, true ) ); // phpcs:ignore
		}

		return error_log( $log ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
