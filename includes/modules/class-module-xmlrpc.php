<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Disable XML-RPC
 * Completely disables XML-RPC. Note: this will break Jetpack, the WordPress
 * mobile apps, and any service that relies on the XML-RPC API.
 */
class Lockbox_Module_XMLRPC extends Lockbox_Module {

	protected string $slug        = 'xmlrpc';
	protected string $name        = 'Disable XML-RPC';
	protected string $description = 'Completely disables the XML-RPC endpoint. Note: this will break Jetpack and the WordPress mobile apps if you use them.';
	protected string $group       = 'wp_hardening';

	public function register_hooks(): void {
		add_filter( 'xmlrpc_enabled', '__return_false' );

		// Remove the X-Pingback header
		add_filter( 'wp_headers', [ $this, 'remove_pingback_header' ] );

		// Remove the RSD/wlwmanifest links from wp_head
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );

		// Block direct requests to xmlrpc.php
		add_action( 'init', [ $this, 'block_xmlrpc_request' ], 1 );
	}

	public function remove_pingback_header( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	public function block_xmlrpc_request(): void {
		if ( isset( $_SERVER['REQUEST_URI'] ) && str_contains( $_SERVER['REQUEST_URI'], 'xmlrpc.php' ) ) {
			wp_die(
				esc_html__( 'XML-RPC is disabled on this site.', 'lockbox-security' ),
				esc_html__( 'XML-RPC Disabled', 'lockbox-security' ),
				[ 'response' => 403 ]
			);
		}
	}
}
