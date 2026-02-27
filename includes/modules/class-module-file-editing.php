<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Disable File Editing
 * Defines DISALLOW_FILE_EDIT if not already defined, preventing admins from
 * editing theme/plugin files via the WordPress dashboard.
 */
class Lockbox_Module_File_Editing extends Lockbox_Module {

	protected string $slug        = 'file_editing';
	protected string $name        = 'Disable File Editing';
	protected string $description = 'Removes the Theme/Plugin Editor from the dashboard, preventing code edits via the admin UI. Equivalent to defining DISALLOW_FILE_EDIT in wp-config.php.';
	protected string $group       = 'wp_hardening';

	public function register_hooks(): void {
		// Must be defined before the admin menu is built.
		// We hook as early as possible, but if the constant is already set in
		// wp-config.php this is a no-op and the wp-config.php value wins.
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}
	}
}
