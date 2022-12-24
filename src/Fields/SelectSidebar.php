<?php

namespace Stackonet\WP\Framework\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Sidebar class
 */
class SelectSidebar extends BaseField {

	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		global $wp_registered_sidebars;
		$value = $this->get_value();
		$name  = $this->get_name();

		$html  = '<select name="' . $name . '">';
		$html .= '<option value="">' . esc_attr( 'Default' ) . '</option>';
		foreach ( $wp_registered_sidebars as $key => $option ) {
			$selected = ( $value === $key ) ? ' selected="selected"' : '';
			$html    .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_attr( $option['name'] ) . '</option>';
		}
		$html .= '</select>';

		return $html;
	}
}
