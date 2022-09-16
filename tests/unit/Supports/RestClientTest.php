<?php

namespace StackonetWPFrameworkTest\Supports;

use Stackonet\WP\Framework\Supports\RestClient;

class RestClientTest extends \WP_UnitTestCase {
	public function setUp() {
		$this->rest_client = new RestClient( 'https://jsonplaceholder.typicode.com' );
		$this->rest_client->add_auth_header( md5( 'Stackonet' ) );
		$this->rest_client->set_global_parameter( 'v', '1.0' );
		parent::setUp();
	}

	public function test_get_request() {
		$response = $this->rest_client->get( '/posts/1' );
		$this->assertArrayHasKey( 'id', $response );
		$this->assertArrayHasKey( 'userId', $response );
		$this->assertArrayHasKey( 'title', $response );
		$this->assertArrayHasKey( 'body', $response );
	}

	public function test_rest_request_with_filter() {
		$response = $this->rest_client->get( '/posts', [ 'userId' => 1 ] );
		$this->assertNotWPError( $response );
	}

	public function test_post_request() {
		$response = $this->rest_client->post( '/posts', [
			'title'  => 'foo',
			'body'   => 'bar',
			'userId' => 1,
		] );
		$this->assertArrayHasKey( 'id', $response );
		$this->assertArrayHasKey( 'userId', $response );
		$this->assertArrayHasKey( 'title', $response );
		$this->assertArrayHasKey( 'body', $response );
	}

	public function test_update_request() {
		$response = $this->rest_client->put( '/posts/1', [
			'title'  => 'foo',
			'body'   => 'bar',
			'userId' => 1,
		] );
		$this->assertArrayHasKey( 'id', $response );
		$this->assertArrayHasKey( 'userId', $response );
		$this->assertArrayHasKey( 'title', $response );
		$this->assertArrayHasKey( 'body', $response );
	}

	public function test_delete_request() {
		$response = $this->rest_client->delete( '/posts/1' );
		$this->assertNotWPError( $response );
	}
}