<?php

namespace StackonetWPFrameworkTest\Supports;

use Stackonet\WP\Framework\Supports\Logger;

class LoggerTest extends \WP_UnitTestCase {
	public function setUp() {
		if ( ! defined( 'WP_DEBUG_LOG' ) ) {
			$path = dirname( __FILE__, 2 ) . '/log/debug.log';
			define( 'WP_DEBUG_LOG', $path );
		}
		parent::setUp();
	}

	public function tearDown() {
		if ( file_exists( WP_DEBUG_LOG ) ) {
			unlink( WP_DEBUG_LOG );
		}
		parent::tearDown();
	}

	public function test_error_log() {
		$array_data  = [ 'name' => 'Sayful Islam', 'country' => 'BD' ];
		$object_data = (object) $array_data;
		Logger::log( 'test' );
		Logger::log( $array_data );
		Logger::log( $object_data );
		$this->assertFileExists( WP_DEBUG_LOG );
		$file_content = file_get_contents( WP_DEBUG_LOG );
		$this->assertStringContainsString( 'test', $file_content );
		$this->assertStringContainsString( print_r( $array_data, true ), $file_content );
	}

	public function test_error_log_exception() {
		$exception = new \Exception( 'test' );
		Logger::log( $exception );
		$this->assertFileExists( WP_DEBUG_LOG );
		$file_content = file_get_contents( WP_DEBUG_LOG );
		$this->assertStringContainsString( $exception->__toString(), $file_content );
	}
}