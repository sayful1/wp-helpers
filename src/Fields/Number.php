<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * Number class
 */
class Number extends BaseField {

	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		$this->set_setting( 'type', 'number' );

		return '<input ' . $this->build_attributes() . ' />';
	}
}
