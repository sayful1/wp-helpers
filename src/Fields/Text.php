<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * Text class
 */
class Text extends BaseField {

	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		return '<input ' . $this->build_attributes() . ' />';
	}
}
