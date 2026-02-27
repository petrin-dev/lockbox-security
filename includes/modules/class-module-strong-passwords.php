<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module: Strong Passwords by Role
 * Suppresses the "confirm use of weak password" mechanism for selected roles,
 * making it impossible to save a weak password for those users.
 */
class Lockbox_Module_Strong_Passwords extends Lockbox_Module {

	protected string $slug        = 'strong_passwords';
	protected string $name        = 'Strong Passwords by Role';
	protected string $description = 'Remove the ability to bypass the weak password warning for selected roles. Users in these roles cannot save a weak password.';
	protected string $group       = 'login_auth';

	public function get_defaults(): array {
		return [
			'enabled' => false,
			'roles'   => [ 'editor', 'author', 'contributor' ],
		];
	}

	public function get_fields(): array {
		return [
			[
				'key'     => 'roles',
				'label'   => __( 'Enforce strong passwords for:', 'lockbox-security' ),
				'type'    => 'roles',
				'default' => [ 'editor', 'author', 'contributor' ],
			],
		];
	}

	public function register_hooks(): void {
		add_action( 'user_profile_update_errors', [ $this, 'block_weak_password' ], 10, 3 );
		add_action( 'validate_password_reset', [ $this, 'block_weak_password_on_reset' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_suppress_script' ] );
	}

	public function block_weak_password( WP_Error $errors, bool $update, WP_User $user ): void {
		if ( ! $this->user_is_affected( $user ) ) {
			return;
		}

		// If pw_weak is checked (user acknowledged weak password), block it for affected roles
		if ( isset( $_POST['pw_weak'] ) && ! empty( $_POST['pass1'] ) ) {
			$errors->add(
				'lockbox_weak_password',
				__( '<strong>Error:</strong> Your role requires a strong password. Please choose a stronger password.', 'lockbox-security' )
			);
		}
	}

	public function block_weak_password_on_reset( WP_Error $errors, WP_User $user ): void {
		if ( ! $this->user_is_affected( $user ) ) {
			return;
		}

		if ( isset( $_POST['pw_weak'] ) ) {
			$errors->add(
				'lockbox_weak_password',
				__( '<strong>Error:</strong> Your role requires a strong password. Please choose a stronger password.', 'lockbox-security' )
			);
		}
	}

	/**
	 * Check if we're on a profile page for an affected user, then schedule
	 * the suppression script to output in the admin footer.
	 */
	public function enqueue_suppress_script(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, [ 'profile', 'user-edit' ], true ) ) {
			return;
		}

		// Determine which user's profile is being edited
		$user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user || ! $this->user_is_affected( $user ) ) {
			return;
		}

		add_action( 'admin_footer', [ $this, 'output_suppress_script' ] );
	}

	/**
	 * Output a MutationObserver script that hides .pw-weak whenever it appears.
	 * Runs in admin_footer so the DOM is fully loaded. Uses MutationObserver
	 * instead of polling so it catches the element no matter when it appears.
	 */
	public function output_suppress_script(): void {
		?>
		<script>
		(function() {
			function hidePwWeak() {
				var el = document.querySelector('.pw-weak');
				if (el) el.style.display = 'none';
			}
			// Catch the element whenever it's added or modified
			var observer = new MutationObserver(hidePwWeak);
			observer.observe(document.body, { childList: true, subtree: true, attributes: true });
			// Handle the case where it's already in the DOM
			hidePwWeak();
		})();
		</script>
		<?php
	}

	private function user_is_affected( WP_User $user ): bool {
		// Super admins are always exempt
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return false;
		}

		$enforced_roles = (array) $this->get( 'roles', [] );
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $enforced_roles, true ) ) {
				return true;
			}
		}

		return false;
	}
}
