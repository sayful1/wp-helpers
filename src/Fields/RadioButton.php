<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * ButtonGroup class
 */
class RadioButton extends BaseField {

	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		$value = $this->get_value();
		$name  = $this->get_name();

		$html = '<div id="' . esc_attr( $this->get_setting( 'id' ) ) . '" class="radio-button">';
		foreach ( $this->get_choices() as $choice ) {
			$is_pro_only = isset( $choice['pro_only'] ) && $choice['pro_only'];
			$id          = sprintf( '%s-%s', $this->get_setting( 'id' ), $choice['value'] );
			$label_class = sprintf( 'radio-button-label radio-button-label-%s', ( $choice['value'] === $value ) ? 'on' : 'off' );
			$radio_attr  = [
				'type'    => 'radio',
				'name'    => $name,
				'id'      => $id,
				'class'   => 'radio-button-input screen-reader-text',
				'value'   => $choice['value'],
				'checked' => $choice['value'] === $value,
			];
			if ( isset( $choice['disabled'] ) && $choice['disabled'] ) {
				$radio_attr['disabled'] = true;
			}
			if ( $is_pro_only ) {
				$label_class .= ' has-pro-tag';
				$label        = esc_html( $choice['label'] ) . '<span class="pro-only">' . esc_html( 'pro' ) . '</span>';
			} else {
				$label = esc_html( $choice['label'] );
			}
			$html .= '<input ' . $this->array_to_attributes( $radio_attr ) . ' />';
			$html .= '<label class="' . esc_attr( $label_class ) . '" for="' . esc_attr( $id ) . '">' . $label . '</label>';
		}

		$html .= '</div>';

		return $html;
	}
}
