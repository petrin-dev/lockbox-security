<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Block User Enumeration
 * Blocks two common user enumeration vectors:
 * 1. The ?author=N query string (redirects to user archive pages, exposing usernames)
 * 2. The unauthenticated REST API /wp/v2/users endpoint
 */
class Lockbox_Module_User_Enumeration extends Lockbox_Module {

	protected string $slug        = 'user_enumeration';
	protected string $name        = 'Block User Enumeration';
	protected string $description = 'Prevents attackers from discovering WordPress usernames via the ?author= query string and the unauthenticated REST API users endpoint.';
	protected string $group       = 'wp_hardening';

	public function register_hooks(): void {
		// Block ?author= queries
		add_action( 'init', [ $this, 'block_author_scan' ], 1 );

		// Restrict REST API /wp/v2/users to authenticated users
		add_filter( 'rest_endpoints', [ $this, 'restrict_user_endpoints' ] );
	}

	public function block_author_scan(): void {
		// Skip in admin context
		if ( is_admin() ) {
			return;
		}

		if ( isset( $_REQUEST['author'] ) && ! is_numeric( $_REQUEST['author'] ) ) {
			return; // Not a numeric author ID — not the enumeration pattern
		}

		if ( isset( $_SERVER['QUERY_STRING'] ) && preg_match( '/author=\d+/i', $_SERVER['QUERY_STRING'] ) ) {
			// Allow admins to use this for legitimate author archive needs
			if ( current_user_can( 'list_users' ) ) {
				return;
			}

			wp_die(
				esc_html__( 'User enumeration is disabled on this site.', 'lockbox-security' ),
				'',
				[ 'response' => 403 ]
			);
		}
	}

	public function restrict_user_endpoints( array $endpoints ): array {
		$user_endpoints = [
			'/wp/v2/users',
			'/wp/v2/users/(?P<id>[\d]+)',
		];

		foreach ( $user_endpoints as $endpoint ) {
			if ( ! isset( $endpoints[ $endpoint ] ) ) {
				continue;
			}

			foreach ( array_keys( $endpoints[ $endpoint ] ) as $key ) {
				if ( isset( $endpoints[ $endpoint ][ $key ]['methods'] ) ) {
					$endpoints[ $endpoint ][ $key ]['permission_callback'] = static function (): bool {
						return current_user_can( 'list_users' );
					};
				}
			}
		}

		return $endpoints;
	}
}
