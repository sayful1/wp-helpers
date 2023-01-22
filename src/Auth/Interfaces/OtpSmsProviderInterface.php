<?php

namespace Stackonet\WP\Framework\Auth\Interfaces;

interface OtpSmsProviderInterface {
	/**
	 * Send OTP to phone number
	 *
	 * @param string $phone_e164 Phone number in E164 format.
	 * @param string $message The message to be sent.
	 *
	 * @return mixed
	 */
	public function send( string $phone_e164, string $message );
}
