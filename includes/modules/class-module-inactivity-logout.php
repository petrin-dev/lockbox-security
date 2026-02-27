<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Inactivity Logout
 * Logs out users after a configurable period of inactivity, tracked via a JS heartbeat.
 */
class Lockbox_Module_Inactivity_Logout extends Lockbox_Module {

	protected string $slug        = 'inactivity_logout';
	protected string $name        = 'Inactivity Logout';
	protected string $description = 'Automatically log out users after a period of inactivity.';
	protected string $group       = 'login_auth';

	public function get_defaults(): array {
		return [
			'enabled' => false,
			'timeout' => 30,
		];
	}

	public function get_fields(): array {
		return [
			[
				'key'     => 'timeout',
				'label'   => __( 'Log out after', 'lockbox-security' ),
				'type'    => 'number',
				'min'     => 1,
				'default' => 30,
				'unit'    => __( 'minutes of inactivity', 'lockbox-security' ),
			],
			[
				'type' => 'notice',
				'text' => __( '<strong>Note:</strong> Users with unsaved content (post drafts, open editors) will lose their work if logged out without saving. Set a generous timeout and ensure users are aware this feature is active.', 'lockbox-security' ),
			],
		];
	}

	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_script' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_script' ] );
		add_action( 'wp_ajax_lockbox_heartbeat', [ $this, 'ajax_heartbeat' ] );
		add_action( 'wp_ajax_lockbox_check_timeout', [ $this, 'ajax_check_timeout' ] );
		add_action( 'login_message', [ $this, 'maybe_show_timeout_message' ] );
	}

	public function maybe_enqueue_script(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Super admins bypass on multisite
		if ( is_multisite() && is_super_admin() ) {
			return;
		}

		$timeout_ms = (int) $this->get( 'timeout', 30 ) * 60 * 1000;

		wp_enqueue_script(
			'lockbox-inactivity',
			LOCKBOX_PLUGIN_URL . 'assets/js/inactivity-logout.js',
			[],
			LOCKBOX_VERSION,
			true
		);

		wp_localize_script( 'lockbox-inactivity', 'lockboxInactivity', [
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'lockbox_inactivity' ),
			'timeoutMs'      => $timeout_ms,
			'logoutBaseUrl'  => wp_logout_url(), // JS will append redirect_to dynamically
			'loginBaseUrl'   => wp_login_url(),  // JS builds the post-login return URL
			'heartbeatMs'    => 60000,
		] );
	}

	public function ajax_heartbeat(): void {
		check_ajax_referer( 'lockbox_inactivity', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'logged_out' => true ] );
		}

		set_transient( 'lockbox_activity_' . get_current_user_id(), time(), HOUR_IN_SECONDS );
		wp_send_json_success();
	}

	public function ajax_check_timeout(): void {
		check_ajax_referer( 'lockbox_inactivity', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'logged_out' => true ] );
		}
		wp_send_json_success();
	}

	public function maybe_show_timeout_message( string $message ): string {
		if ( isset( $_GET['lockbox_timeout'] ) ) {
			$message .= '<p class="message">' . esc_html__( 'You were logged out due to inactivity.', 'lockbox-security' ) . '</p>';
		}
		return $message;
	}
}
