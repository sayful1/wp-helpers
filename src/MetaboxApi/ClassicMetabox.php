<?php

namespace Stackonet\WP\Framework\MetaboxApi;

use Stackonet\WP\Framework\Fields\BaseField;
use Stackonet\WP\Framework\Fields\CheckboxMulti;
use Stackonet\WP\Framework\Fields\CheckboxSwitch;
use Stackonet\WP\Framework\Fields\CheckboxTrueFalse;
use Stackonet\WP\Framework\Fields\Color;
use Stackonet\WP\Framework\Fields\Html;
use Stackonet\WP\Framework\Fields\ImagesGallery;
use Stackonet\WP\Framework\Fields\ImageUploader;
use Stackonet\WP\Framework\Fields\Radio;
use Stackonet\WP\Framework\Fields\RadioButton;
use Stackonet\WP\Framework\Fields\Select;
use Stackonet\WP\Framework\Fields\SelectImageSize;
use Stackonet\WP\Framework\Fields\SelectPosts;
use Stackonet\WP\Framework\Fields\SelectSidebar;
use Stackonet\WP\Framework\Fields\SelectTerms;
use Stackonet\WP\Framework\Fields\Spacing;
use Stackonet\WP\Framework\Fields\Text;
use Stackonet\WP\Framework\Fields\Textarea;
use Stackonet\WP\Framework\Interfaces\FieldInterface;
use Stackonet\WP\Framework\Supports\Sanitize;
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
	 * Metabox field name
	 *
	 * @var string
	 */
	protected $option_name = '_shapla_page_options';

	/**
	 * Shapla_Metabox constructor.
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 3 );
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

		if ( isset( $_POST[ $this->option_name ] ) ) {
			update_post_meta( $post_id, $this->option_name, Sanitize::deep( $_POST[ $this->option_name ] ) );

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

		add_action(
			'add_meta_boxes',
			function ( $post_type ) {
				$config = $this->get_config();
				add_meta_box(
					$config['id'],
					$config['title'],
					array( $this, 'meta_box_callback' ),
					$post_type,
					$config['context'],
					$config['priority'],
					$this->fields
				);
			}
		);

		return true;
	}

	/**
	 * Init meta boxes
	 *
	 * @return void
	 */
	public function init() {
		add_action(
			'add_meta_boxes',
			function ( $post_type ) {
				$config = $this->get_config();
				add_meta_box(
					$config['id'],
					$config['title'],
					array( $this, 'meta_box_callback' ),
					$post_type,
					$config['context'],
					$config['priority'],
					$this->fields
				);
			}
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

		$values = (array) get_post_meta( $post->ID, $this->option_name, true );

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
		$html = '<ul class="shapla-tabs-list">';
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

			$name  = $this->option_name . '[' . $field['id'] . ']';
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
			'checkbox'       => CheckboxMulti::class,
			'switch'         => CheckboxSwitch::class,
			'true_false'     => CheckboxTrueFalse::class,
			'color'          => Color::class,
			'html'           => Html::class,
			'images_gallery' => ImagesGallery::class,
			'upload_iframe'  => ImageUploader::class,
			'radio'          => Radio::class,
			'radio_button'   => RadioButton::class,
			'select'         => Select::class,
			'image_sizes'    => SelectImageSize::class,
			'posts_list'     => SelectPosts::class,
			'post_terms'     => SelectTerms::class,
			'sidebars'       => SelectSidebar::class,
			'spacing'        => Spacing::class,
			'text'           => Text::class,
			'textarea'       => Textarea::class,
		];

		$class = array_key_exists( $type, $types ) ? $types[ $type ] : $types['text'];

		return new $class();
	}
}
