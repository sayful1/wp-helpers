<?php

namespace Stackonet\WP\Framework\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * BackgroundProcessWithUiHelper class
 */
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
	protected $admin_notice_heading = 'A background task is running to process {{total_items}} items.';

	/**
	 * Capability required to perform view/clear operation
	 *
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_clear_' . $this->action, [ $this, 'clear_pending_tasks' ] );
		add_action( 'wp_ajax_view_' . $this->action, [ $this, 'view_pending_tasks' ] );
		add_action( 'wp_ajax_run_now_' . $this->action, [ $this, 'run_single_task' ] );
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
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		$total_items = count( $this->get_pending_items() );
		if ( $total_items < 1 ) {
			return;
		}

		$message = str_replace( '{{total_items}}', $total_items, $this->admin_notice_heading );

		?>
		<div class="notice notice-info is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
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
			add_query_arg(
				[
					'action'   => 'clear_' . $this->action,
					'_referer' => rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
				],
				admin_url( 'admin-ajax.php' )
			),
			'background_task_items_list',
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
			'background_task_items_list',
			'_token'
		);
	}

	/**
	 * Get pending background task items
	 *
	 * @return array
	 */
	public function get_pending_items(): array {
		$batches = $this->get_batches();

		$tasks = [];
		foreach ( $batches as $result ) {
			foreach ( $result as $value ) {
				$tasks[] = $value;
			}
		}

		return $tasks;
	}

	/**
	 * Get pending batches
	 *
	 * @return array
	 */
	public function get_batches() {
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
			$tasks[ $result[ $column ] ] = maybe_unserialize( $result[ $value_column ] );
		}

		return $tasks;
	}

	/**
	 * Remove a item from batches
	 *
	 * @param string|int $batch_key Batch key name.
	 * @param string|int $item_index Item index.
	 *
	 * @return void
	 */
	public function remove_from_batches( $batch_key, $item_index ) {
		$batches     = $this->get_batches();
		$batch_items = $batches[ $batch_key ] ?? [];
		if ( isset( $batch_items[ $item_index ] ) ) {
			unset( $batch_items[ $item_index ] );

			$this->update( $batch_key, $batch_items );
		}
	}

	/**
	 * View pending task items list
	 *
	 * @return void
	 */
	public function view_pending_tasks() {
		if ( $this->can_proceed() ) {
			$this->render_view();
		}
		wp_die();
	}

	/**
	 * Clear all pending task item
	 *
	 * @return void
	 */
	public function clear_pending_tasks() {
		if ( $this->can_proceed() ) {
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
			exit();
		}
		wp_die();
	}

	/**
	 * If current user has permission to view/delete tasks
	 *
	 * @return bool
	 */
	protected function can_proceed(): bool {
		if ( ! current_user_can( $this->capability ) ) {
			return false;
		}
		if ( ! isset( $_GET['_token'] ) ) {
			return false;
		}

		return false !== wp_verify_nonce( $_GET['_token'], 'background_task_items_list' );
	}

	/**
	 * Get run now action url.
	 *
	 * @param mixed      $payload payload data.
	 * @param string|int $batch_key Batch key.
	 * @param string|int $index Item index.
	 *
	 * @return string
	 */
	public function get_run_now_action_url( $payload, $batch_key, $index ): string {
		return wp_nonce_url(
			add_query_arg(
				[
					'action'     => 'run_now_' . $this->action,
					'batch'      => $batch_key,
					'item_index' => $index,
					'payload'    => rawurlencode( wp_json_encode( $payload ) ),
					'_referer'   => rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
				],
				admin_url( 'admin-ajax.php' )
			),
			'background_task_items_list',
			'_token'
		);
	}

	/**
	 * Run a single task
	 *
	 * @return void
	 */
	public function run_single_task() {
		if ( $this->can_proceed() ) {
			$batch_key  = isset( $_GET['batch'] ) ? sanitize_text_field( $_GET['batch'] ) : '';
			$item_index = isset( $_GET['item_index'] ) ? intval( $_GET['item_index'] ) : '';
			$payload    = isset( $_GET['payload'] ) ? rawurldecode( $_GET['payload'] ) : '';
			$payload    = json_decode( stripslashes( $payload ), true );

			if ( $payload ) {
				$task = $this->task( $payload );
				if ( false === $task ) {
					$this->remove_from_batches( $batch_key, $item_index );
				} else {
					var_dump( $task );
					die;
				}
			}

			wp_safe_redirect( $this->get_task_view_url() );
			exit();
		}
		die();
	}

	/**
	 * Render view
	 *
	 * @return void
	 */
	public function render_view() {
		$batches = $this->get_batches();
		$html    = '<div class="container">';
		foreach ( $batches as $batch_key => $tasks ) {
			$html .= '<h2>' . $batch_key . '</h2>';
			foreach ( $tasks as $index => $task ) {
				$action_url = $this->get_run_now_action_url( $task, $batch_key, $index );

				$html .= '<div class="card">';

				$html .= '<pre class="m-0"><code>';
				$html .= wp_json_encode( $task, JSON_PRETTY_PRINT );
				$html .= '</code></pre>' . PHP_EOL;

				$html .= '<div>';
				$html .= '<a class="button" href="' . $action_url . '">Run Now</a>';
				$html .= '</div>' . PHP_EOL;

				$html .= '</div>' . PHP_EOL;
			}
		}
		$html .= '</div>';

		echo $html . '<style>' . $this->style() . '</style>';
	}

	/**
	 * General style for pending task view
	 *
	 * @return string
	 */
	public function style(): string {
		$style = '<style type="text/css">';

		$style .= '.m-0 {margin:0}';
		$style .= '.card {
		    display:flex;
		    justify-content:space-between;
		    align-items:center;
		    margin-bottom: 8px;
		    border: 1px solid rgba(0,0,0,.12);
		    padding:8px;
		}';
		$style .= '.button {
		    display:inline-flex;
		    border: 1px solid rgba(0,0,0,.12);
		    padding:8px;
		    border-radius:4px;
		    text-decoration:none;
		}';
		$style .= '.container {
			margin:16px auto;
			max-width:960;
			display:block;
		}';
		$style .= '</style>';

		return $style;
	}
}
