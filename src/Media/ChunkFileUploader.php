<?php

namespace Stackonet\WP\Framework\Media;

use Stackonet\WP\Framework\Supports\Logger;
use WP_Error;

/**
 * ChunkFileUploader class
 */
class ChunkFileUploader {
	/**
	 * Upload file by chunk
	 *
	 * @param UploadedFile $file The file object.
	 * @param array        $additional_info {
	 *      Array of additional parameters.
	 *
	 * @type int $chunks Total number of chunks
	 * @type int $chunk Chunk number. Starts from 0
	 * @type string $name File name
	 * }
	 *
	 * @return WP_Error|UploadedFile|int WP_Error on failure. Returns 0 when a chunk is uploaded successfully.
	 * UploadedFile object when all chunks are uploaded completely.
	 */
	public static function upload( UploadedFile $file, array $additional_info = [] ) {
		$additional_info = wp_parse_args(
			$additional_info,
			[
				'chunk'  => 0,
				'chunks' => 0,
				'name'   => '',
			]
		);

		/** Check and get file chunks. */
		$chunks       = isset( $additional_info['chunks'] ) ? intval( $additional_info['chunks'] ) : 0;
		$chunk        = isset( $additional_info['chunk'] ) ? intval( $additional_info['chunk'] ) : 0; // zero index.
		$current_part = $chunk + 1;

		/** Get file name and path + name. */
		$file_name = ! empty( $additional_info['name'] ) ? $additional_info['name'] : $file->get_client_filename();

		// Temp upload directory.
		$temp_dir = self::get_temp_upload_directory();
		// only run on first chunk.
		if ( 0 === $chunk ) {
			// Create temp directory if it doesn't exist.
			self::maybe_created_temp_directory( $temp_dir );

			// scan temp dir for files older than 24 hours and delete them.
			self::delete_older_files( $temp_dir );
		}

		$temp_filepath = sprintf( '%s/%d-%s.part', $temp_dir, get_current_blog_id(), sha1( $file_name ) );

		// debugging.
		if ( self::is_debugging_enabled() ) {
			$size = file_exists( $temp_filepath ) ? size_format( filesize( $temp_filepath ), 3 ) : '0 B';
			Logger::log( "Big File Uploader: Processing \"$file_name\" part $current_part of $chunks as $temp_filepath. $size processed so far." );
		}

		$debug_info = [
			'filename' => $file_name,
			'chunk'    => $current_part,
			'chunks'   => $chunks,
		];

		$max_size = static::get_max_upload_size();
		if ( file_exists( $temp_filepath ) && ( filesize( $temp_filepath ) + $file->get_size() ) > $max_size ) {
			return new WP_Error(
				'filesize_limit_exceeded',
				'The file size has exceeded the maximum file size setting.',
				$debug_info
			);
		}

		// Open temp file.
		if ( 0 === $chunk ) {
			$out = @fopen( $temp_filepath, 'wb' ); // phpcs:ignore
		} elseif ( is_writable( $temp_filepath ) ) {
			$out = @fopen( $temp_filepath, 'ab' ); // phpcs:ignore
		} else {
			$out = false;
		}

		if ( ! $out ) {
			return new WP_Error(
				'fail_to_open_temp_file',
				sprintf( 'There was an error opening the temp file %s for writing.', esc_html( $temp_filepath ) ),
				$debug_info
			);
		}

		/** Read binary input stream and append it to temp file. */
		$in = @fopen( $file->get_file(), 'rb' ); // phpcs:ignore

		if ( ! $in ) {
			/** Failed to open input stream. */
			/** Attempt to clean up unfinished output. */
			@fclose( $out ); // phpcs:ignore
			@unlink( $temp_filepath ); // phpcs:ignore

			return new WP_Error(
				'fail_to_read_uploaded_file',
				sprintf( 'There was an error reading uploaded part %1$d of %2$d.', $current_part, $chunks ),
				$debug_info
			);
		}

		while ( $buff = fread( $in, 4096 ) ) { // phpcs:ignore
			fwrite( $out, $buff ); // phpcs:ignore
		}

		@fclose( $in ); // phpcs:ignore
		@fclose( $out ); // phpcs:ignore
		@unlink( $file->get_file() ); // phpcs:ignore

		/** Check if file has finished uploading all parts. */
		if ( ! $chunks || ( $chunks - 1 ) === $chunk ) {

			// debugging.
			if ( self::is_debugging_enabled() ) {
				$size = file_exists( $temp_filepath ) ? size_format( filesize( $temp_filepath ), 3 ) : '0 B';
				Logger::log( "Big File Uploader: Completing \"$file_name\" upload with a $size final size." );
			}

			return new UploadedFile( $temp_filepath, $file_name, $file->get_mime_type(), filesize( $temp_filepath ) );
		}

		return 0;
	}

	/**
	 * Create temp directory if it doesn't exist.
	 *
	 * @param string $temp_dir The temp directory to check.
	 *
	 * @return void
	 */
	protected static function maybe_created_temp_directory( string $temp_dir ) {
		if ( ! @is_dir( $temp_dir ) ) { // phpcs:ignore
			wp_mkdir_p( $temp_dir );
		}

		// Protect temp directory from browsing.
		$index_pathname = $temp_dir . '/index.php';
		if ( ! file_exists( $index_pathname ) ) {
			$_file = fopen( $index_pathname, 'w' ); // phpcs:ignore
			if ( false !== $_file ) {
				fwrite( $_file, "<?php\n// Silence is golden.\n" ); // phpcs:ignore
				fclose( $_file ); // phpcs:ignore
			}
		}
	}

	/**
	 * Delete files older than 24 hours.
	 *
	 * @param string $temp_dir The temp directory to check.
	 *
	 * @return void
	 */
	protected static function delete_older_files( string $temp_dir ): void {
		$files = glob( $temp_dir . '/*.part' );
		if ( is_array( $files ) ) {
			foreach ( $files as $_file ) {
				if ( @filemtime( $_file ) < time() - DAY_IN_SECONDS ) { // phpcs:ignore
					@unlink( $_file ); // phpcs:ignore
				}
			}
		}
	}

	/**
	 * Get the temp upload directory.
	 *
	 * @return string
	 */
	protected static function get_temp_upload_directory(): string {
		return apply_filters( 'chunk_uploader/temp_directory', WP_CONTENT_DIR . '/big-file-uploads-temp' );
	}

	/**
	 * Get max upload size.
	 *
	 * @return int Allowed upload size.
	 */
	protected static function get_max_upload_size(): int {
		return apply_filters( 'chunk_uploader/max_upload_size', wp_max_upload_size() );
	}

	/**
	 * Check if debugging is enabled.
	 *
	 * @return bool
	 */
	protected static function is_debugging_enabled(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}
}
