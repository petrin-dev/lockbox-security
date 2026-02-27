<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Remove Generator Meta Tags
 * Removes meta generator tags added by WordPress core, themes, and plugins
 * that unnecessarily expose software versions.
 */
class Lockbox_Module_Generator_Tags extends Lockbox_Module {

	protected string $slug        = 'generator_tags';
	protected string $name        = 'Remove Generator Meta Tags';
	protected string $description = 'Removes <meta name="generator"> tags from page HTML that can expose WordPress, theme, and plugin version information.';
	protected string $group       = 'wp_hardening';

	public function register_hooks(): void {
		// WordPress core generator
		remove_action( 'wp_head', 'wp_generator' );

		// Filter catches generator tags added by themes/plugins via the_generator
		add_filter( 'the_generator', '__return_empty_string' );

		// Remove any remaining generator meta tags from the final HTML output
		add_filter( 'wp_head', [ $this, 'buffer_and_strip_generators' ], PHP_INT_MAX );
	}

	public function buffer_and_strip_generators(): void {
		ob_start( [ $this, 'strip_generator_tags' ] );
	}

	public function strip_generator_tags( string $html ): string {
		return preg_replace(
			'/<meta[^>]+name=["\']generator["\'][^>]*>/i',
			'',
			$html
		) ?? $html;
	}
}
