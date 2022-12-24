<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * MultiCheckbox class
 */
class CheckboxMulti extends BaseField {

	/**
	 * Render field
	 *
	 * @return string
	 */
	public function render(): string {
		$this->set_setting( 'multiple', true );
		$choices = $this->get_choices();
		$value   = $this->get_value();
		if ( is_string( $value ) ) {
			$value = explode( ',', wp_strip_all_tags( rtrim( $value, ',' ) ) );
		}
		$html = '';
		foreach ( $choices as $choice ) {
			$id         = sprintf( '%s_%s', $this->get_setting( 'id' ), $choice['value'] );
			$selected   = in_array( $choice['value'], $value ) ? 'selected' : ''; // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			$attributes = array(
				'type'    => 'checkbox',
				'id'      => $id,
				'name'    => $this->get_name() . '[]',
				'value'   => $choice['value'],
				'checked' => $selected,
			);
			if ( isset( $choice['readonly'] ) ) {
				$attributes['disabled'] = true;
			}
			$html .= '<label for="' . $id . '">';
			$html .= '<input ' . $this->array_to_attributes( $attributes ) . '>';
			$html .= '<span>' . esc_html( $choice['label'] ) . '</span></label><br>';
		}
		$html .= '</select>';

		return $html;
	}
}
