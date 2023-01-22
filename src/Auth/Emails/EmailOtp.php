<?php

namespace Stackonet\WP\Framework\Auth\Emails;

use Stackonet\WP\Framework\Emails\Mailer;

/**
 * EmailOtp class
 */
class EmailOtp extends Mailer {
	public function __construct( string $email, string $otp ) {
		$this->setTo( $email );
		$this->setSubject( 'Email validation code' );
		$this->set_greeting( 'Hello!' );
		$this->set_intro_lines( 'Your email validation code:' );
		$this->set_outro_lines( $otp );
	}
}
