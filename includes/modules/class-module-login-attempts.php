<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Limit Login Attempts
 * Tracks failed login attempts per IP via transients and temporarily blocks repeat offenders.
 */
class Lockbox_Module_Login_Attempts extends Lockbox_Module {

	protected string $slug        = 'login_attempts';
	protected string $name        = 'Limit Login Attempts';
	protected string $description = 'Temporarily block an IP after a set number of failed login attempts.';
	protected string $group       = 'login_auth';

	public function get_defaults(): array {
		return [
			'enabled'          => false,
			'max_attempts'     => 5,
			'lockout_duration' => 30,
		];
	}

	public function get_fields(): array {
		return [
			[
				'key'     => 'max_attempts',
				'label'   => __( 'Max failed attempts before lockout', 'lockbox-security' ),
				'type'    => 'number',
				'min'     => 1,
				'default' => 5,
				'unit'    => __( 'attempts', 'lockbox-security' ),
			],
			[
				'key'     => 'lockout_duration',
				'label'   => __( 'Lockout duration', 'lockbox-security' ),
				'type'    => 'number',
				'min'     => 1,
				'default' => 30,
				'unit'    => __( 'minutes', 'lockbox-security' ),
			],
		];
	}

	public function register_hooks(): void {
		// Priority 100 — must run AFTER core's wp_authenticate_username_password (priority 20)
		// so our WP_Error isn't overwritten by the credential checker.
		add_filter( 'authenticate', [ $this, 'check_lockout' ], 100, 2 );
		add_action( 'wp_login_failed', [ $this, 'record_failed_attempt' ] );
		add_action( 'wp_login', [ $this, 'clear_attempts_on_success' ], 10, 2 );
	}

	public function check_lockout( mixed $user, string $username ): mixed {
		if ( empty( $username ) ) {
			return $user;
		}

		$ip  = $this->get_client_ip();
		$key = $this->transient_key( $ip );

		$data = get_transient( $key );
		if ( false === $data ) {
			return $user;
		}

		if ( (int) $data['count'] >= (int) $this->get( 'max_attempts', 5 ) ) {
			return new WP_Error(
				'lockbox_too_many_attempts',
				__( '<strong>Too many failed login attempts.</strong> Your IP has been temporarily blocked. Please try again later.', 'lockbox-security' )
			);
		}

		return $user;
	}

	public function record_failed_attempt( string $username ): void {
		$ip       = $this->get_client_ip();
		$key      = $this->transient_key( $ip );
		$duration = (int) $this->get( 'lockout_duration', 30 ) * MINUTE_IN_SECONDS;
		$max      = (int) $this->get( 'max_attempts', 5 );

		$data = get_transient( $key );
		if ( false === $data ) {
			$data = [ 'count' => 0 ];
		}

		// Once locked, don't keep resetting the expiry on every attempt.
		if ( $data['count'] >= $max ) {
			return;
		}

		$data['count']++;
		set_transient( $key, $data, $duration );
	}

	public function clear_attempts_on_success( string $user_login, WP_User $user ): void {
		$ip  = $this->get_client_ip();
		$key = $this->transient_key( $ip );
		delete_transient( $key );
	}

	private function transient_key( string $ip ): string {
		return 'lockbox_login_attempts_' . md5( $ip );
	}

	private function get_client_ip(): string {
		// Prefer REMOTE_ADDR; do not trust forwarded headers without deliberate configuration.
		return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
	}
}
