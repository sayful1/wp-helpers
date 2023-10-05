<?php

namespace Stackonet\WP\Framework\Fields;

use Stackonet\WP\Framework\Supports\Validate;

/**
 * ImageUploader class
 */
class ImageUploader extends BaseField {

	/**
	 * Get value
	 *
	 * @return array
	 */
	public function get_value() {
		$value = parent::get_value();
		if ( is_string( $value ) ) {
			$value = wp_strip_all_tags( rtrim( $value, ',' ) );
			$value = array_filter( array_map( 'intval', explode( ',', $value ) ) );
		} elseif ( is_numeric( $value ) ) {
			$value = [ intval( $value ) ];
		}

		return is_array( $value ) ? $value : [];
	}

	/**
	 * Render html content
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		if ( Validate::checked( $this->get_setting( 'gallery', false ) ) ) {
			return $this->render_gallery();
		}

		return $this->render_image();
	}

	/**
	 * Render image uploader html content
	 *
	 * @return string
	 */
	public function render_image(): string {
		$value       = $this->get_value();
		$button_text = $value ? 'Update Image' : 'Set Image';
		global $post;
		$attrs = [
			'class'                  => 'button',
			'href'                   => esc_url( get_upload_iframe_src( 'image', $post->ID ) ),
			'data-title'             => esc_attr( $this->get_setting( 'modal_title', 'Select or Upload Image' ) ),
			'data-button-text'       => esc_attr( $button_text ),
			'data-media-frame'       => 'select',
			'data-input-target-name' => $this->get_name(),
			'data-preview-target'    => '.field-media-frame-select__list',
		];

		$input_attrs = [
			'type'  => 'hidden',
			'class' => $this->get_setting( 'field_class' ),
			'name'  => $this->get_name(),
			'value' => implode( ',', $value ),
		];

		$html  = '<div class="field-media-frame-select">';
		$html .= '<input ' . $this->array_to_attributes( $input_attrs ) . ' />';
		$html .= '<a ' . $this->array_to_attributes( $attrs ) . '>' . esc_html( $button_text ) . '</a>';
		$html .= '<ul class="field-media-frame-select__list">';
		if ( $value ) {
			foreach ( $value as $thumb ) {
				$html .= '<li>' . wp_get_attachment_image( $thumb, [ 150, 150 ] ) . '</li>';
			}
		}
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render field html
	 */
	public function render_gallery(): string {
		$value = $this->get_value();
		$html  = '';

		$button_attr = [
			'href'                   => esc_url( get_upload_iframe_src( 'media' ) ),
			'class'                  => 'button',
			'data-value'             => implode( ',', $value ),
			'data-media-frame'       => 'post',
			'data-input-target-name' => $this->get_name(),
			'data-preview-target'    => '.field-media-frame-post__list',
			'data-create-text'       => $this->get_setting( 'create_text', 'Create Gallery' ),
			'data-edit-text'         => $this->get_setting( 'edit_text', 'Edit Gallery' ),
			'data-save-text'         => $this->get_setting( 'save_text', 'Save Gallery' ),
			'data-progress-text'     => $this->get_setting( 'progress_text', 'Saving...' ),
			'data-insert-text'       => $this->get_setting( 'insert_text', 'Insert' ),
		];

		$input_attrs = [
			'type'  => 'hidden',
			'class' => $this->get_setting( 'field_class' ),
			'name'  => $this->get_name(),
			'value' => implode( ',', $value ),
		];

		$btn_text = $value ?
			$this->get_setting( 'edit_text', 'Edit Gallery' ) :
			$this->get_setting( 'add_text', 'Add Gallery' );

		$html .= '<div class=".field-media-frame-post">';
		$html .= '<input ' . $this->array_to_attributes( $input_attrs ) . ' />';
		$html .= '<a ' . $this->array_to_attributes( $button_attr ) . '>' . esc_html( $btn_text ) . '</a>';
		$html .= '<ul class="field-media-frame-post__list">';
		if ( $value ) {
			foreach ( $value as $thumb ) {
				$html .= '<li>' . wp_get_attachment_image( $thumb, [ 50, 50 ] ) . '</li>';
			}
		}
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}
}
