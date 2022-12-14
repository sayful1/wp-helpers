<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * ImageGallery class
 */
class ImagesGallery extends BaseField {

	/**
	 * Render field html
	 *
	 * @inheritDoc
	 */
	public function render(): string {
		global $post;
		$value = wp_strip_all_tags( rtrim( $this->get_value(), ',' ) );
		$html  = '';

		$button_attr = [
			'href'          => '#',
			'id'            => 'carousel_slider_gallery_btn',
			'class'         => 'button',
			'data-id'       => $post->ID,
			'data-ids'      => $value,
			'data-create'   => esc_html( 'Create Gallery' ),
			'data-edit'     => esc_html( 'Edit Gallery' ),
			'data-save'     => esc_html( 'Save Gallery' ),
			'data-progress' => esc_html( 'Saving...' ),
			'data-insert'   => esc_html( 'Insert' ),
		];

		$btn_text = $value ? 'Edit Gallery' : 'Add Gallery';

		$html .= '<div class="carousel_slider_images">';
		$html .= '<input type="hidden" value="' . esc_attr( $value ) . '" id="_carousel_slider_images_ids" name="' . $this->get_name() . '">';
		$html .= '<a ' . $this->array_to_attributes( $button_attr ) . '>' . esc_html( $btn_text ) . '</a>';
		$html .= '<ul class="carousel_slider_gallery_list">';
		if ( $value ) {
			$thumbs = array_map( 'intval', explode( ',', $value ) );
			foreach ( $thumbs as $thumb ) {
				$html .= '<li>' . wp_get_attachment_image( $thumb, [ 50, 50 ] ) . '</li>';
			}
		}
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}
}
