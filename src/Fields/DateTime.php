<?php

namespace Stackonet\WP\Framework\Fields;

use Stackonet\WP\Framework\Supports\Validate;

/**
 * DateTime class
 */
class DateTime extends BaseField {

	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		$this->set_setting( 'type', 'datetime-local' );

		return '<input ' . $this->build_attributes() . ' />';
	}

	/**
	 * Sanitize user submitted value
	 *
	 * @param mixed $value The value to be sanitized.
	 * @param array $settings The settings array.
	 *
	 * @return string The time value in H:i format. Or empty string if the value is not valid.
	 */
	public function sanitize( $value, array $settings = [] ) {
		if ( Validate::datetime( $value ) ) {
			return $value;
		}

		return '';
	}
}