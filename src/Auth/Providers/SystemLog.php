<?php

namespace Stackonet\WP\Framework\Auth\Providers;

use Stackonet\WP\Framework\Auth\Interfaces\OtpSmsProviderInterface;
use Stackonet\WP\Framework\Supports\Logger;

/**
 * SystemLog class
 * Log OTP to system log
 */
class SystemLog implements OtpSmsProviderInterface {

	/**
	 * Send OTP to phone number
	 *
	 * @param  string $phone_e164  Phone number in E164 format.
	 * @param  string $message  The message to be sent.
	 *
	 * @return void
	 */
	public function send( string $phone_e164, string $message ) {
		Logger::log(
			[
				'phone'   => $phone_e164,
				'message' => $message,
			]
		);
	}
}
