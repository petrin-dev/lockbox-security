<?php
/**
 * Lockbox Security — Uninstall
 * Runs when the plugin is deleted (not just deactivated).
 * Removes all plugin data from the database.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'lockbox_security_settings' );
delete_option( 'lockbox_dismissed_notices' );

// Clean up any login attempt transients
global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lockbox_login_attempts_%' OR option_name LIKE '_transient_timeout_lockbox_login_attempts_%'"
);

// Clean up inactivity transients
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lockbox_activity_%' OR option_name LIKE '_transient_timeout_lockbox_activity_%'"
);
