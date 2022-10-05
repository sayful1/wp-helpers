<?php

namespace StackonetWPFrameworkTest\Supports;

use Stackonet\WP\Framework\Supports\QueryBuilder;

class QueryBuilderTest extends \WP_UnitTestCase {
	protected $query_builder;

	public function setUp() {
		$this->query_builder = QueryBuilder::table( 'posts' );
	}

	public function test_retrieve_multiple_records() {
		$query_builder = QueryBuilder::table( 'posts' );
		$query_builder->where( 'post_status', 'publish' )
		              ->order_by( 'post_title', 'ASC' )
		              ->order_by( 'post_date', 'DESC' )
		              ->limit( 10 )
		              ->page( 1 )
		              ->get();

		$this->assertEquals(
			"SELECT * FROM wp_posts WHERE post_status = 'publish' ORDER BY post_title ASC, post_date DESC LIMIT 10 OFFSET 0",
			$query_builder->get_query_sql()
		);
	}

	public function test_select_single_data() {
		$query_builder = QueryBuilder::table( 'posts' );
		$this->assertEquals( "SELECT * FROM wp_posts", $query_builder->get_query_sql() );

		$query_builder->where( 'post_status', 'publish' );
		$this->assertEquals(
			"SELECT * FROM wp_posts WHERE post_status = 'publish'",
			$query_builder->get_query_sql()
		);

		$query_builder->where( 'post_type', [ 'post', 'page' ], 'IN' );
		$this->assertEquals(
			"SELECT * FROM wp_posts WHERE post_status = 'publish' AND post_type IN('post','page')",
			$query_builder->get_query_sql()
		);
	}
}
