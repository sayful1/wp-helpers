<?php

namespace Stackonet\WP\Framework\Auth\Emails;

use Stackonet\WP\Framework\Emails\ActionEmailTemplate;
use Stackonet\WP\Framework\Emails\Mailer;
use Stackonet\WP\Framework\Supports\Logger;
use WP_User;

/**
 * Class RegistrationConfirmEmail
 *
 * @package Stackonet\WP\Framework\Auth\Emails
 */
class RegistrationConfirmEmail {
	/**
	 * User object
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * RegistrationConfirmEmail constructor.
	 *
	 * @param  int|string|WP_User $user User's ID, a WP_User object, or a user object from the DB.
	 */
	public function __construct( $user = null ) {
		$this->user = new WP_User( $user );
	}

	/**
	 * Send email
	 */
	public function send() {
		$code = wp_rand( 111111, 999999 );
		$now  = time();
		$data = [
			'code'       => $code,
			'not_before' => gmdate( 'Y-m-d H:i:s', $now ),
			'not_after'  => gmdate( 'Y-m-d H:i:s', ( $now + ( MINUTE_IN_SECONDS * 15 ) ) ),
		];
		update_user_meta( $this->user->ID, '_registration_verification_code', $data );

		$content = $this->get_content_html( $code );

		try {
			$mailer = new Mailer();
			$mailer->setReceiver( $this->user->user_email, $this->user->display_name );
			$mailer->setSubject( 'Registration Verification Code.' );
			$mailer->setMessage( $content );
			$mailer->isHTML( true );
			$mailer->send();
			Logger::log( 'Mail sent.' );
		} catch ( \Exception $e ) {
			Logger::log( $e );
		}
	}

	/**
	 * Get content html
	 *
	 * @param  mixed $code The verification code.
	 *
	 * @return string
	 */
	public function get_content_html( $code ): string {
		$mailer = new ActionEmailTemplate();
		$mailer->set_box_mode( false );
		$mailer->set_greeting( "Hello {$this->user->display_name}!" );
		$mailer->set_intro_lines( "<strong>{$code}</strong>" );
		$mailer->set_intro_lines( ' is your verification code to verify your registration.' );
		$mailer->set_intro_lines( 'If you did not request for registration, no further action is required.' );

		return $mailer->get_content_html();
	}
}
