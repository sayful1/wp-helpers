<?php

namespace Stackonet\WP\Examples\WordPressCore;

use Stackonet\WP\Framework\Abstracts\PostTypeModel;
use Stackonet\WP\Framework\Fields\FieldType;
use Stackonet\WP\Framework\MetaboxApi\ClassicMetabox;

/**
 * MetaboxTest class
 */
class MetaboxTest {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'init', [ self::$instance, 'register_post_type' ] );
		}

		return self::$instance;
	}

	public function register_post_type() {
		$class = new class extends PostTypeModel {
			const POST_TYPE = 'metabox-test';
		};
		register_post_type(
			$class::POST_TYPE,
			$class::get_post_type_args( 'Metabox Test', 'Posts', 'Post', [
				'show_in_rest' => false,
				'supports'     => [ 'title' ],
			] )
		);

		$this->add_meta_boxes();
	}

	/**
	 * Adds the meta box container.
	 */
	public function add_meta_boxes() {
		$options = [
			'id'       => 'meta-box-test',
			'title'    => __( 'Custom Fields Test' ),
			'screen'   => [ 'metabox-test' ],
			'context'  => 'normal',
			'priority' => 'low',
		];

		$panels = [
			[ 'id' => 'panel_1', 'title' => 'Panel 1' ],
			[ 'id' => 'panel_2', 'title' => 'Panel 2' ],
		];

		$fields = [
			'checkbox_multi_test'      => [
				'type'    => FieldType::CHECKBOX,
				'id'      => 'checkbox_multi_test',
				'label'   => __( 'Checkbox multiple' ),
				'panel'   => 'panel_1',
				'default' => [ 'default', 'full-width' ],
				'choices' => [
					'default'       => __( 'Default' ),
					'left-sidebar'  => __( 'Left' ),
					'right-sidebar' => __( 'Right' ),
					'full-width'    => __( 'Disabled' ),
				],
			],
			'checkbox_switch_test'     => [
				'type'        => FieldType::CHECKBOX_SWITCH,
				'id'          => 'switch_test',
				'label'       => __( 'Checkbox switch test' ),
				'subtitle'    => __( 'Discourage search engines from indexing this site' ),
				'description' => __( 'It is up to search engines to honor this request.' ),
				'panel'       => 'panel_1',
			],
			'checkbox_true_false_test' => [
				'type'        => FieldType::CHECKBOX_ACCEPTANCE,
				'id'          => 'true_false_test',
				'label'       => __( 'Search engine visibility' ),
				'subtitle'    => __( 'Discourage search engines from indexing this site' ),
				'description' => __( 'It is up to search engines to honor this request.' ),
				'panel'       => 'panel_1',
				'default'     => 'off',
			],
			'color_test'               => [
				'type'    => FieldType::COLOR,
				'id'      => 'color_test',
				'label'   => __( 'Color test' ),
				'panel'   => 'panel_1',
				'default' => '#ffffff',
			],
			'html_test'                => [
				'type'  => FieldType::HTML,
				'id'    => 'html_test',
				'label' => __( 'HTML test' ),
				'panel' => 'panel_1',
				'html'  => '<p>HTML test</p>',
			],
			'images_gallery_test'      => [
				'type'    => FieldType::IMAGE,
				'gallery' => true,
				'id'      => 'images_gallery_test',
				'label'   => __( 'Image gallery' ),
				'panel'   => 'panel_1',
			],
			'upload_iframe_test'       => [
				'type'  => FieldType::IMAGE,
				'id'    => 'upload_iframe_test',
				'label' => __( 'Image' ),
				'panel' => 'panel_1',
			],
			'radio_test'               => [
				'type'    => FieldType::RADIO,
				'id'      => 'radio_test',
				'label'   => __( 'Radio test' ),
				'panel'   => 'panel_1',
				'default' => 'default',
				'choices' => [
					'default'       => __( 'Default' ),
					'left-sidebar'  => __( 'Left' ),
					'right-sidebar' => __( 'Right' ),
					'full-width'    => __( 'Disabled' ),
				],
			],
			'radio_button_test'        => [
				'type'        => FieldType::RADIO_BUTTON,
				'id'          => 'button_group_test',
				'label'       => __( 'Content Width' ),
				'description' => __( '"100% Width" will take all screen width. On block editor, only certain blocks takes 100% width.' ),
				'panel'       => 'panel_1',
				'default'     => 'site-width',
				'choices'     => [
					'site-width' => __( 'Site Width' ),
					'full-width' => __( '100% Width' ),
				],
			],
			'select_test'              => [
				'type'        => FieldType::SELECT,
				'id'          => 'select_test',
				'label'       => __( 'Sidebar Position' ),
				'description' => __( 'Controls sidebar position for current page.' ),
				'panel'       => 'panel_1',
				'default'     => 'default',
				'choices'     => [
					'default'       => __( 'Default' ),
					'left-sidebar'  => __( 'Left' ),
					'right-sidebar' => __( 'Right' ),
					'full-width'    => __( 'Disabled' ),
				],
			],
			'select_image_sizes_test'  => [
				'type'        => FieldType::SELECT_IMAGE_SIZE,
				'id'          => 'select_image_sizes_test',
				'label'       => __( 'Image Sizes' ),
				'description' => __( 'Select image sizes for current page.' ),
				'panel'       => 'panel_1',
			],
			'posts_list_test'          => [
				'type'        => FieldType::SELECT_POSTS_LIST,
				'id'          => 'posts_list_test',
				'label'       => __( 'Posts List' ),
				'description' => __( 'Select posts for current page.' ),
				'panel'       => 'panel_1',
			],
			'post_terms_test'          => [
				'type'        => FieldType::SELECT_TERMS_LIST,
				'id'          => 'post_terms_test',
				'label'       => __( 'Post Terms' ),
				'description' => __( 'Select post terms for current page.' ),
				'panel'       => 'panel_1',
			],
			'sidebars_test'            => [
				'type'        => FieldType::SELECT_SIDEBARS,
				'id'          => 'sidebars_test',
				'label'       => __( 'Sidebar widget area' ),
				'description' => __( 'Controls sidebar widget area for current page.' ),
				'panel'       => 'panel_1',
			],
			'spacing_test'             => [
				'type'        => FieldType::SPACING,
				'id'          => 'spacing_test',
				'label'       => __( 'Content Padding' ),
				'description' => __( 'Leave empty to use value from theme options.' ),
				'panel'       => 'panel_1',
				'default'     => [
					'top'    => '',
					'bottom' => '',
				],
			],
			'text_test'                => [
				'type'  => FieldType::TEXT,
				'id'    => 'text_test',
				'label' => 'Text field',
				'panel' => 'panel_2',
			],
			'date_test'                => [
				'type'  => 'date',
				'id'    => 'date_test',
				'label' => 'Date field',
				'panel' => 'panel_2',
			],
			'textarea_test'            => [
				'type'  => FieldType::TEXTAREA,
				'id'    => 'textarea_test',
				'label' => 'Textarea field',
				'panel' => 'panel_2',
			],
		];

		$metabox = new ClassicMetabox();
		$metabox->set_config( $options );
		$metabox->set_panels( $panels );
		$metabox->set_fields( $fields );

		$metabox->init();
	}
}