<?php

namespace Stackonet\WP\Framework\Media;

use BadMethodCallException;
use finfo;
use Imagick;
use ImagickException;
use InvalidArgumentException;
use RuntimeException;
use Stackonet\WP\Framework\Interfaces\UploadedFileInterface;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class UploadedFile
 *
 * @method null|int getSize
 * @method int getError
 * @method null|string getClientFilename
 * @method null|string getClientMediaType
 * @method bool isPdf
 * @method bool isImage
 * @method static array getUploadedFiles
 *
 * @package Stackonet\WP\Framework\Media
 */
class UploadedFile implements UploadedFileInterface {

	/**
	 * The client-provided full path to the file
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * The client-provided file name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The client-provided media type of the file.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The media type of the file. Determine in server by checking file
	 *
	 * @var string
	 */
	protected $mime_type = false;

	/**
	 * The size of the file in bytes.
	 *
	 * @var int
	 */
	protected $size;

	/**
	 * A valid PHP UPLOAD_ERR_xxx code for the file upload.
	 *
	 * @var int
	 */
	protected $error = UPLOAD_ERR_OK;

	/**
	 * Indicates if the uploaded file has already been moved.
	 *
	 * @var bool
	 */
	protected $moved = false;

	/**
	 * Indicates if the upload is from a SAPI environment.
	 *
	 * @var bool
	 */
	protected $sapi = false;

	/**
	 * Create a normalized tree of UploadedFile instances from the Environment.
	 *
	 * @return array A normalized tree of UploadedFile instances or null if none are provided.
	 */
	public static function get_uploaded_files(): array {
		$files = [];
		if ( isset( $_FILES ) ) {
			$files = static::parse_uploaded_files( $_FILES );
		}

		return $files;
	}

	/**
	 * Read a file from file path.
	 *
	 * @param  string      $filepath  The filepath or uploaded temp filepath.
	 * @param  string|null $name  Optional filename to modify client filename.
	 *
	 * @return false|static
	 */
	public static function read_from_filepath( string $filepath, ?string $name = null ) {
		if ( file_exists( $filepath ) ) {
			if ( empty( $name ) ) {
				$name = basename( $filepath );
			}
			$mime_type = mime_content_type( $filepath );

			$self            = new static( $filepath, $name, $mime_type, filesize( $filepath ), UPLOAD_ERR_OK, true );
			$self->mime_type = $mime_type;

			return $self;
		}

		return false;
	}

	/**
	 * Read a file from url
	 *
	 * @param  string      $file_url  The remote file url.
	 * @param  string|null $name  Optional filename.
	 *
	 * @return WP_Error|static
	 */
	public static function read_from_url( string $file_url, ?string $name = null ) {
		$tmp_name = get_transient( md5( $file_url ) );
		if ( false === $tmp_name ) {
			$tmp_name = download_url( $file_url );
		} elseif ( ! file_exists( $tmp_name ) ) {
			$tmp_name = download_url( $file_url );
		}
		if ( is_wp_error( $tmp_name ) ) {
			return $tmp_name;
		}
		set_transient( md5( $file_url ), $tmp_name, HOUR_IN_SECONDS );

		if ( empty( $name ) ) {
			$name = basename( $file_url );
		}

		return static::read_from_filepath( $tmp_name, $name );
	}

	/**
	 * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
	 *
	 * @param array $uploaded_files The non-normalized tree of uploaded file data.
	 * @param bool  $sapi Is it Server Application Programming Interface.
	 *
	 * @return UploadedFile[] A normalized tree of UploadedFile instances.
	 */
	public static function parse_uploaded_files( array $uploaded_files, bool $sapi = true ): array {
		$parsed = [];
		foreach ( $uploaded_files as $field => $uploaded_file ) {
			if ( ! isset( $uploaded_file['error'] ) ) {
				if ( is_array( $uploaded_file ) ) {
					$parsed[ $field ] = static::parse_uploaded_files( $uploaded_file, $sapi );
				}
				continue;
			}

			$parsed[ $field ] = [];
			if ( ! is_array( $uploaded_file['error'] ) ) {
				$parsed[ $field ] = new static(
					$uploaded_file['tmp_name'],
					$uploaded_file['name'] ?? null,
					$uploaded_file['type'] ?? null,
					$uploaded_file['size'] ?? null,
					$uploaded_file['error'],
					$sapi
				);
			} else {
				$sub_array = array();
				foreach ( $uploaded_file['error'] as $file_index => $error ) {
					// normalise sub array and reparse to move the input's keyname up a level.
					$sub_array[ $file_index ]['name']     = $uploaded_file['name'][ $file_index ];
					$sub_array[ $file_index ]['type']     = $uploaded_file['type'][ $file_index ];
					$sub_array[ $file_index ]['tmp_name'] = $uploaded_file['tmp_name'][ $file_index ];
					$sub_array[ $file_index ]['error']    = $uploaded_file['error'][ $file_index ];
					$sub_array[ $file_index ]['size']     = $uploaded_file['size'][ $file_index ];

					$parsed[ $field ] = static::parse_uploaded_files( $sub_array, $sapi );
				}
			}
		}

		return $parsed;
	}

	/**
	 * Construct a new UploadedFile instance.
	 *
	 * @param string      $file The full path to the uploaded file provided by the client.
	 * @param string|null $name The file name.
	 * @param string|null $type The file media type.
	 * @param int|null    $size The file size in bytes.
	 * @param int         $error The UPLOAD_ERR_XXX code representing the status of the upload.
	 * @param bool        $sapi Indicates if the upload is in a SAPI environment.
	 */
	public function __construct(
		string $file, ?string $name = null, ?string $type = null, ?int $size = null,
		int $error = UPLOAD_ERR_OK, bool $sapi = false
	) {
		$this->file  = $file;
		$this->name  = $name;
		$this->type  = $type;
		$this->size  = $size;
		$this->error = $error;
		$this->sapi  = $sapi;
	}

	/**
	 * Set SAPI
	 *
	 * @param bool $sapi Set SAPI status.
	 */
	public function set_sapi( bool $sapi ): void {
		$this->sapi = $sapi;
	}

	/**
	 * Moves the uploaded file to the upload directory and assigns it a unique name
	 * to avoid overwriting an existing uploaded file.
	 *
	 * @param string      $directory directory to which the file is moved.
	 * @param null|string $filename unique file name.
	 *
	 * @return string new path of moved file
	 */
	public function move_uploaded_file( string $directory, ?string $filename = null ): string {
		if ( empty( $filename ) ) {
			$extension = pathinfo( $this->get_client_filename(), PATHINFO_EXTENSION );
			$basename  = md5( uniqid( wp_rand(), true ) );
			$filename  = sprintf( '%s.%0.8s', $basename, $extension );
		}

		$directory     = rtrim( $directory, DIRECTORY_SEPARATOR );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;
		$this->move_to( $new_file_path );

		return $new_file_path;
	}

	/**
	 * Move the uploaded file to a new location.
	 *
	 * Use this method as an alternative to move_uploaded_file(). This method is
	 * guaranteed to work in both SAPI and non-SAPI environments.
	 * Implementations must determine which environment they are in, and use the
	 * appropriate method (move_uploaded_file(), rename(), or a stream
	 * operation) to perform the operation.
	 *
	 * $targetPath may be an absolute path, or a relative path. If it is a
	 * relative path, resolution should be the same as used by PHP's rename()
	 * function.
	 *
	 * The original file or stream MUST be removed on completion.
	 *
	 * If this method is called more than once, any subsequent calls MUST raise
	 * an exception.
	 *
	 * When used in an SAPI environment where $_FILES is populated, when writing
	 * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
	 * used to ensure permissions and upload status are verified correctly.
	 *
	 * If you wish to move to a stream, use getStream(), as SAPI operations
	 * cannot guarantee writing to stream destinations.
	 *
	 * @see http://php.net/is_uploaded_file
	 * @see http://php.net/move_uploaded_file
	 *
	 * @param string $target_path Path to which to move the uploaded file.
	 *
	 * @throws InvalidArgumentException If the $path specified is invalid.
	 * @throws RuntimeException On any error during the move operation, or on
	 *     the second or subsequent call to the method.
	 */
	public function move_to( string $target_path ) {
		if ( $this->moved ) {
			throw new RuntimeException( 'Uploaded file already moved' );
		}

		if ( ! is_writable( dirname( $target_path ) ) ) {
			throw new InvalidArgumentException( 'Upload target path is not writable' );
		}

		if ( $this->sapi ) {
			if ( ! is_uploaded_file( $this->file ) ) {
				throw new RuntimeException( sprintf( '%1s is not a valid uploaded file', $this->file ) );
			}

			if ( ! move_uploaded_file( $this->file, $target_path ) ) {
				throw new RuntimeException( sprintf( 'Error moving uploaded file %1s to %2s', $this->name, $target_path ) );
			}
		} else {
			if ( ! rename( $this->file, $target_path ) ) {
				throw new RuntimeException( sprintf( 'Error moving uploaded file %1s to %2s', $this->name, $target_path ) );
			}
		}

		$this->moved = true;
	}

	/**
	 * Save as webp image
	 *
	 * @param  string      $directory  The directory where it will be saved.
	 * @param  string|null $filename Optional filename.
	 * @param  int         $compression_quality Compression quality.
	 *
	 * @return false|string
	 */
	public function save_as_webp_image( string $directory, ?string $filename = null, int $compression_quality = 83 ) {
		if ( ! $this->is_image() ) {
			return false;
		}
		if ( empty( $filename ) ) {
			$filename = sprintf( '%s.webp', wp_generate_uuid4() );
		}

		$directory     = rtrim( $directory, DIRECTORY_SEPARATOR );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;

		if ( class_exists( Imagick::class ) ) {
			try {
				$image = new Imagick( $this->get_file() );
				$image->setImageFormat( 'webp' );
				$image->setImageCompressionQuality( min( $compression_quality, 99 ) );
				$image->writeImage( $new_file_path );

				$image->destroy();

				// Set correct file permissions.
				$stat  = stat( dirname( $new_file_path ) );
				$perms = $stat['mode'] & 0000666;
				chmod( $new_file_path, $perms );

				unlink( $this->get_file() );
				$this->moved = true;

				return $new_file_path;
			} catch ( ImagickException $e ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Retrieve the file
	 *
	 * @return string
	 */
	public function get_file(): string {
		return $this->file;
	}

	/**
	 * Retrieve the file size.
	 *
	 * Implementations SHOULD return the value stored in the "size" key of
	 * the file in the $_FILES array if available, as PHP calculates this based
	 * on the actual size transmitted.
	 *
	 * @return int|null The file size in bytes or null if unknown.
	 */
	public function get_size(): ?int {
		return $this->size;
	}

	/**
	 * Retrieve the error associated with the uploaded file.
	 *
	 * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
	 *
	 * If the file was uploaded successfully, this method MUST return
	 * UPLOAD_ERR_OK.
	 *
	 * Implementations SHOULD return the value stored in the "error" key of
	 * the file in the $_FILES array.
	 *
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 *
	 * @return int One of PHP's UPLOAD_ERR_XXX constants.
	 */
	public function get_error(): int {
		return $this->error;
	}

	/**
	 * Retrieve the filename sent by the client.
	 *
	 * Do not trust the value returned by this method. A client could send
	 * a malicious filename with the intention to corrupt or hack your
	 * application.
	 *
	 * Implementations SHOULD return the value stored in the "name" key of
	 * the file in the $_FILES array.
	 *
	 * @return string|null The filename sent by the client or null if none
	 *     was provided.
	 */
	public function get_client_filename(): ?string {
		return $this->name;
	}

	/**
	 * Retrieve the media type sent by the client.
	 *
	 * Do not trust the value returned by this method. A client could send
	 * a malicious media type with the intention to corrupt or hack your
	 * application.
	 *
	 * Implementations SHOULD return the value stored in the "type" key of
	 * the file in the $_FILES array.
	 *
	 * @return string|null The media type sent by the client or null if none
	 *     was provided.
	 */
	public function get_client_media_type(): ?string {
		return $this->type;
	}

	/**
	 * Retrieve the file extension from filename sent by the client.
	 *
	 * @return string
	 */
	public function get_client_extension(): string {
		$extension = pathinfo( $this->get_client_filename(), PATHINFO_EXTENSION );

		return strtolower( $extension );
	}

	/**
	 * Get media type using finfo class
	 *
	 * @return string|bool
	 */
	public function get_mime_type() {
		if ( empty( $this->mime_type ) ) {
			if ( class_exists( finfo::class ) ) {
				$this->mime_type = ( new finfo() )->file( $this->get_file(), FILEINFO_MIME_TYPE );
			}
		}

		return $this->mime_type;
	}

	/**
	 * Check if uploaded file is an image
	 *
	 * @return bool
	 */
	public function is_image(): bool {
		$mime_types = [
			'image/jpeg',
			'image/gif',
			'image/png',
			'image/webp',
			'image/bmp',
			'image/tiff',
			'image/x-icon',
		];

		return in_array( $this->get_mime_type(), $mime_types, true );
	}

	/**
	 * Check if uploaded file is a video
	 *
	 * @return bool
	 */
	public function is_video(): bool {
		$mime_types = [
			'video/x-ms-asf',
			'video/x-ms-wmv',
			'video/x-ms-wmx',
			'video/x-ms-wm',
			'video/avi',
			'video/divx',
			'video/x-flv',
			'video/quicktime',
			'video/mpeg',
			'video/mp4',
			'video/ogg',
			'video/webm',
			'video/x-matroska',
		];

		return in_array( $this->get_mime_type(), $mime_types, true );
	}

	/**
	 * Check if uploaded file is a PDF
	 *
	 * @return bool
	 */
	public function is_pdf(): bool {
		return 'application/pdf' === $this->get_mime_type();
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
	public function __call( string $name, array $arguments ) {
		$new_method = static::camel_to_snake( $name );
		if ( method_exists( $this, $new_method ) ) {
			return $this->$new_method();
		}

		if ( 'getMediaType' === $name || 'get_media_type' === $new_method ) {
			return $this->get_mime_type();
		}

		throw new BadMethodCallException( sprintf( 'Method %s is not available', $name ) );
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
			return static::$new_method();
		}

		throw new BadMethodCallException( sprintf( 'Method %s is not available', $name ) );
	}
}
