<?php

namespace StackonetWPFrameworkTest\Mocks;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess as BackgroundProcessBase;

class BackgroundProcess extends BackgroundProcessBase {
	public function get_data(): array {
		return $this->data;
	}

	protected function task( $item ) {
		return false;
	}
}