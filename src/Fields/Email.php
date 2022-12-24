<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * Email class
 */
class Email extends BaseField {

	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		$this->set_setting( 'type', 'email' );

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
		return sanitize_email( $value );
	}
}
