<?php

namespace Stackonet\WP\Framework\Fields;

/**
 * Class FieldType
 *
 * @package Stackonet\WP\Framework\MetaboxApi
 */
class FieldType {
	const TEXTAREA            = 'textarea';
	const SELECT              = 'select';
	const SELECT_IMAGE_SIZE   = 'image_sizes';
	const SELECT_POSTS_LIST   = 'posts_list';
	const SELECT_TERMS_LIST   = 'post_terms';
	const SELECT_SIDEBARS     = 'sidebars';
	const RADIO               = 'radio';
	const RADIO_BUTTON        = 'radio_button';
	const CHECKBOX            = 'checkbox';
	const CHECKBOX_SWITCH     = 'switch';
	const CHECKBOX_ACCEPTANCE = 'acceptance';
	const COLOR               = 'color';
	const IMAGE               = 'image';
	const IMAGE_GALLERY       = 'images_gallery';
	const HTML                = 'html';
	const SPACING             = 'spacing';
	const TEXT                = 'text';
	const NUMBER              = 'number';
	const DATE                = 'date';
	const TIME                = 'time';
	const DATETIME            = 'datetime';

	/**
	 * Fields that cannot save value
	 */
	const GUARDED = [ self::HTML ];
}
