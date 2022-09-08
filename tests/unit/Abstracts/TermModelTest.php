<?php

namespace StackonetWPFrameworkTest\Abstracts;

use Stackonet\WP\Framework\Abstracts\TermModel;

class TermModelTest extends \WP_UnitTestCase {
	/**
	 * @var TermModel
	 */
	protected $instance;

	/**
	 * @var int
	 */
	private $post_id = 0;

	public function set_up() {
		$this->instance = new class extends TermModel {
			const TAXONOMY = 'faq_cat';

			protected static $meta_fields = [
				[ 'meta_key' => '_first_name', 'sanitize_callback' => 'sanitize_text_field' ],
				[ 'meta_key' => '_thumbnail_id', 'sanitize_callback' => 'absint' ],
			];
		};

		parent::set_up();
	}

	public function test_register_taxonomy() {
		register_taxonomy( $this->instance::TAXONOMY, 'post', $this->instance::get_term_args() );
		$this->assertTrue( taxonomy_exists( $this->instance::TAXONOMY ) );
	}

	public function test_create_custom_post() {
		$result        = $this->instance::create( 'Test 1' );
		$this->post_id = $result['term_id'] ?? 0;
		$testimonial   = new $this->instance( $this->post_id );

		$image_path = dirname( __DIR__, 2 ) . '/assets/stackonet-logo.png';
		if ( ! file_exists( $image_path ) ) {
			$backup_dir = dirname( __DIR__, 2 ) . '/backup-assets/*';
			$assets_dir = dirname( __DIR__, 2 ) . '/assets';
			shell_exec( "cp -r $backup_dir $assets_dir" );
		}

		$this->assertTrue( $testimonial->get_thumbnail_image() instanceof \ArrayObject );

		$attachment_id = $this->factory()->attachment->create_upload_object( $image_path );

		if ( $this->post_id ) {
			$this->instance::save_form_fields( $this->post_id, $this->instance::TAXONOMY, 'admin-ui',
				[ '_first_name' => 'Sayful', '_thumbnail_id' => $attachment_id ]
			);
		}

		// Test JSON conversion
		$data = json_decode( wp_json_encode( $testimonial ), true );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );

		$testimonial = new $this->instance( $this->post_id );
		$this->assertIsNumeric( $this->post_id );

		$this->assertEquals( $this->post_id, $testimonial->get_id() );
		$this->assertEquals( 'Test 1', $testimonial->get_name() );
		$this->assertEquals( 'test-1', $testimonial->get_slug() );
		$this->assertEquals( 'Sayful', $testimonial->get_meta( '_first_name' ) );
		$this->assertEquals( '', $testimonial->get_meta( '_meta_key_not_available' ) );
		$this->assertArrayHasKey( 'id', $testimonial->to_array() );

		$this->assertArrayHasKey( 'url', $testimonial->get_thumbnail_image() );
	}

	public function test_crud_operations() {
		$term1 = $this->instance::create( 'Chair' );
		$term2 = $this->instance::create( 'Table' );

		$query = $this->instance::query();
		$this->assertTrue( $query instanceof \WP_Term_Query );

		// Update
		$this->instance::update( $term2['term_id'], [ 'name' => 'Table Updated' ] );
		$testimonial = new $this->instance( $term2['term_id'] );
		$this->assertEquals( 'Table Updated', $testimonial->get_name() );

		$this->instance::delete( $term1['term_id'] );
	}

	public function test_find_for_post() {
		$term1 = $this->instance::create( 'Bed' );

		$post_id = $this->factory()->post->create( [
			'post_title'  => 'Test post for term',
			'post_status' => 'publish'
		] );

		wp_set_object_terms( $post_id, $term1['term_id'], $this->instance::TAXONOMY );

		$terms = $this->instance::find_for_post( $post_id );

		$this->assertIsArray( $terms );
		$this->assertTrue( $terms[0] instanceof $this->instance );
	}
}