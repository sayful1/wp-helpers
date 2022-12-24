<?php

namespace Stackonet\WP\Framework\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface FieldInterface
 *
 * @package Stackonet\WP\Framework\Interfaces
 */
interface FieldInterface {
	/**
	 * Set settings
	 *
	 * @param array $settings The settings array.
	 *
	 * @return mixed
	 */
	public function set_settings( array $settings );

	/**
	 * Set field name
	 *
	 * @param string $name the field name.
	 *
	 * @return mixed
	 */
	public function set_name( string $name );

	/**
	 * Set field value
	 *
	 * @param mixed $value The field value.
	 *
	 * @return mixed
	 */
	public function set_value( $value );

	/**
	 * Render field html
	 *
	 * @return string
	 */
	public function render(): string;

	/**
	 * Sanitize user submitted value
	 *
	 * @param mixed $value The value to be sanitized.
	 * @param array $settings The settings array.
	 *
	 * @return mixed
	 */
	public function sanitize( $value, array $settings = [] );
}
