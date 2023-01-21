<?php

namespace Stackonet\WP\Framework\Auth;

use Stackonet\WP\Framework\Auth\Admin\TokenListTable;

/**
 * Admin class
 */
class Admin {

	public static function revoke_auth_token() {
		if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'revoke_auth_token' ) ) {
			$user_id = isset( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : 0;
			$referer = isset( $_REQUEST['referer'] ) ? sanitize_text_field( $_REQUEST['referer'] ) : '';
			$referer = in_array( $referer, [ 'profile', 'user-edit' ], true ) ? $referer : '';
			$token   = isset( $_REQUEST['token'] ) ? sanitize_text_field( $_REQUEST['token'] ) : '';
			if ( 'all' === $token ) {
				$tokens = ( new Token() )->get_query_builder()->where( 'user_id', $user_id )->get();
				$ids    = wp_list_pluck( $tokens, 'id' );
				( new Token() )->batch_delete( $ids );
			} else {
				( new Token() )->delete( intval( $token ) );
			}

			if ( 'user-edit' === $referer ) {
				wp_safe_redirect( add_query_arg( 'message', 'revoke_auth_token', get_edit_user_link( $user_id ) ) );
			} else {
				wp_safe_redirect( add_query_arg( 'message', 'revoke_auth_token', get_edit_profile_url( $user_id ) ) );
			}
			exit;
		}
	}

	/**
	 * Save option
	 *
	 * @param  int  $user_id  The user id.
	 *
	 * @return void
	 */
	public static function save_option( int $user_id ) {
		// phpcs:ignore
		$token_name = isset( $_POST['new_auth_token_name'] ) ? sanitize_text_field( $_POST['new_auth_token_name'] ) : '';
		// phpcs:ignore
		$month = isset( $_POST['new_auth_token_month'] ) ? intval( $_POST['new_auth_token_month'] ) : 1;
		if ( ! empty( $token_name ) ) {
			$token = Token::generate_token_for_user( $user_id, $month, $token_name );
			update_user_meta( $user_id, '_new_auth_token', $token );
		}
	}

	/**
	 * Generate token ui for admin
	 *
	 * @param  \WP_User  $user
	 *
	 * @return void
	 */
	public static function token_ui( \WP_User $user ) {
		$new_token = get_user_meta( $user->ID, '_new_auth_token', true );
		if ( ! empty( $new_token ) ) {
			delete_user_meta( $user->ID, '_new_auth_token' );
		}
		?>
        <div class="auth-token">
            <h2>Auth Tokens</h2>
            <p>Auth token allow authentication via REST API, without providing your actual password. Auth token can be
                easily revoked. They cannot be used for traditional logins to your website.</p>

            <div class="create-application-password form-wrap">
                <div class="form-field">
                    <label for="new_auth_token_name">New Token Name</label>
                    <input type="text" size="30" id="new_auth_token_name" name="new_auth_token_name" autocomplete="off"
                           class="input" aria-required="true" aria-describedby="new_auth_token_name_desc">
                    <p class="description" id="new_auth_token_name_desc">
                        Required to create an Application Password, but not to update the user.
                    </p>
                </div>
                <div class="form-field">
                    <label for="new_auth_token_month">Expire in</label>
                    <select name="new_auth_token_month" id="new_auth_token_month">
                        <option value="1">30 days</option>
                        <option value="3">90 days</option>
                        <option value="6">180 days</option>
                        <option value="12">360 days</option>
                    </select>
                </div>

                <button type="submit" name="do_new_auth_token" id="do_new_auth_token" class="button button-secondary">
                    Add New Auth Token
                </button>
            </div>

			<?php if ( $new_token ) { ?>
                <div class="notice notice-success is-dismissible new-application-password-notice" role="alert"
                     tabindex="-1">
                    <p class="application-password-display">
                        <label for="new-application-password-value">
                            Your new auth token is: </label>
                        <input id="new-application-password-value" type="text" class="code" readonly="readonly"
                               value="<?php echo esc_attr( $new_token ) ?>" style="width: 22em">
                    </p>
                    <p>Be sure to save this in a safe location. You will not be able to retrieve it.</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
			<?php } ?>

            <div class="application-passwords-list-table-wrapper">
				<?php
				$application_passwords_list_table = new TokenListTable();
				$application_passwords_list_table->prepare_items();
				$application_passwords_list_table->display();
				?>
            </div>
        </div>
		<?php
	}
}
