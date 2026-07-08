<?php

class Free_Bookly_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/free-bookly-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/free-bookly-wizard.js', array( 'jquery' ), $this->version, true );
		
		wp_localize_script( $this->plugin_name, 'fb_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'fb_wizard_nonce' )
		));
	}

	public function register_shortcode() {
		add_shortcode( 'free_bookly', array( $this, 'display_wizard' ) );
	}

	public function display_wizard( $atts ) {
		ob_start();
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-public-display.php';
		return ob_get_clean();
	}
}
