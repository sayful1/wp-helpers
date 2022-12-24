<?php

namespace Stackonet\WP\Framework\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * CheckboxTrueFalse class
 */
class CheckboxAcceptance extends BaseField {

	/**
	 * Render checkbox html content
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		$name        = $this->get_name();
		$true_value  = $this->get_setting( 'true_value', 'on' );
		$false_value = $this->get_setting( 'false_value', 'off' );

		$attributes = array(
			'type'    => 'checkbox',
			'id'      => $this->get_setting( 'id' ),
			'name'    => $name,
			'value'   => $true_value,
			'checked' => $true_value === $this->get_value(),
		);

		$html  = '<input type="hidden" name="' . $name . '" value="' . esc_attr( $false_value ) . '">';
		$html .= '<label for="' . $this->get_setting( 'id' ) . '">';
		$html .= '<input ' . $this->array_to_attributes( $attributes ) . '>';
		$html .= '<span>' . $this->get_setting( 'subtitle' ) . '</span></label>';

		return $html;
	}
}
