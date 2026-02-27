<?php
/**
 * Plugin Name: Lockbox Security
 * Plugin URI:  https://github.com/lockbox-security
 * Description: Modular WordPress security hardening. Enable only what you need.
 * Version:     1.0.0
 * Author:      petrin.dev
 * Author URI: https://petrin.dev
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lockbox-security
 * Requires PHP: 8.2
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

define( 'LOCKBOX_VERSION', '1.0.0' );
define( 'LOCKBOX_PLUGIN_FILE', __FILE__ );
define( 'LOCKBOX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOCKBOX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once LOCKBOX_PLUGIN_DIR . 'includes/class-module.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/class-lockbox.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/class-admin.php';

// Modules
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-file-editing.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-wp-version.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-xmlrpc.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-generator-tags.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-user-enumeration.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-security-headers.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-inactivity-logout.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-strong-passwords.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-login-errors.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-login-attempts.php';
require_once LOCKBOX_PLUGIN_DIR . 'includes/modules/class-module-ip-whitelist.php';

function lockbox_security(): Lockbox {
	return Lockbox::instance();
}

lockbox_security();
