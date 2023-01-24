<?php

namespace Stackonet\WP\Framework\MetaboxApi;

use Stackonet\WP\Framework\Fields\BaseField;
use Stackonet\WP\Framework\Fields\CheckboxAcceptance;
use Stackonet\WP\Framework\Fields\CheckboxMulti;
use Stackonet\WP\Framework\Fields\CheckboxSwitch;
use Stackonet\WP\Framework\Fields\Color;
use Stackonet\WP\Framework\Fields\Date;
use Stackonet\WP\Framework\Fields\DateTime;
use Stackonet\WP\Framework\Fields\Email;
use Stackonet\WP\Framework\Fields\FieldType;
use Stackonet\WP\Framework\Fields\Html;
use Stackonet\WP\Framework\Fields\ImagesGallery;
use Stackonet\WP\Framework\Fields\ImageUploader;
use Stackonet\WP\Framework\Fields\Number;
use Stackonet\WP\Framework\Fields\Radio;
use Stackonet\WP\Framework\Fields\RadioButton;
use Stackonet\WP\Framework\Fields\Select;
use Stackonet\WP\Framework\Fields\SelectImageSize;
use Stackonet\WP\Framework\Fields\SelectPosts;
use Stackonet\WP\Framework\Fields\SelectSidebar;
use Stackonet\WP\Framework\Fields\SelectTerms;
use Stackonet\WP\Framework\Fields\Spacing;
use Stackonet\WP\Framework\Fields\Tel;
use Stackonet\WP\Framework\Fields\Text;
use Stackonet\WP\Framework\Fields\Textarea;
use Stackonet\WP\Framework\Fields\Time;
use Stackonet\WP\Framework\Fields\Url;
use Stackonet\WP\Framework\Interfaces\FieldInterface;
use WP_Error;
use WP_Post;

/**
 * ClassicMetabox class
 */
class ClassicMetabox extends MetaboxApi {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Shapla_Metabox constructor.
	 */
	public function __construct() {
		add_action( 'save_post', [ $this, 'save_meta_box' ], 10, 3 );
		add_action( 'admin_enqueue_scripts', [ $this, 'meta_box_style' ] );
		add_action( 'admin_print_footer_scripts', [ $this, 'meta_box_script' ], 90 );
	}

	/**
	 * Meta box style
	 */
	public function meta_box_style() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_media();
		if ( $this->has_panels() ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
		}
		if ( $this->has_color_field() ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}
	}

	/**
	 * Meta box script
	 *
	 * @return void
	 */
	public function meta_box_script() {
		?>
		<script>
			(function ($) {
				if ('function' === typeof $.fn.tabs) {
					$("#shapla-metabox-tabs").tabs();
				}
				if ('function' === typeof $.fn.wpColorPicker) {
					$('.color-picker').wpColorPicker();
				}
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated or not.
	 *
	 * @return void
	 */
	public function save_meta_box( $post_id, $post, $update ) {
		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Verify that the nonce is valid.
		$nonce = isset( $_POST['_shapla_nonce'] ) && wp_verify_nonce( $_POST['_shapla_nonce'], basename( __FILE__ ) );
		if ( ! $nonce ) {
			return;
		}

		// Check if user has permissions to save data.
		$capability = ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) ? 'edit_page' : 'edit_post';
		if ( ! current_user_can( $capability, $post_id ) ) {
			return;
		}

		do_action( 'shapla_before_save_post_meta', $post_id, $post, $update );

		if ( isset( $_POST[ $this->get_input_group() ] ) ) {
			$raw_values       = $_POST[ $this->get_input_group() ] ?? [];
			$sanitized_values = $this->sanitize( $raw_values );

			if ( $this->get_option_name() ) {
				update_post_meta( $post_id, $this->get_option_name(), $sanitized_values );
			} else {
				foreach ( $sanitized_values as $meta_key => $meta_value ) {
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}

			$styles = $this->get_styles();
			if ( ! empty( $styles ) ) {
				update_post_meta( $post_id, '_shapla_page_options_css', $styles );
			}
		}

		do_action( 'shapla_after_save_post_meta', $post_id, $post, $update );
	}

	/**
	 * Add metabox
	 *
	 * @param array $options Array of options.
	 *
	 * @return WP_Error|bool
	 */
	public function add( $options ) {
		if ( ! is_array( $options ) ) {
			return new WP_Error( 'invalid_options', 'Invalid options' );
		}

		if ( ! isset( $options['fields'] ) ) {
			return new WP_Error( 'fields_not_set', 'Field is not set properly.' );
		}

		$this->set_config( $options );

		if ( isset( $options['panels'] ) ) {
			$this->set_panels( $options['panels'] );
		}

		if ( isset( $options['sections'] ) ) {
			$this->set_sections( $options['sections'] );
		}

		$this->set_fields( $options['fields'] );

		$this->init();

		return true;
	}

	/**
	 * Init meta boxes
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
	}

	/**
	 * Add meta box
	 *
	 * @return void
	 */
	public function add_meta_box() {
		$config = $this->get_config();
		add_meta_box(
			$config['id'],
			$config['title'],
			[ $this, 'meta_box_callback' ],
			$config['screen'],
			$config['context'],
			$config['priority'],
			$this->fields
		);
	}

	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The object for the current post/page.
	 * @param array   $fields The metabox fields.
	 */
	public function meta_box_callback( $post, $fields ) {
		if ( ! is_array( $fields ) ) {
			return;
		}

		wp_nonce_field( basename( __FILE__ ), '_shapla_nonce' );

		$values = $this->get_option_name() ?
			(array) get_post_meta( $post->ID, $this->get_option_name(), true ) :
			[];

		if ( $this->has_panels() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_tab_content( $values );
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_html_for_section( $this->get_fields(), $values );
		}
	}

	/**
	 * Get tab navigation html
	 *
	 * @param array $values The values of the fields.
	 *
	 * @return string
	 */
	public function get_tab_content( array $values = [] ): string {
		$html  = '<div class="shapla-tabs-wrapper">';
		$html .= '<div id="shapla-metabox-tabs" class="shapla-tabs">';

		$html .= '<ul class="shapla-tabs-list">';
		foreach ( $this->get_panels() as $panel ) {
			$class = ! empty( $panel['class'] ) ? $panel['class'] : $panel['id'];

			$html .= '<li class="' . esc_attr( $class ) . '">';
			$html .= '<a href="#tab-' . esc_attr( $panel['id'] ) . '">';
			$html .= '<span>' . esc_html( $panel['title'] ) . '</span>';
			$html .= '</a>';
			$html .= '</li>';
		}
		$html .= '</ul>';

		foreach ( $this->get_panels() as $panel ) {
			$sections = $this->get_sections_by_panel( $panel['id'] );
			$fields   = $this->get_fields_by_panel( $panel['id'] );

			$html .= '<div id="tab-' . esc_attr( $panel['id'] ) . '" class="shapla_options_panel">';
			if ( count( $fields ) ) {
				$html .= $this->get_html_for_section( $fields, $values );
			}
			foreach ( $sections as $section ) {
				$html .= $this->get_html_for_section( $this->get_fields_by_section( $section['id'] ), $values );
			}
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get html for section
	 *
	 * @param array $fields Array of fields.
	 * @param array $values Array of values.
	 *
	 * @return string
	 */
	public function get_html_for_section( array $fields, array $values = [] ): string {
		global $post;
		$html = '<table class="form-table shapla-metabox-table">';
		foreach ( $fields as $field ) {

			$name  = $this->get_input_group() . '[' . $field['id'] . ']';
			$value = empty( $values[ $field['id'] ] ) ? $field['default'] : $values[ $field['id'] ];

			if ( ! isset( $values[ $field['id'] ] ) ) {
				$meta  = get_post_meta( $post->ID, $field['id'], true );
				$value = empty( $meta ) ? $field['default'] : $meta;
			}

			$html .= '<tr>';

			$html .= '<th>';

			$html .= '<label for="' . esc_attr( $field['id'] ) . '">';
			$html .= '<strong>' . esc_html( $field['label'] ) . '</strong>';
			$html .= '</label>';
			$html .= '</th>';

			$html .= '<td>';
			$html .= $this->render( $field, $name, $value );
			if ( ! empty( $field['description'] ) ) {
				$html .= '<p class="description">' . esc_html( $field['description'] ) . '</p>';
			}
			$html .= '</td>';

			$html .= '</tr>';
		}
		$html .= '</table>';

		return $html;
	}

	/**
	 * Sanitize user submitted values.
	 *
	 * @param array $raw_values User submitted values.
	 *
	 * @return array
	 */
	public function sanitize( array $raw_values ): array {
		$sanitized_values = [];
		foreach ( $this->get_fields() as $field ) {
			if ( in_array( $field['type'], FieldType::GUARDED, true ) ) {
				continue;
			}
			$raw_value = $raw_values[ $field['id'] ] ?? null;
			if ( $field['sanitize_callback'] && is_callable( $field['sanitize_callback'] ) ) {
				$sanitized_values[ $field['id'] ] = call_user_func(
					$field['sanitize_callback'],
					$raw_value
				);
			} else {
				$field_class                      = self::get_field_class( $field['type'] );
				$sanitized_values[ $field['id'] ] = $field_class->sanitize( $raw_value, $field );
			}
		}

		return $sanitized_values;
	}

	/**
	 * Render field
	 *
	 * @param array  $settings Field settings.
	 * @param string $name Field name.
	 * @param mixed  $value Field value.
	 *
	 * @return string
	 */
	public function render( array $settings, string $name, $value ): string {
		$field = static::get_field_class( $settings['type'] ?? 'text' );
		$field->set_settings( $settings );
		$field->set_name( $name );
		$field->set_value( $value );

		return $field->render();
	}

	/**
	 * Get field class
	 *
	 * @param string $type The field type.
	 *
	 * @return BaseField|FieldInterface
	 */
	private static function get_field_class( string $type = 'text' ): FieldInterface {
		$types = [
			FieldType::CHECKBOX            => CheckboxMulti::class,
			FieldType::CHECKBOX_SWITCH     => CheckboxSwitch::class,
			FieldType::CHECKBOX_ACCEPTANCE => CheckboxAcceptance::class,
			FieldType::COLOR               => Color::class,
			FieldType::HTML                => Html::class,
			FieldType::IMAGE               => ImageUploader::class,
			FieldType::IMAGE_GALLERY       => ImagesGallery::class,
			FieldType::RADIO               => Radio::class,
			FieldType::RADIO_BUTTON        => RadioButton::class,
			FieldType::SELECT              => Select::class,
			FieldType::SELECT_IMAGE_SIZE   => SelectImageSize::class,
			FieldType::SELECT_POSTS_LIST   => SelectPosts::class,
			FieldType::SELECT_TERMS_LIST   => SelectTerms::class,
			FieldType::SELECT_SIDEBARS     => SelectSidebar::class,
			FieldType::SPACING             => Spacing::class,
			FieldType::TEXT                => Text::class,
			FieldType::TEXTAREA            => Textarea::class,
			FieldType::NUMBER              => Number::class,
			FieldType::DATE                => Date::class,
			FieldType::TIME                => Time::class,
			FieldType::DATETIME            => DateTime::class,
			FieldType::EMAIL               => Email::class,
			FieldType::URL                 => Url::class,
			FieldType::TELEPHONE           => Tel::class,
		];

		$class = array_key_exists( $type, $types ) ? $types[ $type ] : $types['text'];

		return new $class();
	}
}
