<?php

namespace StackonetWPFrameworkTest\Supports;

use Stackonet\WP\Framework\Supports\StringHelper;

class StringHelperTest extends \WP_UnitTestCase {
	public function test_starts_with() {
		$this->assertTrue( StringHelper::str_starts_with( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_starts_with( 'Stackonet', 'onet' ) );
		$this->assertFalse( StringHelper::str_starts_with( 'Stackonet', '' ) );
	}

	public function test_ends_with() {
		$this->assertTrue( StringHelper::str_ends_with( 'Stackonet', 'onet' ) );
		$this->assertFalse( StringHelper::str_ends_with( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_ends_with( 'Stackonet', '' ) );
	}

	public function test_contains() {
		$this->assertTrue( StringHelper::str_exists( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_exists( 'Stackonet', 'sayful' ) );
		$this->assertFalse( StringHelper::str_exists( 'Stackonet', '' ) );
	}

	public function test_str_truncate() {
		$this->assertEquals(
			'Stackonet...',
			StringHelper::str_truncate( 'Stackonet is a company.', 12 )
		);
		$this->assertEquals(
			'Stackonet is a company.',
			StringHelper::str_truncate( 'Stackonet is a company.', 54 )
		);
	}

	public function test_str_to_sane_utf8() {
		$string = "My name is written in Bengali: সাকিব";

		$this->assertEquals(
			'My name is written in Bengali: সাকিব',
			StringHelper::str_to_sane_utf8( $string )
		);
	}

	public function test_disabling_multibyte() {
		StringHelper::set_multibyte_loaded( false );

		$this->assertTrue( StringHelper::str_starts_with( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_starts_with( 'Stackonet', 'onet' ) );
		$this->assertFalse( StringHelper::str_starts_with( 'Stackonet', '' ) );

		$this->assertTrue( StringHelper::str_ends_with( 'Stackonet', 'onet' ) );
		$this->assertFalse( StringHelper::str_ends_with( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_ends_with( 'Stackonet', '' ) );

		$this->assertTrue( StringHelper::str_exists( 'Stackonet', 'Stack' ) );
		$this->assertFalse( StringHelper::str_exists( 'Stackonet', 'sayful' ) );
		$this->assertFalse( StringHelper::str_exists( 'Stackonet', '' ) );

		$this->assertEquals(
			'Stackonet...',
			StringHelper::str_truncate( 'Stackonet is a company.', 12 )
		);
		$this->assertEquals(
			'Stackonet is a company.',
			StringHelper::str_truncate( 'Stackonet is a company.', 54 )
		);
	}
}