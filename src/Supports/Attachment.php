<?php

namespace Stackonet\WP\Framework\Supports;

use Stackonet\WP\Framework\Media\Uploader;

defined( 'ABSPATH' ) || exit;

/**
 * Class Attachment
 * This class is just to keep backward compatibility
 */
class Attachment extends Uploader {

	/**
	 * Upload a file
	 *
	 * @param UploadedFile[]|UploadedFile $file Array of UploadedFile or UploadedFile object.
	 * @param string|null                 $dir The directory where the file will be uploaded.
	 *
	 * @inheritDoc
	 */
	public static function upload( $file, ?string $dir = null ): array {
		_deprecated_function( __FUNCTION__, '1.1.4', '\Stackonet\WP\Framework\Media\Uploader::upload()' );

		return parent::upload( $file, $dir );
	}
}
