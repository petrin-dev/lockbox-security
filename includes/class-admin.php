<?php

defined( 'ABSPATH' ) || exit;

/**
 * Handles the admin settings page under Tools → Lockbox Security.
 */
class Lockbox_Admin {

	private Lockbox $lockbox;

	public function __construct( Lockbox $lockbox ) {
		$this->lockbox = $lockbox;
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_lockbox_save_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_lockbox_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );
	}

	public function register_page(): void {
		add_management_page(
			__( 'Lockbox Security', 'lockbox-security' ),
			'<span class="dashicons dashicons-lock" style="font-size:16px;line-height:1.45;float:left;margin-right:4px;"></span>' . __( 'Lockbox Security', 'lockbox-security' ),
			'manage_options',
			'lockbox-security',
			[ $this, 'render_page' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'tools_page_lockbox-security' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'lockbox-admin',
			LOCKBOX_PLUGIN_URL . 'assets/css/admin-settings.css',
			[],
			LOCKBOX_VERSION
		);

		wp_enqueue_script(
			'lockbox-admin',
			LOCKBOX_PLUGIN_URL . 'assets/js/admin-settings.js',
			[],
			LOCKBOX_VERSION,
			true
		);

		wp_localize_script( 'lockbox-admin', 'lockboxAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'lockbox_settings' ),
		] );
	}

	public function ajax_save_settings(): void {
		check_ajax_referer( 'lockbox_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'lockbox-security' ) ] );
		}

		$posted   = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}';
		$raw      = json_decode( $posted, true );

		if ( ! is_array( $raw ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data.', 'lockbox-security' ) ] );
		}

		$sanitized = $this->sanitize_settings( $raw );
		$this->lockbox->save_settings( $sanitized );

		wp_send_json_success( [ 'message' => __( 'Settings saved.', 'lockbox-security' ) ] );
	}

	public function ajax_dismiss_notice(): void {
		check_ajax_referer( 'lockbox_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$notice_id = sanitize_key( $_POST['notice_id'] ?? '' );
		if ( $notice_id ) {
			$this->lockbox->dismiss_notice( $notice_id );
		}

		wp_send_json_success();
	}

	private function sanitize_settings( array $raw ): array {
		$sanitized = [];
		$modules   = $this->lockbox->get_modules();

		foreach ( $modules as $slug => $module ) {
			$module_raw = $raw[ $slug ] ?? [];
			$defaults   = $module->get_defaults();
			$fields     = $module->get_fields();
			$entry      = [];

			// Enabled toggle
			$entry['enabled'] = ! empty( $module_raw['enabled'] );

			// Module-specific fields
			foreach ( $fields as $field ) {
				$key   = $field['key'];
				$value = $module_raw[ $key ] ?? $field['default'];

				$entry[ $key ] = match ( $field['type'] ) {
					'number'   => absint( $value ),
					'textarea' => sanitize_textarea_field( $value ),
					'roles'    => array_map( 'sanitize_key', (array) $value ),
					default    => sanitize_text_field( $value ),
				};
			}

			$sanitized[ $slug ] = $entry;
		}

		return $sanitized;
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$modules  = $this->lockbox->get_modules();
		$settings = $this->lockbox->get_settings();

		$groups = [
			'login_auth'    => __( 'Login & Authentication', 'lockbox-security' ),
			'wp_hardening'  => __( 'WordPress Hardening', 'lockbox-security' ),
			'admin_protect' => __( 'Admin Protection', 'lockbox-security' ),
		];

		// Group modules
		$grouped = [];
		foreach ( $modules as $module ) {
			$grouped[ $module->get_group() ][] = $module;
		}

		?>
		<div class="wrap lockbox-wrap">
			<div class="lockbox-header">
				<h1><span class="dashicons dashicons-lock" aria-hidden="true"></span> <?php esc_html_e( 'Lockbox Security', 'lockbox-security' ); ?></h1>
				<p class="lockbox-tagline"><?php esc_html_e( 'Modular security hardening. Enable only what you need.', 'lockbox-security' ); ?></p>
				<?php if ( is_multisite() ) : ?>
				<div class="lockbox-notice lockbox-notice--info">
					<strong><?php esc_html_e( 'Multisite detected:', 'lockbox-security' ); ?></strong>
					<?php esc_html_e( 'All settings apply network-wide. Super Admins are exempt from all restrictions. Per-site configuration is not supported in this version.', 'lockbox-security' ); ?>
				</div>
				<?php endif; ?>
			</div>

			<?php $this->render_recommended_notices(); ?>

			<form id="lockbox-settings-form">
				<?php foreach ( $groups as $group_key => $group_label ) : ?>
					<?php if ( empty( $grouped[ $group_key ] ) ) : continue; endif; ?>
					<div class="lockbox-group">
						<h2 class="lockbox-group__title"><?php echo esc_html( $group_label ); ?></h2>
						<?php foreach ( $grouped[ $group_key ] as $module ) : ?>
							<?php $this->render_module_row( $module, $settings[ $module->get_slug() ] ?? [] ); ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>

				<div class="lockbox-actions">
					<button type="submit" class="button button-primary lockbox-save-btn">
						<?php esc_html_e( 'Save Settings', 'lockbox-security' ); ?>
					</button>
					<span class="lockbox-save-status" aria-live="polite"></span>
				</div>
			</form>

			<?php $this->render_header_inspector(); ?>
		</div>
		<?php
	}

	private function render_module_row( Lockbox_Module $module, array $current_settings ): void {
		$slug    = $module->get_slug();
		$enabled = ! empty( $current_settings['enabled'] );
		$fields  = $module->get_fields();
		$id      = 'lockbox-module-' . $slug;
		?>
		<div class="lockbox-module <?php echo $enabled ? 'is-enabled' : ''; ?>" data-module="<?php echo esc_attr( $slug ); ?>">
			<div class="lockbox-module__header">
				<label class="lockbox-toggle" for="<?php echo esc_attr( $id ); ?>">
					<input
						type="checkbox"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( "settings[{$slug}][enabled]" ); ?>"
						value="1"
						<?php checked( $enabled ); ?>
						class="lockbox-module-toggle"
					>
					<span class="lockbox-toggle__label"><?php echo esc_html( $module->get_name() ); ?></span>
				</label>
				<p class="lockbox-module__description"><?php echo esc_html( $module->get_description() ); ?></p>
			</div>

			<?php if ( ! empty( $fields ) ) : ?>
			<div class="lockbox-module__fields <?php echo $enabled ? '' : 'is-hidden'; ?>">
				<?php foreach ( $fields as $field ) : ?>
					<?php $this->render_field( $slug, $field, $current_settings ); ?>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_field( string $slug, array $field, array $current_settings ): void {
		$type    = $field['type'];
		$key     = $field['key'] ?? '';
		$label   = $field['label'] ?? '';
		$default = $field['default'] ?? null;
		$value   = $key ? ( $current_settings[ $key ] ?? $default ) : null;
		$name    = $key ? "settings[{$slug}][{$key}]" : '';
		$id      = $key ? "lockbox-{$slug}-{$key}" : '';

		echo '<div class="lockbox-field">';

		switch ( $type ) {
			case 'number':
				$unit = $field['unit'] ?? '';
				echo '<div class="lockbox-field__inline">';
				echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
				echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( $field['min'] ?? 1 ) . '" style="width:80px;">';
				if ( $unit ) {
					echo '<span class="lockbox-field__unit">' . esc_html( $unit ) . '</span>';
				}
				echo '</div>';
				break;

			case 'textarea':
				echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
				if ( ! empty( $field['description'] ) ) {
					echo '<p class="lockbox-field__desc">' . esc_html( $field['description'] ) . '</p>';
				}
				echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4" style="width:100%;max-width:500px;">' . esc_textarea( $value ) . '</textarea>';
				break;

			case 'notice':
				echo '<p class="lockbox-field__notice">' . wp_kses( $field['text'], [ 'strong' => [], 'em' => [] ] ) . '</p>';
				break;

			case 'roles':
				$selected_roles = (array) $value;
				$all_roles      = wp_roles()->get_names();
				echo '<fieldset>';
				echo '<legend>' . esc_html( $label ) . '</legend>';
				foreach ( $all_roles as $role_slug => $role_name ) {
					$checked = in_array( $role_slug, $selected_roles, true );
					$field_id = esc_attr( $id . '-' . $role_slug );
					echo '<label class="lockbox-role-label">';
					echo '<input type="checkbox" name="' . esc_attr( $name . '[]' ) . '" value="' . esc_attr( $role_slug ) . '" id="' . $field_id . '" ' . checked( $checked, true, false ) . '>';
					echo esc_html( $role_name );
					echo '</label>';
				}
				echo '</fieldset>';
				break;
		}

		echo '</div>';
	}

	private function render_recommended_notices(): void {
		$notices = [
			[
				'id'          => 'passwordless_login',
				'plugin_name' => 'Passwordless Login',
				'plugin_url'  => 'https://wordpress.org/plugins/passwordless-login/',
				'message'     => __( 'For magic link / passwordless authentication, we recommend the free <strong>Passwordless Login</strong> plugin — a focused, well-maintained solution.', 'lockbox-security' ),
			],
			[
				'id'          => 'activity_log',
				'plugin_name' => 'WP Security Audit Log',
				'plugin_url'  => 'https://wordpress.org/plugins/wp-security-audit-log/',
				'message'     => __( 'For comprehensive admin activity logging, we recommend the free <strong>WP Security Audit Log</strong> — comprehensive activity logging is outside the scope of Lockbox.', 'lockbox-security' ),
			],
		];

		foreach ( $notices as $notice ) {
			if ( $this->lockbox->is_notice_dismissed( $notice['id'] ) ) {
				continue;
			}
			?>
			<div class="lockbox-notice lockbox-notice--recommend" data-notice-id="<?php echo esc_attr( $notice['id'] ); ?>">
				<p><?php echo wp_kses( $notice['message'], [ 'strong' => [] ] ); ?>
				<a href="<?php echo esc_url( $notice['plugin_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View plugin →', 'lockbox-security' ); ?></a></p>
				<button type="button" class="lockbox-dismiss-notice button button-secondary" data-notice-id="<?php echo esc_attr( $notice['id'] ); ?>">
					<?php esc_html_e( 'Noted — don\'t show again', 'lockbox-security' ); ?>
				</button>
			</div>
			<?php
		}
	}

	private function render_header_inspector(): void {
		$url     = home_url( '/' );
		$headers = [];

		// Fetch front-end headers via internal request
		$response = wp_remote_head( $url, [ 'timeout' => 5, 'sslverify' => false ] );

		$checks = [
			'X-Frame-Options'           => [ 'label' => 'X-Frame-Options',           'recommended' => 'SAMEORIGIN' ],
			'X-Content-Type-Options'    => [ 'label' => 'X-Content-Type-Options',    'recommended' => 'nosniff' ],
			'Referrer-Policy'           => [ 'label' => 'Referrer-Policy',           'recommended' => 'strict-origin-when-cross-origin' ],
			'Content-Security-Policy'   => [ 'label' => 'Content-Security-Policy',   'recommended' => null ],
			'Strict-Transport-Security' => [ 'label' => 'Strict-Transport-Security', 'recommended' => null ],
			'Permissions-Policy'        => [ 'label' => 'Permissions-Policy',        'recommended' => null ],
		];

		if ( ! is_wp_error( $response ) ) {
			foreach ( $checks as $header_name => $check ) {
				$value = wp_remote_retrieve_header( $response, strtolower( $header_name ) );
				$headers[ $header_name ] = $value ?: null;
			}
		}

		?>
		<div class="lockbox-inspector">
			<h2><?php esc_html_e( 'Security Header Inspector', 'lockbox-security' ); ?></h2>
			<p class="lockbox-inspector__desc"><?php esc_html_e( 'Read-only snapshot of HTTP response headers on your front page. Configure missing headers via Lockbox modules above or at the server/CDN level.', 'lockbox-security' ); ?></p>

			<?php if ( is_wp_error( $response ) ) : ?>
				<p class="lockbox-inspector__error"><?php esc_html_e( 'Could not retrieve headers — request failed.', 'lockbox-security' ); ?></p>
			<?php else : ?>
			<table class="lockbox-inspector__table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Header', 'lockbox-security' ); ?></th>
						<th><?php esc_html_e( 'Current Value', 'lockbox-security' ); ?></th>
						<th><?php esc_html_e( 'Status', 'lockbox-security' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $checks as $header_name => $check ) : ?>
						<?php
						$value  = $headers[ $header_name ] ?? null;
						$is_set = null !== $value;
						$status_class = $is_set ? 'lockbox-badge lockbox-badge--ok' : 'lockbox-badge lockbox-badge--missing';
						$status_label = $is_set ? __( 'Set', 'lockbox-security' ) : __( 'Missing', 'lockbox-security' );
						?>
						<tr>
							<td><code><?php echo esc_html( $check['label'] ); ?></code></td>
							<td><?php echo $is_set ? '<code>' . esc_html( $value ) . '</code>' : '<em>' . esc_html__( 'not set', 'lockbox-security' ) . '</em>'; ?></td>
							<td><span class="<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
