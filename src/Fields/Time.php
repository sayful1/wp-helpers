<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * Time class
 */
class Time extends BaseField {
	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		$this->set_setting( 'type', 'time' );

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
		if ( is_string( $value ) && preg_match( '/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $value ) ) {
			return $value;
		}

		return '';
	}
}
