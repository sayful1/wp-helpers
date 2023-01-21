<?php
/**
 * Plugin Name: Stackonet WP Helper
 * Description: A powerful WordPress plugin to extend functionality to your WordPress site.
 * Version: 1.9.6
 * Author: Stackonet Services (Pvt.) Ltd.
 * Author URI: https://stackonet.com
 * Requires at least: 5.3
 * Requires PHP: 7.2
 * Text Domain: stackonet-wp-helper
 * Domain Path: /languages
 *
 * @package Stackonet/WP/Helpers
 */

defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/src/autoload.php';

// Load example features only if the plugin is in development mode.
if ( in_array( wp_get_environment_type(), [ 'local', 'development' ], true ) ) {
	require dirname( __FILE__ ) . '/examples/autoload.php';
}
