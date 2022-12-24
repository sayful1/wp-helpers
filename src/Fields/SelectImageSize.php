<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * SelectImageSize class
 */
class SelectImageSize extends Select {
	/**
	 * Render field html
	 *
	 * @inerhitDoc
	 */
	public function render(): string {
		$this->set_setting( 'choices', self::get_available_image_sizes() );

		return parent::render();
	}

	/**
	 * Get available image sizes
	 *
	 * @return array
	 */
	public static function get_available_image_sizes(): array {
		global $_wp_additional_image_sizes;

		$sizes = [];
		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, [ 'thumbnail', 'medium', 'medium_large', 'large' ], true ) ) {

				$width  = get_option( "{$_size}_size_w" );
				$height = get_option( "{$_size}_size_h" );
				$crop   = get_option( "{$_size}_crop" ) ? 'hard' : 'soft';

				$sizes[ $_size ] = sprintf( '%s - %s:%sx%s', $_size, $crop, $width, $height );

			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

				$width  = $_wp_additional_image_sizes[ $_size ]['width'];
				$height = $_wp_additional_image_sizes[ $_size ]['height'];
				$crop   = $_wp_additional_image_sizes[ $_size ]['crop'] ? 'hard' : 'soft';

				$sizes[ $_size ] = sprintf( '%s - %s:%sx%s', $_size, $crop, $width, $height );
			}
		}

		return array_merge( $sizes, [ 'full' => 'original uploaded image' ] );
	}
}
