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

		// 5. Gestione Cambio Stato Appuntamento (Approva / Annulla)
		if ( isset( $_GET['page'] ) && 'free-bookly-calendar' === $_GET['page'] && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'approve', 'cancel' ), true ) && isset( $_GET['id'] ) ) {
			$app_id = absint( $_GET['id'] );
			check_admin_referer( 'fb_status_app_' . $app_id );

			$new_status = 'approve' === $_GET['action'] ? 'approved' : 'cancelled';
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'fb_appointments',
				array( 'status' => $new_status ),
				array( 'id' => $app_id ),
				array( '%s' ),
				array( '%d' )
			);

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-calendar&message=' . ( 'approve' === $_GET['action'] ? 'approved' : 'cancelled' ) ) );
			exit;
		}

		// 6. Gestione Eliminazione Appuntamento
		if ( isset( $_GET['page'] ) && 'free-bookly-calendar' === $_GET['page'] && isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$app_id = absint( $_GET['id'] );
			check_admin_referer( 'fb_delete_app_' . $app_id );

			global $wpdb;
			$wpdb->delete(
				$wpdb->prefix . 'fb_appointments',
				array( 'id' => $app_id ),
				array( '%d' )
			);

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-calendar&message=deleted' ) );
			exit;
		}

		// 7. Gestione Salvataggio Moduli Attivi
		if ( isset( $_POST['action'] ) && 'fb_save_modules' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_modules_action', 'fb_modules_nonce' );

			$modules = isset( $_POST['active_modules'] ) && is_array( $_POST['active_modules'] )
				? array_map( 'sanitize_key', $_POST['active_modules'] )
				: array();

			$module_manager = new Free_Bookly_Module_Manager();
			$module_manager->set_active_modules( $modules );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-modules&message=saved' ) );
			exit;
		}

		// 8. Gestione Salvataggio Orari e Pause Staff
		if ( isset( $_POST['action'] ) && 'fb_save_staff_schedule' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_schedule_action', 'fb_schedule_nonce' );

			$staff_id     = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;
			$raw_schedule = isset( $_POST['schedule'] ) && is_array( $_POST['schedule'] ) ? $_POST['schedule'] : array();

			$formatted_schedule = array();
			for ( $day = 1; $day <= 7; $day++ ) {
				$day_in     = isset( $raw_schedule[ $day ] ) ? $raw_schedule[ $day ] : array();
				$is_working = ! empty( $day_in['is_working_day'] ) ? 1 : 0;
				$start_time = ! empty( $day_in['start_time'] ) ? sanitize_text_field( $day_in['start_time'] ) : '09:00';
				$end_time   = ! empty( $day_in['end_time'] ) ? sanitize_text_field( $day_in['end_time'] ) : '18:00';

				$breaks = array();
				if ( ! empty( $day_in['break_start'] ) && ! empty( $day_in['break_end'] ) ) {
					$breaks[] = array(
						'start_time' => sanitize_text_field( $day_in['break_start'] ),
						'end_time'   => sanitize_text_field( $day_in['break_end'] ),
					);
				}

				$formatted_schedule[ $day ] = array(
					'is_working_day' => $is_working,
					'start_time'     => $start_time,
					'end_time'       => $end_time,
					'breaks'         => $breaks,
				);
			}

			FB_Staff_Schedule::save_schedule( $staff_id, $formatted_schedule );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&view=schedule&staff_id=' . $staff_id . '&message=schedule_saved' ) );
			exit;
		}
	}

	public function display_dashboard() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-display.php';
	}

	public function display_calendar() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-calendar.php';
	}

	public function display_staff() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-staff.php';
	}

	public function display_services() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-services.php';
	}

	public function display_modules() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-modules.php';
	}
}
