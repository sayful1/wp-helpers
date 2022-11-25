<?php

namespace Stackonet\WP\Framework\Media;

use BadMethodCallException;
use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class Uploader
 *
 * @method static int|WP_Error uploadSingleFile( UploadedFile $file, ?string $dir = null, ?string $filename = null )
 * @method static string|WP_Error uploadFile( UploadedFile $file, string $directory, ?string $filename = null )
 *
 * @package Stackonet\WP\Framework\Media
 */
class Uploader {
	/**
	 * Upload attachments
	 *
	 * @param UploadedFile[]|UploadedFile $file Array of UploadedFile or UploadedFile object.
	 * @param string|null                 $dir The directory where the file will be uploaded.
	 *
	 * @return array
	 */
	public static function upload( $file, ?string $dir = null ): array {
		$attachments = [];

		$files = $file instanceof UploadedFile ? [ $file ] : $file;
		if ( ! is_array( $files ) ) {
			return $attachments;
		}

		foreach ( $files as $uploaded_file ) {
			if ( ! $uploaded_file instanceof UploadedFile ) {
				continue;
			}
			$id = self::upload_single_file( $uploaded_file, $dir );

			$attachments[]['attachment_id'] = is_wp_error( $id ) ? 0 : $id;
		}

		return array_filter( $attachments );
	}

	/**
	 * Upload attachment
	 *
	 * @param UploadedFile $file The uploaded UploadedFile object.
	 * @param string|null  $dir The directory where to upload file.
	 * @param string|null  $filename The filename.
	 *
	 * @return int|WP_Error Media id on success.
	 */
	public static function upload_single_file( UploadedFile $file, ?string $dir = null, ?string $filename = null ) {
		// Check if upload directory is writable.
		$upload_dir = static::get_upload_dir( $dir );
		if ( is_wp_error( $upload_dir ) ) {
			return $upload_dir;
		}

		// Upload file to upload directory.
		$file_path = static::upload_file( $file, $upload_dir, $filename );
		if ( is_wp_error( $file_path ) ) {
			Logger::log( $file_path );

			return $file_path;
		}

		return static::add_attachment_data( $file, $file_path );
	}

	/**
	 * Upload a file
	 *
	 * @param UploadedFile $file The uploaded UploadedFile object.
	 * @param string       $directory The directory where to upload file.
	 * @param string|null  $filename The filename for the uploaded file.
	 *
	 * @return string|WP_Error Uploaded file full path
	 */
	public static function upload_file( UploadedFile $file, string $directory, ?string $filename = null ) {
		if ( $file->get_size() > wp_max_upload_size() ) {
			return new WP_Error( 'large_file_size', 'File size too large.' );
		}

		if ( ! in_array( $file->get_mime_type(), get_allowed_mime_types(), true ) ) {
			return new WP_Error( 'invalid_file_format', 'Invalid file format.' );
		}

		// Check file has no error.
		if ( $file->get_error() !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'invalid_file', 'File not valid.' );
		}

		try {
			if ( empty( $filename ) ) {
				$filename = wp_unique_filename( $directory, $file->get_client_filename() );
			}

			$new_file = $file->move_uploaded_file( $directory, $filename );

			// Set correct file permissions.
			$stat  = stat( dirname( $new_file ) );
			$perms = $stat['mode'] & 0000666;
			chmod( $new_file, $perms );

			return $new_file;
		} catch ( Exception $exception ) {
			return new WP_Error( 'upload_error', $exception->getMessage() );
		}
	}

	/**
	 * Add attachment data
	 *
	 * @param UploadedFile $file The uploaded UploadedFile object.
	 * @param string       $file_path The uploaded file path.
	 *
	 * @return int|WP_Error
	 */
	protected static function add_attachment_data( UploadedFile $file, string $file_path ) {
		$upload_dir = wp_upload_dir();
		$data       = [
			'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path ),
			'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_text_field( $file->get_client_filename() ) ),
			'post_status'    => 'inherit',
			'post_mime_type' => $file->get_mime_type(),
		];

		$attachment_id = wp_insert_attachment( $data, $file_path );

		if ( ! is_wp_error( $attachment_id ) ) {
			// Make sure that this file is included, as wp_read_video_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/media.php';
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
			wp_update_attachment_metadata( $attachment_id, $attach_data );
		}

		return $attachment_id;
	}

	/**
	 * Get file upload directory
	 *
	 * @param null|string $dir The directory inside uploads directory.
	 *
	 * @return string|WP_Error
	 */
	public static function get_upload_dir( ?string $dir = null ) {
		if ( empty( $dir ) ) {
			$dir = gmdate( 'Y/m', time() );
		}

		$upload_dir = wp_upload_dir();
		$media_dir  = join( DIRECTORY_SEPARATOR, array( $upload_dir['basedir'], $dir ) );

		// Make attachment directory in upload directory if not already exists.
		if ( ! file_exists( $media_dir ) ) {
			wp_mkdir_p( $media_dir );
		}

		// Check if attachment directory is writable.
		if ( ! wp_is_writable( $media_dir ) ) {
			return new WP_Error( 'directory_not_writable', 'Upload directory is not writable.' );
		}

		return $media_dir;
	}

	/**
	 * Convert camel case string to snake case
	 *
	 * @param string $camel method name in camel case.
	 *
	 * @return string
	 */
	private static function camel_to_snake( string $camel ): string {
		$snake = preg_replace_callback(
			'/[A-Z]/',
			function ( $match ) {
				return '_' . strtolower( $match[0] );
			},
			$camel
		);

		return ltrim( $snake, '_' );
	}

	/**
	 * Handle camel case method name calling
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $arguments An enumerated array containing the parameters passed to the $name'ed method.
	 *
	 * @return mixed
	 * @throws BadMethodCallException It throws exception if $name'ed method is not available.
	 */
	public static function __callStatic( string $name, array $arguments ) {
		$new_method = static::camel_to_snake( $name );
		if ( method_exists( __CLASS__, $new_method ) ) {
			return static::$new_method( ...$arguments );
		}

		throw new BadMethodCallException( sprintf( 'Method %s is not available', $name ) );
	}
}
