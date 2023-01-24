<?php

namespace Stackonet\WP\Framework\Auth\Admin;

use Stackonet\WP\Framework\Auth\Models\Token;
use WP_List_Table;

if ( ! class_exists( \WP_List_Table::class ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class TokenListTable
 */
class TokenListTable extends WP_List_Table {

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'token',
				'plural'   => 'tokens',
				'ajax'     => false,
			]
		);
	}

	/**
	 * Gets the list of columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'note'         => 'Name',
			'issued_at'    => 'Created',
			'expired_at'   => 'Expired',
			'last_used_at' => 'Last Used',
			'last_ip'      => 'Last IP',
			'revoke'       => 'Revoke',
		];
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 5.6.0
	 *
	 * @global int $user_id User ID.
	 */
	public function prepare_items() {
		global $user_id;

		$this->_column_headers = array( $this->get_columns(), [], [] );

		$query = ( new Token() )->get_query_builder();
		$query->where( 'user_id', $user_id );
		$items       = $query->get();
		$this->items = $items;
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param  array  $item  The current item.
	 * @param  string $column_name  The current column name.
	 */
	protected function column_default( $item, $column_name ) {
		if ( in_array( $column_name, [ 'issued_at', 'expired_at', 'last_used_at' ], true ) ) {
			if ( ! empty( $item[ $column_name ] ) ) {
				echo esc_html( date_i18n( 'F j, Y', strtotime( $item[ $column_name ] ) ) );
			} else {
				echo '&mdash;';
			}

			return;
		}
		echo $item[ $column_name ] ? esc_html( $item[ $column_name ] ) : '';
	}

	/**
	 * Handles the last ip column output.
	 *
	 * @param  array $item  The current application password item.
	 */
	public function column_last_ip( $item ) {
		if ( empty( $item['last_ip'] ) ) {
			echo '&mdash;';
		} else {
			echo esc_html( $item['last_ip'] );
		}
	}

	/**
	 * Handles the revoke column output.
	 *
	 * @param  array $item  The current application password item.
	 *
	 * @since 5.6.0
	 */
	public function column_revoke( $item ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mode       = isset( $_GET['user_id'] ) ? 'user-edit' : 'profile';
		$revoke_url = add_query_arg(
			[
				'action'  => 'revoke_auth_token',
				'token'   => $item['id'],
				'user_id' => $item['user_id'],
				'referer' => $mode,
			],
			admin_url( 'admin-ajax.php' )
		);
		printf(
			'<a href="%1$s" class="button delete" aria-label="%2$s">%3$s</a>',
			esc_url( wp_nonce_url( $revoke_url, 'revoke_auth_token' ) ),
			/* translators: %s: the application password's given name. */
			esc_attr( sprintf( 'Revoke "%s"', $item['note'] ) ),
			'Revoke'
		);
	}

	/**
	 * Generates custom table navigation to prevent conflicting nonces.
	 *
	 * @param  string $which  The location of the bulk actions: 'top' or 'bottom'.
	 *
	 * @since 5.6.0
	 */
	protected function display_tablenav( $which ) {
		global $user_id;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mode       = isset( $_GET['user_id'] ) ? 'user-edit' : 'profile';
		$revoke_url = wp_nonce_url(
			add_query_arg(
				[
					'action'  => 'revoke_auth_token',
					'token'   => 'all',
					'user_id' => $user_id,
					'referer' => $mode,
				],
				admin_url( 'admin-ajax.php' )
			),
			'revoke_auth_token'
		);
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( 'bottom' === $which ) : ?>
				<div class="alignright">
					<a class="button delete"
					   href="<?php echo esc_url( $revoke_url ); ?>"><?php _e( 'Revoke all auth tokens' ); ?></a>
				</div>
			<?php endif; ?>
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear"/>
		</div>
		<?php
	}
}
