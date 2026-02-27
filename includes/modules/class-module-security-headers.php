<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Security Headers
 * Adds three universal, non-destructive HTTP security headers:
 * - X-Frame-Options: SAMEORIGIN
 * - X-Content-Type-Options: nosniff
 * - Referrer-Policy: strict-origin-when-cross-origin
 *
 * CSP is intentionally omitted as it requires per-site configuration.
 */
class Lockbox_Module_Security_Headers extends Lockbox_Module {

	protected string $slug        = 'security_headers';
	protected string $name        = 'Security Headers';
	protected string $description = 'Adds X-Frame-Options, X-Content-Type-Options, and Referrer-Policy headers to all front-end responses. CSP is not included — configure that at the server or CDN level.';
	protected string $group       = 'admin_protect';

	public function register_hooks(): void {
		add_action( 'send_headers', [ $this, 'send_security_headers' ] );
		add_action( 'login_init', [ $this, 'send_security_headers' ] );
	}

	public function send_security_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}
}
