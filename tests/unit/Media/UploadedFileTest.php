<?php

namespace StackonetWPFrameworkTest\Media;

use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Media\Uploader;

class UploadedFileTest extends \WP_UnitTestCase {
	private $data = [];
	private $uploaded_files = [];

	public function copy_back_assets() {
		$backup_dir = dirname( __DIR__, 2 ) . '/backup-assets/*';
		$assets_dir = dirname( __DIR__, 2 ) . '/assets';
		shell_exec( "cp -r $backup_dir $assets_dir" );
	}

	public function setUp() {
		$this->copy_back_assets();
		$image_path           = dirname( __DIR__, 2 ) . '/assets/stackonet-logo.png';
		$pdf2_path            = dirname( __DIR__, 2 ) . '/assets/A4-Card.pdf';
		$this->data           = [
			'image_sample' => [
				'error'    => UPLOAD_ERR_OK,
				'name'     => basename( $image_path ),
				'size'     => filesize( $image_path ),
				'tmp_name' => $image_path,
				'type'     => 'image/png'
			],
			'pdf_sample'   => [
				[
					'error'    => UPLOAD_ERR_OK,
					'name'     => basename( $pdf2_path ),
					'size'     => filesize( $pdf2_path ),
					'tmp_name' => $pdf2_path,
					'type'     => 'application/pdf'
				]
			]
		];
		$this->uploaded_files = UploadedFile::parse_uploaded_files( $this->data, false );
	}

	public function test_uploaded_files() {
		$image_path     = dirname( __DIR__, 2 ) . '/assets/stackonet-logo.png';
		$pdf2_path      = dirname( __DIR__, 2 ) . '/assets/A4-Card.pdf';
		$_FILES         = [
			'image_sample' => [
				'error'    => UPLOAD_ERR_OK,
				'name'     => basename( $image_path ),
				'size'     => filesize( $image_path ),
				'tmp_name' => $image_path,
				'type'     => 'image/png'
			],
			'pdf_sample'   => [
				'error'    => [ UPLOAD_ERR_OK ],
				'name'     => [ basename( $pdf2_path ) ],
				'size'     => [ filesize( $pdf2_path ) ],
				'tmp_name' => [ $pdf2_path ],
				'type'     => [ 'application/pdf' ]
			],
		];
		$uploaded_files = UploadedFile::getUploadedFiles();
		$this->assertArrayHasKey( 'image_sample', $uploaded_files );
		$this->assertArrayHasKey( 'pdf_sample', $uploaded_files );
	}

	public function test_image_file_upload() {
		$uploaded_file = $this->uploaded_files['image_sample'];
		$this->assertTrue( $uploaded_file instanceof UploadedFile );
		$this->assertEquals( $this->data['image_sample']['size'], $uploaded_file->getSize() );
		$this->assertEquals( $this->data['image_sample']['tmp_name'], $uploaded_file->get_file() );
		$this->assertEquals( $this->data['image_sample']['error'], $uploaded_file->get_error() );
		$this->assertEquals( $this->data['image_sample']['type'], $uploaded_file->get_mime_type() );
		$this->assertEquals( $this->data['image_sample']['type'], $uploaded_file->getMediaType() );
		$this->assertEquals( $this->data['image_sample']['name'], $uploaded_file->get_client_filename() );
		$this->assertEquals( 'png', $uploaded_file->get_client_extension() );
		$this->assertTrue( $uploaded_file->isImage() );
		$this->assertTrue( $uploaded_file->is_image() );
	}

	public function test_pdf_file_upload() {
		$uploaded_file = $this->uploaded_files['pdf_sample'][0];
		$this->assertTrue( $uploaded_file instanceof UploadedFile );
		$this->assertEquals( $this->data['pdf_sample'][0]['size'], $uploaded_file->getSize() );
		$this->assertEquals( $this->data['pdf_sample'][0]['type'], $uploaded_file->get_mime_type() );
		$this->assertEquals( $this->data['pdf_sample'][0]['type'], $uploaded_file->getMediaType() );
		$this->assertEquals( $this->data['pdf_sample'][0]['type'], $uploaded_file->get_client_media_type() );
		$this->assertEquals( 'pdf', $uploaded_file->get_client_extension() );
		$this->assertTrue( $uploaded_file->isPdf() );
		$this->assertTrue( $uploaded_file->is_pdf() );
	}

	public function test_exception() {
		$this->expectException( \BadMethodCallException::class );

		$uploaded_file = $this->uploaded_files['image_sample'];
		$this->assertTrue( $uploaded_file instanceof UploadedFile );
		$uploaded_file->callSomeMethodThatNotExist();
	}

	public function test_exception_for_static_method() {
		$this->expectException( \BadMethodCallException::class );
		UploadedFile::callSomeStaticMethodThatNotExist();
	}

	public function test_file_upload() {
		foreach ( $this->uploaded_files as $name => $uploaded_file ) {
			$ids = Uploader::upload( $uploaded_file );

			$this->assertIsArray( $ids );
			$this->assertIsNumeric( $ids[0]['attachment_id'] );
			$this->assertTrue( $ids[0]['attachment_id'] > 0 );
		}
	}
}