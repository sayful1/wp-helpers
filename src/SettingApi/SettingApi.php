<?php

namespace Stackonet\WP\Framework\SettingApi;

use Stackonet\WP\Framework\Supports\Sanitize;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Setting API class
 * This class is used to get, set, update settings panels, sections, fields
 */
class SettingApi {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Settings options array
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * Settings menu fields array
	 *
	 * @var array
	 */
	protected $menu_fields = [];

	/**
	 * Settings fields array
	 *
	 * @var array
	 */
	protected $fields = [];

	/**
	 * Settings tabs array
	 *
	 * @var array
	 */
	protected $panels = [];

	/**
	 * Settings sections array
	 *
	 * @var array
	 */
	protected $sections = [];

	/**
	 * Option name
	 *
	 * @var string
	 */
	protected $option_name = null;

	/**
	 * The only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add new admin menu
	 *
	 * This method is accessible outside the class for creating menu
	 *
	 * @param array $menu_fields {
	 *    An array of menu fields.
	 *
	 * @type string $page_title The text to be displayed in the title tags of the page when the menu is selected.
	 * @type string $menu_title The text to be used for the menu.
	 * @type string $menu_slug The slug name to refer to this menu by (should be unique for this menu).
	 * @type string $capability The capability required for this menu to be displayed to the user.
	 * @type string $parent_slug The slug name for the parent menu (or the file name of a standard WordPress admin page).
	 * @type string $option_name The option name for the menu.
	 * }
	 *
	 * @return WP_Error|SettingApi
	 */
	public function add_menu( array $menu_fields ) {
		if ( ! isset( $menu_fields['page_title'], $menu_fields['menu_title'], $menu_fields['menu_slug'] ) ) {
			return new WP_Error( 'field_not_set', 'Required key is not set properly for creating menu.' );
		}

		$this->menu_fields = $menu_fields;

		if ( ! empty( $menu_fields['option_name'] ) ) {
			$this->set_option_name( $menu_fields['option_name'] );
		}

		return $this;
	}

	/**
	 * Sanitize the option value
	 *
	 * @param array $input User raw input.
	 *
	 * @return array
	 */
	public function sanitize_options( array $input ): array {
		$output_array = array();
		$fields       = $this->get_fields();
		$options      = $this->get_options();
		foreach ( $fields as $field ) {
			$key     = $field['id'] ?? null;
			$default = $field['default'] ?? null;
			$type    = $field['type'] ?? 'text';
			$value   = $input[ $field['id'] ] ?? $options[ $field['id'] ];

			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				if ( isset( $field['multiple'] ) && is_array( $value ) ) {
					foreach ( $value as $item ) {
						if ( in_array( $item, array_keys( $field['options'] ) ) ) {
							$output_array[ $key ][] = $item;
						}
					}
				} else {
					$output_array[ $key ] = in_array( $value, array_keys( $field['options'] ) ) ? $value : $default;
				}
				continue;
			}

			if ( 'checkbox' === $type ) {
				$output_array[ $key ] = Validate::checked( $value ) ? 1 : 0;
				continue;
			}

			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$output_array[ $key ] = call_user_func( $field['sanitize_callback'], $value );
				continue;
			}

			$output_array[ $key ] = $this->sanitize_by_input_type( $value, $field['type'] );
		}

		return $output_array;
	}

	/**
	 * Validate the option's value
	 *
	 * @param mixed  $value The value to validate.
	 * @param string $type The type of the value.
	 *
	 * @return string|numeric
	 */
	private function sanitize_by_input_type( $value, string $type = 'text' ) {
		switch ( $type ) {
			case 'number':
				return Sanitize::number( $value );
			case 'url':
				return Sanitize::url( $value );
			case 'email':
				return Sanitize::email( $value );
			case 'date':
				return Sanitize::date( $value );
			case 'textarea':
				return Sanitize::textarea( $value );
			case 'text':
				return Sanitize::text( $value );
			default:
				return Sanitize::deep( $value );
		}
	}

	/**
	 * Get fields default values
	 *
	 * @return array
	 */
	public function get_default_options(): array {
		$defaults = array();

		foreach ( $this->get_fields() as $field ) {
			$defaults[ $field['id'] ] = $field['default'] ?? '';
		}

		return $defaults;
	}

	/**
	 * Get options parsed with default value
	 *
	 * @return array
	 */
	public function get_options(): array {
		if ( empty( $this->options ) ) {
			$defaults      = $this->get_default_options();
			$options       = get_option( $this->get_option_name() );
			$this->options = wp_parse_args( $options, $defaults );
		}

		return $this->options;
	}

	/**
	 * Update options
	 *
	 * @param array $options The options to update.
	 * @param bool  $sanitize Whether to sanitize the options or not.
	 */
	public function update_options( array $options, bool $sanitize = true ) {
		if ( $sanitize ) {
			$options = $this->sanitize_options( $options );
		}
		update_option( $this->get_option_name(), $options );
	}

	/**
	 * Get settings panels
	 *
	 * @return array
	 */
	public function get_panels(): array {
		// Sort by priority.
		usort( $this->panels, [ $this, 'sort_by_priority' ] );

		return $this->panels;
	}

	/**
	 * Set panels
	 *
	 * @param array $panels The panels to set.
	 *
	 * @return self
	 */
	public function set_panels( array $panels ): SettingApi {
		foreach ( $panels as $panel ) {
			$this->set_panel( $panel );
		}

		return $this;
	}

	/**
	 * Get settings sections
	 *
	 * @return array
	 */
	public function get_sections(): array {
		// Sort by priority.
		usort( $this->sections, [ $this, 'sort_by_priority' ] );

		return $this->sections;
	}

	/**
	 * Set sections
	 *
	 * @param array $sections The sections to set.
	 *
	 * @return self
	 */
	public function set_sections( array $sections ): SettingApi {
		foreach ( $sections as $section ) {
			$this->set_section( $section );
		}

		return $this;
	}

	/**
	 * Get settings fields
	 *
	 * @return array
	 */
	public function get_fields(): array {
		// Sort by priority.
		usort( $this->fields, [ $this, 'sort_by_priority' ] );

		return $this->fields;
	}

	/**
	 * Set fields
	 *
	 * @param array $fields The fields to set.
	 *
	 * @return self
	 */
	public function set_fields( array $fields ): SettingApi {
		foreach ( $fields as $field ) {
			$this->set_field( $field );
		}

		return $this;
	}

	/**
	 * Add setting page tab
	 *
	 * This method is accessible outside the class for creating page tab
	 *
	 * @param array $panel The panel to add.
	 *
	 * @return self
	 */
	public function set_panel( array $panel ): SettingApi {
		$panel = wp_parse_args(
			$panel,
			array(
				'id'          => '',
				'title'       => '',
				'description' => '',
				'priority'    => 200,
			)
		);

		$this->panels[] = $panel;

		return $this;
	}

	/**
	 * Add Setting page section
	 *
	 * @param array $section The section to add.
	 *
	 * @return self
	 */
	public function set_section( array $section ): SettingApi {
		$section = wp_parse_args(
			$section,
			array(
				'id'          => 'general',
				'panel'       => '',
				'title'       => '',
				'description' => '',
				'priority'    => 200,
			)
		);

		$this->sections[] = $section;

		return $this;
	}

	/**
	 * Add new settings field
	 * This method is accessible outside the class for creating settings field
	 *
	 * @param array $field The field to add.
	 *
	 * @return self
	 */
	public function set_field( array $field ): SettingApi {
		$field = wp_parse_args(
			$field,
			array(
				'type'        => 'text',
				'section'     => 'general',
				'id'          => '',
				'title'       => '',
				'description' => '',
				'priority'    => 200,
			)
		);

		$this->fields[ $field['id'] ] = $field;

		return $this;
	}

	/**
	 * Sort array by its priority field
	 *
	 * @param array $array1 The first array to compare.
	 * @param array $array2 The second array to compare.
	 *
	 * @return mixed
	 */
	public function sort_by_priority( array $array1, array $array2 ) {
		// Sort by priority. if priority is same, sort by title.
		if ( $array1['priority'] === $array2['priority'] ) {
			return strcmp( $array1['title'], $array2['title'] );
		}
		return $array1['priority'] - $array2['priority'];
	}

	/**
	 * Get option name
	 *
	 * @return string
	 */
	public function get_option_name(): ?string {
		if ( ! empty( $this->menu_fields['option_name'] ) ) {
			return $this->menu_fields['option_name'];
		}

		return $this->option_name;
	}

	/**
	 * Set option name
	 *
	 * @param string $option_name The option name to set.
	 *
	 * @return SettingApi
	 */
	public function set_option_name( string $option_name ): SettingApi {
		$this->option_name = $option_name;

		return $this;
	}
}
