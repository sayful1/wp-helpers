<?php

namespace StackonetWPFrameworkTest\Supports;

use Stackonet\WP\Framework\Supports\Filesystem;

class FilesystemTest extends \WP_UnitTestCase {

	public function tearDown() {
		$file_system = Filesystem::get_filesystem();
		$uploads_dir = Filesystem::get_uploads_dir( 'test' );
		$file_system->delete( $uploads_dir['path'], true );
		parent::tearDown();
	}

	public function test_get_filesystem() {
		$file_system = Filesystem::get_filesystem();
		$this->assertInstanceOf( 'WP_Filesystem_Base', $file_system );
	}

	public function test_get_uploads_dir() {
		$uploads_dir = Filesystem::get_uploads_dir( 'test' );
		$this->assertArrayHasKey( 'path', $uploads_dir );
		$this->assertArrayHasKey( 'url', $uploads_dir );
	}

	public function test_update_file_content() {
		$uploads_dir  = Filesystem::get_uploads_dir( 'test' );
		$file_path    = $uploads_dir['path'] . '/test.txt';
		$file_content = 'This is a test file content';
		$result       = Filesystem::update_file_content( $file_content, $file_path );
		$this->assertTrue( $result );
		$this->assertFileExists( $file_path );
		$this->assertFileIsReadable( $file_path );
		$this->assertFileIsWritable( $file_path );

		$read_file_content = file_get_contents( $file_path );
		$this->assertStringContainsString( $file_content, $read_file_content );
	}
}