<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Admin IP Whitelist
 * Restricts access to /wp-admin/ and wp-login.php to a list of allowed IP addresses.
 * Settings are stored in the database only — no wp-config.php modifications are made.
 */
class Lockbox_Module_IP_Whitelist extends Lockbox_Module {

	protected string $slug        = 'ip_whitelist';
	protected string $name        = 'Admin IP Whitelist';
	protected string $description = 'Restrict access to /wp-admin and wp-login.php to specific IP addresses. Supports IPv4 and IPv6.';
	protected string $group       = 'admin_protect';

	public function get_defaults(): array {
		return [
			'enabled' => false,
			'ips'     => '',
		];
	}

	public function get_fields(): array {
		return [
			[
				'key'         => 'ips',
				'label'       => __( 'Allowed IP addresses', 'lockbox-security' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'One IP address per line. Your current IP: ' . $this->get_client_ip(), 'lockbox-security' ),
			],
			[
				'type' => 'notice',
				'text' => __( '<strong>Locked out?</strong> Recovery options: (1) Run <strong>wp option delete lockbox_security_settings</strong> via WP-CLI. (2) Delete the <strong>lockbox_security_settings</strong> row from <strong>wp_options</strong> in your database. (3) Delete or rename the plugin folder via FTP/SFTP.', 'lockbox-security' ),
			],
		];
	}

	public function register_hooks(): void {
		add_action( 'init', [ $this, 'check_ip_access' ], 1 );
	}

	public function check_ip_access(): void {
		if ( ! $this->is_admin_request() ) {
			return;
		}

		// Super admins bypass on multisite
		if ( is_multisite() && is_super_admin() ) {
			return;
		}

		$client_ip   = $this->get_client_ip();
		$allowed_ips = $this->get_allowed_ips();

		if ( in_array( $client_ip, $allowed_ips, true ) ) {
			return;
		}

		wp_die(
			esc_html__( 'Access to this area is restricted.', 'lockbox-security' ),
			esc_html__( 'Forbidden', 'lockbox-security' ),
			[ 'response' => 403 ]
		);
	}

	private function is_admin_request(): bool {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		return is_admin() || str_contains( $request_uri, 'wp-login.php' );
	}

	private function get_allowed_ips(): array {
		$ips = [];

		$raw = $this->get( 'ips', '' );
		if ( ! empty( $raw ) ) {
			$lines = explode( "\n", $raw );
			foreach ( $lines as $line ) {
				$ip = trim( $line );
				if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					$ips[] = $ip;
				}
			}
		}

		return array_unique( $ips );
	}

	private function get_client_ip(): string {
		return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
	}
}
