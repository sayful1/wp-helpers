<?php

namespace StackonetWPFrameworkTest\Abstracts;

use Stackonet\WP\Framework\Abstracts\PostTypeModel;

class PostTypeModelTest extends \WP_UnitTestCase {
	/**
	 * @var PostTypeModel
	 */
	protected $instance;

	/**
	 * @var int
	 */
	private $post_id = 0;

	public function set_up() {
		$this->instance = new class extends PostTypeModel {
			const POST_TYPE = 'testimonial';

			protected static $meta_fields = [
				[ 'meta_key' => '_first_name', 'sanitize_callback' => 'sanitize_text_field' ]
			];
		};

		parent::set_up();
	}

	/**
	 * Delete the item after the test.
	 */
	public function tearDown() {
		parent::tearDown();

		if ( $this->post_id ) {
			$this->instance::delete( $this->post_id );
		}
	}

	public function test_register_post_type() {
		register_post_type( $this->instance::POST_TYPE, $this->instance::get_post_type_args() );
		$this->assertTrue( post_type_exists( $this->instance::POST_TYPE ) );
	}

	public function test_create_custom_post() {
		$this->post_id = $this->instance::create( [
			'post_title'   => 'Chair',
			'post_content' => 'Chair post body',
			'post_excerpt' => 'Chair post summery',
			'post_status'  => 'publish',
		] );

		if ( $this->post_id ) {
			$this->instance::save_meta_data( $this->post_id, 'admin-ui', [ '_first_name' => 'Sayful' ] );
		}

		$this->assertIsNumeric( $this->post_id );

		$testimonial = new $this->instance( $this->post_id );
		$this->assertIsBool( $testimonial->is_valid() );
		$this->assertEquals( $this->post_id, $testimonial->get_id() );
		$this->assertEquals( 'Chair', $testimonial->get_title() );
		$this->assertEquals( 'publish', $testimonial->get_status() );
		$this->assertEquals( '<p>Chair post body</p>', trim( $testimonial->get_content() ) );
		$this->assertEquals( '<p>Chair post summery</p>', trim( $testimonial->get_excerpt() ) );
		$this->assertEquals( 'Sayful', $testimonial->get_meta( '_first_name' ) );
		$this->assertEquals( '', $testimonial->get_meta( '_meta_key_not_available' ) );
		$this->assertArrayHasKey( 'id', $testimonial->to_array() );

		$image_path = dirname( __DIR__, 2 ) . '/assets/stackonet-logo.png';
		if ( ! file_exists( $image_path ) ) {
			$backup_dir = dirname( __DIR__, 2 ) . '/backup-assets/*';
			$assets_dir = dirname( __DIR__, 2 ) . '/assets';
			shell_exec( "cp -r $backup_dir $assets_dir" );
		}

		$this->assertTrue( $testimonial->get_thumbnail_image() instanceof \ArrayObject );

		$attachment_id = $this->factory()->attachment->create_upload_object( $image_path, $testimonial->get_id() );
		$this->assertArrayHasKey( 'url', $testimonial->get_image_data( $attachment_id ) );

		// Test JSON conversion
		$data = json_decode( wp_json_encode( $testimonial ), true );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
	}

	public function test_crud_operations() {
		$id1 = $this->instance::create( [ 'post_title' => 'Chair', 'post_status' => 'publish', ] );
		$id2 = $this->instance::create( [ 'post_title' => 'Table', 'post_status' => 'publish', ] );

		$items = $this->instance::find();

		$this->assertIsArray( $items );
		$this->assertTrue( $items[0] instanceof $this->instance );

		// Update
		$this->instance::update( [ 'ID' => $id2, 'post_title' => 'Table Updated' ] );
		$testimonial = new $this->instance( $id2 );
		$this->assertEquals( 'Table Updated', $testimonial->get_title() );

		$this->instance::trash( $id1 );
		$this->instance::delete( $id2 );
		$this->instance::restore( $id1 );
	}
}