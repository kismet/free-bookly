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
		$plugin_path = plugin_dir_path( dirname( __FILE__ ) );

		// Core Activator & Module Manager
		require_once $plugin_path . 'includes/class-free-bookly-activator.php';
		require_once $plugin_path . 'includes/class-module-manager.php';

		// Modelli e Helpers
		require_once $plugin_path . 'includes/class-fb-settings.php';
		require_once $plugin_path . 'includes/class-fb-category.php';
		require_once $plugin_path . 'includes/class-fb-holiday.php';
		require_once $plugin_path . 'includes/class-fb-service.php';
		require_once $plugin_path . 'includes/class-fb-staff.php';
		require_once $plugin_path . 'includes/class-fb-staff-schedule.php';
		require_once $plugin_path . 'includes/class-fb-customer.php';
		require_once $plugin_path . 'includes/class-fb-appointment.php';

		// Availability Engine & AJAX
		require_once $plugin_path . 'includes/class-fb-availability.php';
		require_once $plugin_path . 'includes/class-fb-ajax.php';

		// Admin & Public Controllers
		require_once $plugin_path . 'admin/class-free-bookly-admin.php';
		require_once $plugin_path . 'public/class-free-bookly-public.php';
	}

	private function set_locale() {
		load_plugin_textdomain(
			'free-bookly',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	private function define_admin_hooks() {
		$admin = new Free_Bookly_Admin( $this->get_plugin_name(), $this->get_version() );

		add_action( 'admin_menu', array( $admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $admin, 'handle_form_actions' ) );
	}

	private function define_public_hooks() {
		$public = new Free_Bookly_Public( $this->get_plugin_name(), $this->get_version() );

		add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_scripts' ) );
		add_action( 'init', array( $public, 'register_shortcode' ) );
	}

	private function init_module_manager() {
		$this->module_manager = new Free_Bookly_Module_Manager();

		$plugin_path = plugin_dir_path( dirname( __FILE__ ) );

		// Modulo 1: Notifiche Email & Calendario (.ics)
		$this->module_manager->register_module( 'email_notifications', array(
			'name'       => 'Notifiche Email & Calendario',
			'class_file' => $plugin_path . 'includes/modules/class-fb-email-notifications.php',
			'class_name' => 'FB_Email_Notifications',
		) );

		// Modulo 4a: Servizi Extra & Add-ons
		$this->module_manager->register_module( 'service_extras', array(
			'name'       => 'Servizi Extra & Add-ons',
			'class_file' => $plugin_path . 'includes/modules/class-fb-service-extras-module.php',
			'class_name' => 'FB_Service_Extras_Module',
		) );

		// Modulo 4b: Campi Personalizzati (Custom Fields)
		$this->module_manager->register_module( 'custom_fields', array(
			'name'       => 'Campi Personalizzati',
			'class_file' => $plugin_path . 'includes/modules/class-fb-custom-fields-module.php',
			'class_name' => 'FB_Custom_Fields_Module',
		) );

		$this->module_manager->load_active_modules();
	}

	public function run() {
		// Aggancia l'attivazione
		register_activation_hook( dirname( dirname( __FILE__ ) ) . '/free-bookly.php', array( 'Free_Bookly_Activator', 'activate' ) );

		// Auto-migrazione database su aggiornamenti dello schema
		add_action( 'admin_init', array( $this, 'maybe_update_db' ) );
	}

	public function maybe_update_db() {
		global $wpdb;
		$cat_table = $wpdb->prefix . 'fb_categories';
		$current_db_version = get_option( 'fb_db_version' );

		if ( $current_db_version !== Free_Bookly_Activator::DB_VERSION || $wpdb->get_var( "SHOW TABLES LIKE '$cat_table'" ) !== $cat_table ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-free-bookly-activator.php';
			Free_Bookly_Activator::activate();
		}
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
