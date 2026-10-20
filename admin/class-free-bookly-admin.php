<?php

/**
 * Controller per la gestione dell'area di amministrazione di Free Bookly.
 */
class Free_Bookly_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Carica gli stili per l'area admin
	 */
	public function enqueue_styles( $hook ) {
		// Carica solo sulle pagine di Free Bookly
		if ( strpos( $hook, 'free-bookly' ) === false ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			plugins_url( 'css/free-bookly-admin.css', __FILE__ ),
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Carica gli script per l'area admin
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'free-bookly' ) === false ) {
			return;
		}

		// Eventuali script JS per l'admin possono essere registrati qui
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
			'Dashboard',
			'Dashboard',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_dashboard' )
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

	/**
	 * Intercetta e gestisce le richieste POST/GET prima del rendering dell'admin.
	 */
	public function handle_form_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// 1. Gestione Salvataggio Servizio
		if ( isset( $_POST['action'] ) && 'fb_save_service' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_service_action', 'fb_service_nonce' );

			$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
			$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$duration   = isset( $_POST['duration'] ) ? absint( $_POST['duration'] ) : 60;
			$price      = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0.00;
			$capacity   = isset( $_POST['capacity'] ) ? absint( $_POST['capacity'] ) : 1;

			if ( empty( $title ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&error=' . urlencode( 'Il titolo del servizio è obbligatorio.' ) ) );
				exit;
			}

			$service = new FB_Service( array(
				'id'       => $service_id,
				'title'    => $title,
				'duration' => $duration,
				'price'    => $price,
				'capacity' => $capacity,
			) );

			$service->save();

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&message=saved' ) );
			exit;
		}

		// 2. Gestione Eliminazione Servizio
		if ( isset( $_GET['page'] ) && 'free-bookly-services' === $_GET['page'] && isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$service_id = absint( $_GET['id'] );
			check_admin_referer( 'fb_delete_service_' . $service_id );

			$service = FB_Service::get_by_id( $service_id );
			if ( $service ) {
				$service->delete();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&message=deleted' ) );
			exit;
		}

		// 3. Gestione Salvataggio Staff
		if ( isset( $_POST['action'] ) && 'fb_save_staff' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_staff_action', 'fb_staff_nonce' );

			$staff_id   = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;
			$full_name  = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
			$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
			$wp_user_id = ! empty( $_POST['wp_user_id'] ) ? absint( $_POST['wp_user_id'] ) : null;
			$services   = isset( $_POST['services'] ) && is_array( $_POST['services'] ) ? array_map( 'absint', $_POST['services'] ) : array();

			if ( empty( $full_name ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&error=' . urlencode( 'Il nome dello staff è obbligatorio.' ) ) );
				exit;
			}

			$staff = new FB_Staff( array(
				'id'         => $staff_id,
				'full_name'  => $full_name,
				'email'      => $email,
				'phone'      => $phone,
				'wp_user_id' => $wp_user_id,
			) );

			if ( $staff->save() ) {
				$staff->set_services( $services );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&message=saved' ) );
			exit;
		}

		// 4. Gestione Eliminazione Staff
		if ( isset( $_GET['page'] ) && 'free-bookly-staff' === $_GET['page'] && isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$staff_id = absint( $_GET['id'] );
			check_admin_referer( 'fb_delete_staff_' . $staff_id );

			$staff = FB_Staff::get_by_id( $staff_id );
			if ( $staff ) {
				$staff->delete();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&message=deleted' ) );
			exit;
		}
	}

	public function display_dashboard() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-display.php';
	}

	public function display_calendar() {
		echo '<div class="wrap"><h1>Calendario Appuntamenti</h1><p>Vista calendario per visualizzare e filtrare gli appuntamenti.</p></div>';
	}

	public function display_staff() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-staff.php';
	}

	public function display_services() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-services.php';
	}

	public function display_modules() {
		echo '<div class="wrap"><h1>Moduli Extra (Add-ons)</h1><p>Abilita o disabilita moduli per espandere le funzionalità senza pesare sulle prestazioni.</p></div>';
	}
}
