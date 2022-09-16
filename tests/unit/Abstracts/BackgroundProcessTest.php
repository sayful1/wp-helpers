<?php

namespace StackonetWPFrameworkTest\Abstracts;

use StackonetWPFrameworkTest\Mocks\BackgroundProcess;

class BackgroundProcessTest extends \WP_UnitTestCase {

	/**
	 * @var BackgroundProcess
	 */
	protected $background_process;

	public function set_up() {
		parent::set_up();

		$this->background_process = new BackgroundProcess();
	}

	public function test_push_to_queue() {
		$this->background_process->push_to_queue( 'test' );
		$this->background_process->push_to_queue( 'test 2' );
		$this->background_process->push_to_queue( 'test 3' );
		$this->background_process->save()->dispatch();
		$this->assertEquals( 3, count( $this->background_process->get_data() ) );
	}
}
