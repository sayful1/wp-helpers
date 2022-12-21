<?php

namespace Stackonet\WP\Framework\Supports;

/**
 * CssGenerator class
 */
class CssGenerator {

	/**
	 * Parse output args
	 *
	 * @param array $output
	 *
	 * @return array
	 */
	protected static function parse_output_args( $output ) {
		$defaults = array(
			'element'       => '',
			'property'      => '',
			'media_query'   => 'global',
			'prefix'        => '',
			'units'         => '',
			'suffix'        => '',
			'value_pattern' => '$',
			'choice'        => '',
		);

		return wp_parse_args( $output, $defaults );
	}

	/**
	 * Build typography value
	 *
	 * @param array $value
	 *
	 * @return array
	 */
	protected static function get_typography_value( array $value ) {
		$valid_properties = array(
			'font-weight',
			'font-size',
			'line-height',
			'letter-spacing',
			'color',
			'text-transform',
			'text-align',
			'font-style',
		);

		$typography_value = array();
		foreach ( $value as $property => $property_value ) {
			// Early exit if the value is not saved in the values.
			if ( ! in_array( $property, $valid_properties ) ) {
				continue;
			}

			if ( ! empty( $property_value ) ) {
				$typography_value[ $property ] = $property_value;
			}
		}

		return $typography_value;
	}

	/**
	 * Get spacing value
	 *
	 * @param array  $value
	 * @param string $output_property
	 *
	 * @return array
	 */
	private static function get_spacing_value( array $value, $output_property ) {
		$spacing_list = array();

		foreach ( $value as $property => $property_value ) {
			if ( ! empty( $property_value ) ) {
				if ( ! in_array( $output_property, array( 'padding', 'margin' ) ) ) {
					continue;
				}
				if ( ! in_array( $property, array( 'top', 'right', 'bottom', 'left' ) ) ) {
					continue;
				}

				$spacing_list[ $output_property . '-' . $property ] = $property_value;
			}
		}

		return $spacing_list;
	}

	/**
	 * Get the CSS for a field.
	 *
	 * @static
	 * @access public
	 *
	 * @param array $css
	 * @param array $field The field.
	 * @param mixed $value
	 */
	public static function css( array &$css, array $field, $value ) {
		// Check if we need to exit early
		if ( ! is_array( $field ) ) {
			return;
		}

		// No need to process fields without an output, or an improperly-formatted output
		if ( ! isset( $field['output'] ) || ( isset( $field['output'] ) && ! is_array( $field['output'] ) ) ) {
			return;
		}

		// Field Type
		$type = isset( $field['type'] ) ? esc_attr( $field['type'] ) : 'text';

		// Get the default value of this field
		$default = isset( $field['default'] ) ? $field['default'] : '';
		$value   = ! empty( $value ) ? $value : $default;

		// start parsing the output arguments of the field
		foreach ( $field['output'] as $output ) {
			$output = static::parse_output_args( $output );

			// If element is an array, convert it to a string
			if ( is_array( $output['element'] ) ) {
				$output['element'] = array_unique( $output['element'] );
				sort( $output['element'] );
				$output['element'] = implode( ',', $output['element'] );
			}

			// If field type typography and value is array
			if ( is_array( $value ) && 'typography' == $type ) {
				$value = static::get_typography_value( $value );
			}

			if ( is_array( $value ) && 'spacing' == $type ) {
				$value = static::get_spacing_value( $value, $output['property'] );
			}

			// If value is array and field is not typography
			if ( is_array( $value ) ) {
				foreach ( $value as $property => $property_value ) {
					if ( empty( $property_value ) ) {
						continue;
					}
					$css[ $output['media_query'] ][ $output['element'] ][ $property ] = $output['prefix'] . $property_value . $output['units'] . $output['suffix'];
				}
			}

			// if value is not array
			if ( ! is_array( $value ) ) {
				$value = str_replace( '$', $value, $output['value_pattern'] );
				if ( ! empty( $output['element'] ) && ! empty( $output['property'] ) ) {
					$css[ $output['media_query'] ][ $output['element'] ][ $output['property'] ] = $output['prefix'] . $value . $output['units'] . $output['suffix'];
				}
			}
		}
	}

	/**
	 * Gets the array of generated styles and creates the minimized, inline CSS.
	 *
	 * @static
	 * @access public
	 *
	 * @param array $css The CSS definitions array.
	 *
	 * @return string    The generated CSS.
	 */
	public static function styles_parse( $css = array() ) {
		$final_css = '';

		if ( ! is_array( $css ) || empty( $css ) ) {
			return $final_css;
		}

		// Parse the generated CSS array and create the CSS string for the output.
		foreach ( $css as $media_query => $styles ) {
			// Handle the media queries
			$final_css .= ( 'global' != $media_query ) ? $media_query . '{' . PHP_EOL : '';
			foreach ( $styles as $style => $style_array ) {
				$final_css .= $style . '{';
				foreach ( $style_array as $property => $value ) {
					$value = (string) $value;
					// Make sure background-images are properly formatted
					if ( 'background-image' == $property ) {
						if ( false === strrpos( $value, 'url(' ) ) {
							$value = 'url("' . esc_url_raw( $value ) . '")';
						}
					}

					$final_css .= $property . ':' . $value . ';';
				}
				$final_css .= '}' . PHP_EOL;
			}
			$final_css .= ( 'global' != $media_query ) ? '}' : '';
		}

		return $final_css;
	}

	/**
	 * Minify CSS
	 *
	 * @param string $content
	 * @param bool   $newline
	 *
	 * @return string
	 */
	public static function minify_css( $content, $newline = true ) {
		// Strip comments
		$content = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content );
		// remove leading & trailing whitespace
		$content = preg_replace( '/^\s*/m', '', $content );
		$content = preg_replace( '/\s*$/m', '', $content );

		// replace newlines with a single space
		$content = preg_replace( '/\s+/', ' ', $content );

		// remove whitespace around meta characters
		// inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex
		$content = preg_replace( '/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content );
		$content = preg_replace( '/([\[(:])\s+/', '$1', $content );
		$content = preg_replace( '/\s+([\]\)])/', '$1', $content );
		$content = preg_replace( '/\s+(:)(?![^\}]*\{)/', '$1', $content );

		// whitespace around + and - can only be stripped inside some pseudo-
		// classes, like `:nth-child(3+2n)`
		// not in things like `calc(3px + 2px)`, shorthands like `3px -2px`, or
		// selectors like `div.weird- p`
		$pseudos = array( 'nth-child', 'nth-last-child', 'nth-last-of-type', 'nth-of-type' );
		$content = preg_replace(
			'/:(' . implode( '|', $pseudos ) . ')\(\s*([+-]?)\s*(.+?)\s*([+-]?)\s*(.*?)\s*\)/',
			':$1($2$3$4$5)',
			$content
		);

		// remove semicolon/whitespace followed by closing bracket
		$content = str_replace( ';}', '}', $content );

		// Add new line after closing bracket
		if ( $newline ) {
			$content = str_replace( '}', '}' . PHP_EOL, $content );
		}

		return trim( $content );
	}
}

