<?php

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin orchestrator. Singleton. Loads and boots all modules.
 */
final class Lockbox {

	private static ?self $instance = null;

	/** @var Lockbox_Module[] */
	private array $modules = [];

	private array $settings = [];

	const OPTION_KEY     = 'lockbox_security_settings';
	const DISMISSED_KEY  = 'lockbox_dismissed_notices';

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	private function __construct() {}

	private function boot(): void {
		$this->settings = $this->load_settings();
		$this->register_modules();
		$this->boot_modules();

		if ( is_admin() ) {
			new Lockbox_Admin( $this );
		}

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'lockbox-security', false, dirname( plugin_basename( LOCKBOX_PLUGIN_FILE ) ) . '/languages' );
	}

	private function register_modules(): void {
		$module_classes = [
			// Login & Authentication
			'Lockbox_Module_Login_Attempts',
			'Lockbox_Module_Inactivity_Logout',
			'Lockbox_Module_Login_Errors',
			'Lockbox_Module_Strong_Passwords',
			// WordPress Hardening
			'Lockbox_Module_File_Editing',
			'Lockbox_Module_WP_Version',
			'Lockbox_Module_XMLRPC',
			'Lockbox_Module_Generator_Tags',
			'Lockbox_Module_User_Enumeration',
			// Admin Protection
			'Lockbox_Module_IP_Whitelist',
			'Lockbox_Module_Security_Headers',
		];

		foreach ( $module_classes as $class ) {
			/** @var Lockbox_Module $module */
			$module = new $class();
			$slug   = $module->get_slug();

			$module->set_settings( $this->settings[ $slug ] ?? [] );
			$this->modules[ $slug ] = $module;
		}
	}

	private function boot_modules(): void {
		foreach ( $this->modules as $module ) {
			if ( $module->is_enabled() ) {
				$module->register_hooks();
			}
		}
	}

	public function get_modules(): array {
		return $this->modules;
	}

	public function get_module( string $slug ): ?Lockbox_Module {
		return $this->modules[ $slug ] ?? null;
	}

	public function get_settings(): array {
		return $this->settings;
	}

	private function load_settings(): array {
		return (array) get_option( self::OPTION_KEY, [] );
	}

	public function save_settings( array $settings ): void {
		$this->settings = $settings;
		update_option( self::OPTION_KEY, $settings );
	}

	public function is_notice_dismissed( string $notice_id ): bool {
		$dismissed = (array) get_option( self::DISMISSED_KEY, [] );
		return in_array( $notice_id, $dismissed, true );
	}

	public function dismiss_notice( string $notice_id ): void {
		$dismissed   = (array) get_option( self::DISMISSED_KEY, [] );
		$dismissed[] = $notice_id;
		update_option( self::DISMISSED_KEY, array_unique( $dismissed ) );
	}
}
