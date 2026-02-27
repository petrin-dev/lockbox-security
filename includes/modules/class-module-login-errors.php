<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Generic Login Errors
 * Replaces WordPress's specific login error messages with a generic one
 * so attackers cannot determine whether a username exists.
 */
class Lockbox_Module_Login_Errors extends Lockbox_Module {

	protected string $slug        = 'login_errors';
	protected string $name        = 'Generic Login Errors';
	protected string $description = 'Replace specific "incorrect username" or "incorrect password" messages with a single vague error, preventing username enumeration via the login form.';
	protected string $group       = 'login_auth';

	public function register_hooks(): void {
		add_filter( 'login_errors', [ $this, 'generic_error' ] );
		add_filter( 'shake_error_codes', [ $this, 'add_shake_code' ] );
	}

	public function generic_error( string $error ): string {
		// Only replace authentication-related errors, not others (e.g. cookie errors)
		$auth_errors = [
			'invalid_username',
			'invalid_email',
			'incorrect_password',
			'invalidcombo',
		];

		global $errors;
		if ( ! is_wp_error( $errors ) ) {
			return $error;
		}

		foreach ( $auth_errors as $code ) {
			if ( in_array( $code, $errors->get_error_codes(), true ) ) {
				return '<strong>' . esc_html__( 'Error:', 'lockbox-security' ) . '</strong> ' .
					esc_html__( 'The credentials you entered are incorrect. Please try again.', 'lockbox-security' );
			}
		}

		return $error;
	}

	public function add_shake_code( array $codes ): array {
		$codes[] = 'lockbox_generic_error';
		return $codes;
	}
}
