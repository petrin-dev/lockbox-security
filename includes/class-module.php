<?php

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for all Lockbox Security modules.
 */
abstract class Lockbox_Module {

	protected string $slug;
	protected string $name;
	protected string $description;
	protected string $group;
	protected array  $settings = [];

	/**
	 * Register WordPress hooks. Only called when the module is enabled.
	 */
	abstract public function register_hooks(): void;

	/**
	 * Return default settings for this module.
	 * Override in subclass to provide config values beyond 'enabled'.
	 */
	public function get_defaults(): array {
		return [ 'enabled' => false ];
	}

	/**
	 * Return field definitions for any sub-options this module exposes.
	 * Each field: [ 'key' => string, 'label' => string, 'type' => 'number'|'textarea'|'roles', 'default' => mixed ]
	 */
	public function get_fields(): array {
		return [];
	}

	public function set_settings( array $settings ): void {
		$this->settings = array_merge( $this->get_defaults(), $settings );
	}

	public function is_enabled(): bool {
		return (bool) ( $this->settings['enabled'] ?? false );
	}

	public function get( string $key, mixed $default = null ): mixed {
		return $this->settings[ $key ] ?? $default;
	}

	public function get_slug(): string {
		return $this->slug;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_description(): string {
		return $this->description;
	}

	public function get_group(): string {
		return $this->group;
	}
}
