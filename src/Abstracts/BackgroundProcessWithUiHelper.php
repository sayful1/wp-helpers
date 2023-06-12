<?php

namespace Stackonet\WP\Framework\Abstracts;

defined( 'ABSPATH' ) || exit;

abstract class BackgroundProcessWithUiHelper extends BackgroundProcess {
	/**
	 * Show admin notice or not
	 *
	 * @var bool
	 */
	protected $show_admin_notice = true;

	/**
	 * Admin notice heading
	 *
	 * @var string
	 */
	protected $admin_notice_heading = "A background task is running to process {{total_items}} items.";

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_clear_' . $this->action, [ $this, 'clear_sync_openai_api' ] );
		add_action( 'wp_ajax_view_' . $this->action, [ $this, 'view_pending_tasks' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
		add_action( 'shutdown', [ $this, 'save_and_dispatch' ] );
	}

	/**
	 * Save and run background on shutdown of all code
	 */
	public function save_and_dispatch() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	/**
	 * Show admin status notice
	 *
	 * @return void
	 */
	public function admin_notices() {
		if ( false === $this->show_admin_notice ) {
			return;
		}
		$total_items = count( $this->get_pending_items() );
		if ( $total_items < 1 ) {
			return;
		}

		$message = str_replace( '{{total_items}}', $total_items, $this->admin_notice_heading );

		?>
		<div class="notice notice-info is-dismissible">
			<p><?php echo esc_html( $message ) ?></p>
			<p>
				<a href="<?php echo esc_url( $this->get_task_view_url() ); ?>" class="button button-primary"
				   target="_blank">View</a>
				<a href="<?php echo esc_url( $this->get_task_clear_url() ); ?>" class="button is-error">Clear
					Task</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Get task clear url
	 *
	 * @return string
	 */
	public function get_task_clear_url(): string {
		return wp_nonce_url(
			add_query_arg( [
				'action'   => 'clear_' . $this->action,
				'_referer' => urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
			], admin_url( 'admin-ajax.php' ) ),
			'clear_background_task_items_list',
			'_token'
		);
	}

	/**
	 * Get task view url
	 *
	 * @return string
	 */
	public function get_task_view_url(): string {
		return wp_nonce_url(
			add_query_arg( [ 'action' => 'view_' . $this->action ], admin_url( 'admin-ajax.php' ) ),
			'view_background_task_items_list',
			'_token'
		);
	}

	/**
	 * Get pending background task items
	 *
	 * @return array
	 */
	public function get_pending_items(): array {
		global $wpdb;
		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';
		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}
		$key     = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$column} LIKE %s ORDER BY {$key_column} ASC",
				$key
			),
			ARRAY_A
		);

		$tasks = [];
		foreach ( $results as $result ) {
			$values = maybe_unserialize( $result[ $value_column ] );
			foreach ( $values as $value ) {
				$tasks[] = $value;
			}
		}

		return $tasks;
	}

	/**
	 * View pending task items list
	 *
	 * @return void
	 */
	public function view_pending_tasks() {
		if ( isset( $_GET['_token'] ) && wp_verify_nonce( $_GET['_token'], 'view_background_task_items_list' ) ) {
			$tasks = $this->get_pending_items();
			header( 'Content-Type: application/json' );
			echo wp_json_encode( $tasks, \JSON_PRETTY_PRINT );
		}
		wp_die();
	}

	/**
	 * Clear all pending task item
	 *
	 * @return void
	 */
	public function clear_sync_openai_api() {
		if ( isset( $_GET['_token'] ) && wp_verify_nonce( $_GET['_token'], 'clear_background_task_items_list' ) ) {
			global $wpdb;
			$table  = $wpdb->options;
			$column = 'option_name';
			if ( is_multisite() ) {
				$table  = $wpdb->sitemeta;
				$column = 'meta_key';
			}
			$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE {$column} LIKE %s",
					$key
				)
			);

			$_referer    = isset( $_GET['_referer'] ) ? urldecode( $_GET['_referer'] ) : '';
			$redirect_to = $_referer ? site_url( $_referer ) : admin_url();

			wp_safe_redirect( $redirect_to );
		}
		wp_die();
	}
}
