<?php

namespace Stackonet\WP\Framework\MetaboxApi;

use Stackonet\WP\Framework\Supports\CssGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * MetaboxApi class
 */
class MetaboxApi {

	/**
	 * Metabox field name
	 *
	 * @var string
	 */
	protected $option_name = '_single_post_settings';

	/**
	 * Metabox config
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Metabox panels
	 *
	 * @var array
	 */
	protected $panels = [];

	/**
	 * Metabox sections
	 *
	 * @var array
	 */
	protected $sections = [];

	/**
	 * Metabox fields
	 *
	 * @var array
	 */
	protected $fields = [];

	/**
	 * Get metabox configuration
	 *
	 * @return array
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Set metabox config
	 *
	 * @param array $config Metabox config.
	 *
	 * @return static
	 */
	public function set_config( array $config ) {
		$default = array(
			'id'       => 'shapla_meta_box_options',
			'title'    => 'Page options',
			'screen'   => 'page',
			'context'  => 'advanced',
			'priority' => 'low',
		);
		foreach ( $default as $key => $value ) {
			$this->config[ $key ] = $config[ $key ] ?? $value;
		}

		return $this;
	}

	/**
	 * Get sections by panel
	 *
	 * @param string $panel Panel ID.
	 *
	 * @return array
	 */
	public function get_sections_by_panel( string $panel ): array {
		$sections = [];
		foreach ( $this->get_sections() as $section ) {
			if ( $section['panel'] === $panel ) {
				$sections[] = $section;
			}
		}

		return $sections;
	}

	/**
	 * Get fields by section
	 *
	 * @param string $section Section ID.
	 *
	 * @return array
	 */
	public function get_fields_by_section( string $section ): array {
		$current_field = [];
		foreach ( $this->get_fields() as $field ) {
			if ( $field['section'] === $section ) {
				$current_field[] = $field;
			}
		}

		return $current_field;
	}

	/**
	 * Get fields by panel
	 *
	 * @param string $panel_id Panel ID.
	 *
	 * @return array
	 */
	public function get_fields_by_panel( string $panel_id ): array {
		$current_field = [];
		foreach ( $this->get_fields() as $field ) {
			if ( $field['panel'] === $panel_id ) {
				$current_field[] = $field;
			}
		}

		return $current_field;
	}

	/**
	 * If it has panels
	 *
	 * @return bool
	 */
	public function has_panels(): bool {
		return count( $this->panels ) > 0;
	}

	/**
	 * Get panels
	 *
	 * @return array
	 */
	public function get_panels(): array {
		return $this->sort_by_priority( $this->panels );
	}

	/**
	 * Set panels
	 *
	 * @param array $panels Array of panels
	 *
	 * @return static
	 */
	public function set_panels( array $panels ) {
		foreach ( $panels as $panel ) {
			$this->set_panel( $panel );
		}

		return $this;
	}

	/**
	 * Set panel
	 *
	 * @param array $options Panel options.
	 *
	 * @return static
	 */
	public function set_panel( array $options ) {
		$default        = array(
			'id'          => '',
			'title'       => '',
			'description' => '',
			'class'       => '',
			'priority'    => 200,
		);
		$this->panels[] = wp_parse_args( $options, $default );

		return $this;
	}

	/**
	 * Get section
	 *
	 * @return array
	 */
	public function get_sections(): array {
		return $this->sort_by_priority( $this->sections );
	}

	/**
	 * Set sections
	 *
	 * @param array $sections Array of sections
	 *
	 * @return static
	 */
	public function set_sections( array $sections ) {
		foreach ( $sections as $section ) {
			$this->set_section( $section );
		}

		return $this;
	}

	/**
	 * Set section
	 *
	 * @param array $options Section options.
	 *
	 * @return static
	 */
	public function set_section( array $options ) {
		$default          = array(
			'id'          => '',
			'title'       => '',
			'description' => '',
			'panel'       => '',
			'priority'    => 200,
		);
		$this->sections[] = wp_parse_args( $options, $default );

		return $this;
	}

	/**
	 * Get fields
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return $this->sort_by_priority( $this->fields );
	}

	/**
	 * Set fields
	 *
	 * @param array $fields Array of fields
	 *
	 * @return static
	 */
	public function set_fields( array $fields ) {
		foreach ( $fields as $field ) {
			$this->set_field( $field );
		}

		return $this;
	}

	/**
	 * Set field
	 *
	 * @param array $options Field options.
	 *
	 * @return static
	 */
	public function set_field( array $options ) {
		$default        = array(
			'type'        => 'text',
			'id'          => '',
			'section'     => 'default',
			'label'       => '',
			'description' => '',
			'priority'    => 200,
			'default'     => '',
		);
		$this->fields[] = wp_parse_args( $options, $default );

		return $this;
	}

	/**
	 * Gets all our styles for current page and returns them as a string.
	 *
	 * @return string
	 */
	public function get_styles(): string {
		global $post;
		$fields = $this->get_fields();

		// Check if we need to exit early.
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return '';
		}

		// initially we're going to format our styles as an array.
		// This is going to make processing them a lot easier
		// and make sure there are no duplicate styles etc.
		$css    = [];
		$values = get_post_meta( $post->ID, $this->option_name, true );

		// start parsing our fields.
		foreach ( $fields as $field ) {
			// If no setting id, then exist.
			if ( ! isset( $field['id'] ) ) {
				continue;
			}

			// Get the default value of this field.
			$default = $field['default'] ?? '';
			$value   = $values[ $field['id'] ] ?? $default;

			CssGenerator::css( $css, $field, $value );
		}

		return CssGenerator::styles_parse( $css );
	}

	/**
	 * Sort by priority
	 *
	 * @param array $array Array to sort.
	 *
	 * @return array
	 */
	protected function sort_by_priority( array $array ): array {
		$array_copy = $array;
		usort(
			$array_copy,
			function ( $a, $b ) {
				return $a['priority'] - $b['priority'];
			}
		);

		return $array_copy;
	}
}
