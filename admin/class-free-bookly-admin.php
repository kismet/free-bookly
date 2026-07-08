<?php

class Free_Bookly_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Carica gli stili per l'area admin
	 */
	public function enqueue_styles() {
		// wp_enqueue_style(...)
	}

	/**
	 * Carica gli script per l'area admin
	 */
	public function enqueue_scripts() {
		// wp_enqueue_script(...)
	}

	/**
	 * Registra i menu nel pannello di amministrazione di WordPress.
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			'Free Bookly', 
			'Free Bookly', 
			'manage_options', 
			$this->plugin_name, 
			array( $this, 'display_dashboard' ), 
			'dashicons-calendar-alt', 
			26
		);

		add_submenu_page(
			$this->plugin_name,
			'Calendario',
			'Calendario',
			'manage_options',
			$this->plugin_name . '-calendar',
			array( $this, 'display_calendar' )
		);

		add_submenu_page(
			$this->plugin_name,
			'Staff',
			'Staff',
			'manage_options',
			$this->plugin_name . '-staff',
			array( $this, 'display_staff' )
		);

		add_submenu_page(
			$this->plugin_name,
			'Servizi',
			'Servizi',
			'manage_options',
			$this->plugin_name . '-services',
			array( $this, 'display_services' )
		);

		add_submenu_page(
			$this->plugin_name,
			'Moduli',
			'Moduli Extra',
			'manage_options',
			$this->plugin_name . '-modules',
			array( $this, 'display_modules' )
		);
	}

	public function display_dashboard() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-display.php';
	}

	public function display_calendar() {
		echo '<div class="wrap"><h1>Calendario Appuntamenti</h1><p>Qui andrà la griglia visiva degli appuntamenti.</p></div>';
	}

	public function display_staff() {
		echo '<div class="wrap"><h1>Gestione Staff</h1><p>Tabella per aggiungere/modificare i membri del team e orari.</p></div>';
	}

	public function display_services() {
		echo '<div class="wrap"><h1>Gestione Servizi</h1><p>Aggiungi/Modifica servizi, durata, e prezzi.</p></div>';
	}

	public function display_modules() {
		echo '<div class="wrap"><h1>Moduli Extra (Add-ons)</h1><p>Abilita o disabilita moduli per espandere le funzionalità senza pesare sulle prestazioni.</p></div>';
	}
}
