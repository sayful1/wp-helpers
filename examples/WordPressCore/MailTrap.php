<?php

namespace Stackonet\WP\Examples\WordPressCore;

use Stackonet\WP\Framework\SettingApi\SettingApi;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\Validate;

/**
 * MailTrap class
 */
class MailTrap {
	public static function init() {
		add_action( 'phpmailer_init', [ __CLASS__, 'phpmailer_init' ] );
		add_action( 'stackonet/settings/after_register', [ __CLASS__, 'register_settings_fields' ] );
	}

	public static function phpmailer_init( $phpmailer ) {
		$option = (array) get_option( '_wp_helper_setting_example' );
		if ( isset( $option['use_smtp'] ) && Validate::checked( $option['use_smtp'] ) ) {
			$phpmailer->isSMTP();
			$phpmailer->SMTPAuth = true;
			$phpmailer->Host     = $option['smtp_host'] ?? 'smtp.mailtrap.io';
			$phpmailer->Port     = $option['smtp_port'] ? intval( $option['smtp_port'] ) : 2525;
			$phpmailer->Username = $option['smtp_username'] ?? '';
			$phpmailer->Password = $option['smtp_password'] ?? '';
		}
	}

	public static function register_settings_fields( SettingApi $setting ) {
		$panels = [
			'id'       => 'panel_smtp',
			'title'    => 'SMTP Settings',
			'priority' => 10,
		];
		$setting->set_panel( $panels );

		$sections = [
			[
				'id'       => 'section_smtp',
				'title'    => __( 'SMTP Settings', 'dialog-contact-form' ),
				'panel'    => 'panel_smtp',
				'priority' => 10,
			],
			[
				'id'       => 'section_smtp_test',
				'title'    => __( 'SMTP Test', 'dialog-contact-form' ),
				'panel'    => 'panel_smtp',
				'priority' => 10,
			],
		];
		$setting->set_sections( $sections );

		$fields = [
			[
				'id'          => 'use_smtp',
				'type'        => 'checkbox',
				'title'       => __( 'Use SMTP', 'dialog-contact-form' ),
				'description' => __( 'Check to send all emails via SMTP', 'dialog-contact-form' ),
				'section'     => 'section_smtp',
				'priority'    => 5,
			],
			[
				'id'                => 'smtp_host',
				'type'              => 'text',
				'title'             => __( 'SMTP Host', 'dialog-contact-form' ),
				'description'       => __( 'Specify your SMTP server hostname', 'dialog-contact-form' ),
				'default'           => 'smtp.mailtrap.io',
				'priority'          => 10,
				'sanitize_callback' => 'sanitize_text_field',
				'section'           => 'section_smtp',
			],
			[
				'id'                => 'smtp_port',
				'type'              => 'text',
				'title'             => __( 'SMTP Port', 'dialog-contact-form' ),
				'description'       => __( 'Specify your SMTP server port', 'dialog-contact-form' ),
				'default'           => '2525',
				'priority'          => 15,
				'sanitize_callback' => 'sanitize_text_field',
				'section'           => 'section_smtp',
			],
			[
				'id'                => 'smtp_username',
				'type'              => 'text',
				'title'             => __( 'SMTP Username', 'dialog-contact-form' ),
				'description'       => __( 'Specify your SMTP server username', 'dialog-contact-form' ),
				'priority'          => 20,
				'sanitize_callback' => 'sanitize_text_field',
				'section'           => 'section_smtp',
			],
			[
				'id'                => 'smtp_password',
				'type'              => 'text',
				'title'             => __( 'SMTP Password', 'dialog-contact-form' ),
				'description'       => __( 'Specify your SMTP server password', 'dialog-contact-form' ),
				'priority'          => 25,
				'sanitize_callback' => 'sanitize_text_field',
				'section'           => 'section_smtp',
			],
			[
				'id'                => 'test_smtp_message',
				'type'              => 'text',
				'title'             => __( 'Title', 'dialog-contact-form' ),
				'description'       => __( 'Write test title to over SMTP.', 'dialog-contact-form' ),
				'priority'          => 30,
				'section'           => 'section_smtp_test',
				'sanitize_callback' => [ ( new static ), 'send_test_message' ],
			],
		];

		$setting->set_fields( $fields );
	}

	public static function send_test_message( $value ) {
		if ( $value ) {
			Logger::log( 'Sending mail with title: ' . $value );
			wp_mail( 'mail@example.com', $value, 'Write some content.' );
		}

		// Always return empty value
		return '';
	}
}