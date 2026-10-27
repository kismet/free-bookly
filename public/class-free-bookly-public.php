<?php

class Free_Bookly_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/free-bookly-public.css', array( 'dashicons' ), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		// Controllo opzione caricamento script: tutte le pagine vs solo pagine con shortcode
		if ( class_exists( 'FB_Settings' ) && 'booking_pages' === FB_Settings::get_script_loading() ) {
			global $post;
			if ( ! is_a( $post, 'WP_Post' ) || ( ! has_shortcode( $post->post_content, 'free_bookly' ) && ! has_shortcode( $post->post_content, 'free_bookly_services' ) && ! has_shortcode( $post->post_content, 'free_bookly_staff' ) && ! has_shortcode( $post->post_content, 'free_bookly_search' ) ) ) {
				return;
			}
		}

		// Wizard in Vanilla JavaScript nativo (zero framework esterni pesanti)
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/free-bookly-wizard.js', array(), $this->version, true );
		
		wp_localize_script( $this->plugin_name, 'fb_ajax', array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'fb_wizard_nonce' ),
			'currency_symbol' => class_exists( 'FB_Settings' ) ? FB_Settings::get_currency_symbol() : '€',
		));
	}

	public function register_shortcode() {
		add_shortcode( 'free_bookly', array( $this, 'render_booking_form' ) );
		add_shortcode( 'free_bookly_services', array( $this, 'render_services_form' ) );
		add_shortcode( 'free_bookly_staff', array( $this, 'render_staff_form' ) );
		add_shortcode( 'free_bookly_search', array( $this, 'render_search_form' ) );
	}

	public function render_booking_form( $atts ) {
		$atts = shortcode_atts( array(
			'form'        => 'classic',
			'service_id'  => 0,
			'category_id' => 0,
			'staff_id'    => 'any',
		), $atts, 'free_bookly' );

		if ( 'services' === $atts['form'] ) {
			return $this->render_services_form( $atts );
		}
		if ( 'staff' === $atts['form'] ) {
			return $this->render_staff_form( $atts );
		}
		if ( 'search' === $atts['form'] ) {
			return $this->render_search_form( $atts );
		}

		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/free-bookly-public-display.php';
		return ob_get_clean();
	}

	public function render_services_form( $atts = array() ) {
		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/free-bookly-services-catalog.php';
		return ob_get_clean();
	}

	public function render_staff_form( $atts = array() ) {
		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/free-bookly-staff-first.php';
		return ob_get_clean();
	}

	public function render_search_form( $atts = array() ) {
		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/free-bookly-search-form.php';
		return ob_get_clean();
	}
}
