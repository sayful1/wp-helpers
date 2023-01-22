<?php

namespace Stackonet\WP\Framework\Auth\REST;

use Stackonet\WP\Framework\Auth\Config;
use Stackonet\WP\Framework\REST\ApiController;

/**
 * Class BaseController
 *
 * @package Stackonet\WP\Framework\Auth\REST
 */
class BaseController extends ApiController {
	/**
	 * BaseController constructor.
	 */
	public function __construct() {
		$this->namespace = Config::get_rest_namespace();
	}
}
