<?php

namespace StackonetWPFrameworkTest\Supports;

use Stackonet\WP\Framework\Supports\StringHelper;

class StringHelperTest extends \WP_UnitTestCase {
	public function test_starts_with() {
		$this->assertTrue( StringHelper::str_starts_with( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_starts_with( 'Stackonet', 'onet' ) );
	}

	public function test_ends_with() {
		$this->assertTrue( StringHelper::str_ends_with( 'Stackonet', 'onet' ) );
		$this->assertFalse( StringHelper::str_ends_with( 'Stackonet', 'Stack' ) );
	}

	public function test_contains() {
		$this->assertTrue( StringHelper::str_exists( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_exists( 'Stackonet', 'sayful' ) );
	}

	public function test_str_truncate() {
		$this->assertEquals(
			'Stackonet...',
			StringHelper::str_truncate( 'Stackonet is a company.', 12 )
		);
	}
}