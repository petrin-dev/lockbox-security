<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Hide WordPress Version
 * Comprehensively removes the WP version from all publicly accessible locations:
 * - Generator meta tag
 * - RSS feed generator tag
 * - Script/style ?ver= query strings
 * - readme.html / license.txt (via redirect)
 * - wp-admin login page generator header
 */
class Lockbox_Module_WP_Version extends Lockbox_Module {

	protected string $slug        = 'wp_version';
	protected string $name        = 'Hide WordPress Version';
	protected string $description = 'Removes the WordPress version number from all public-facing locations: meta tags, RSS feeds, asset query strings, and readme/license files.';
	protected string $group       = 'wp_hardening';

	public function register_hooks(): void {
		// Meta generator tag
		remove_action( 'wp_head', 'wp_generator' );

		// RSS feed generator
		add_filter( 'the_generator', '__return_empty_string' );

		// Strip ?ver= query strings from scripts and styles
		add_filter( 'style_loader_src', [ $this, 'remove_version_query_string' ], 9999 );
		add_filter( 'script_loader_src', [ $this, 'remove_version_query_string' ], 9999 );

		// Block direct access to readme.html, license.txt, wp-config-sample.php
		add_action( 'init', [ $this, 'block_sensitive_files' ] );
	}

	public function remove_version_query_string( string $src ): string {
		if ( str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	public function block_sensitive_files(): void {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		$blocked     = [ 'readme.html', 'license.txt', 'wp-config-sample.php' ];

		foreach ( $blocked as $file ) {
			if ( str_ends_with( strtolower( $request_uri ), $file ) ) {
				wp_die( '', '', [ 'response' => 404 ] );
			}
		}
	}
}
