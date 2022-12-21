<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * ImageUploader class
 */
class ImageUploader extends BaseField {

	/**
	 * Render html content
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		$value       = $this->get_value();
		$button_text = $value ? 'Update Image' : 'Set Image';
		global $post;
		$attrs = [
			'class'            => 'button slide_image_add',
			'href'             => esc_url( get_upload_iframe_src( 'image', $post->ID ) ),
			'data-title'       => esc_attr( 'Select or Upload Slide Background Image' ),
			'data-button-text' => esc_attr( $button_text ),
		];

		$input_attrs = [
			'type'  => 'hidden',
			'class' => $this->get_setting( 'field_class' ),
			'name'  => $this->get_name(),
			'value' => $value,
		];

		$html  = '<input ' . $this->array_to_attributes( $input_attrs ) . ' />';
		$html .= '<a ' . $this->array_to_attributes( $attrs ) . '>' . esc_html( $button_text ) . '</a>';

		return $html;
	}
}
