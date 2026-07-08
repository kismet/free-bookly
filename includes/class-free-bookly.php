<?php

/**
 * La classe core del plugin.
 *
 * Viene utilizzata per definire internazionalizzazione, hooks lato admin e lato publico,
 * e il Module Manager.
 */
class Free_Bookly {

	protected $loader;
	protected $plugin_name;
	protected $version;
	protected $module_manager;

	public function __construct() {
		if ( defined( 'FREE_BOOKLY_VERSION' ) ) {
			$this->version = FREE_BOOKLY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'free-bookly';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->init_module_manager();
	}

	private function load_dependencies() {
		// Richiede la classe per attivazione DB
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-free-bookly-activator.php';
		// Richiede il module manager
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-module-manager.php';
	}

	private function set_locale() {
		// TODO: load_plugin_textdomain
	}

	private function define_admin_hooks() {
		// TODO: Registrazione menu admin
	}

	private function define_public_hooks() {
		// TODO: Registrazione shortcode e script JS frontend
	}

	private function init_module_manager() {
		$this->module_manager = new Free_Bookly_Module_Manager();
		
		// Qui si registrerebbero i moduli se esistessero
		// es: $this->module_manager->register_module('stripe_payments', array(...));
		
		$this->module_manager->load_active_modules();
	}

	public function run() {
		// Aggancia l'attivazione
		register_activation_hook( dirname( dirname( __FILE__ ) ) . '/free-bookly.php', array( 'Free_Bookly_Activator', 'activate' ) );
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
	
	public function get_module_manager() {
		return $this->module_manager;
	}
}
