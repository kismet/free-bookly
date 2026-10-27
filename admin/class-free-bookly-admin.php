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
			'Impostazioni',
			'Impostazioni',
			'manage_options',
			$this->plugin_name . '-settings',
			array( $this, 'display_settings' )
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

		// 1. Salvataggio Servizio
		if ( isset( $_POST['action'] ) && 'fb_save_service' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_service_action', 'fb_service_nonce' );

			$service_id        = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
			$category_id       = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
			$title             = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$duration          = isset( $_POST['duration'] ) ? absint( $_POST['duration'] ) : 60;
			$price             = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0.00;
			$capacity          = isset( $_POST['capacity'] ) ? absint( $_POST['capacity'] ) : 1;
			$image_url         = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
			$color             = isset( $_POST['color'] ) ? sanitize_hex_color( $_POST['color'] ) : '#0284c7';
			$visibility        = isset( $_POST['visibility'] ) && 'private' === $_POST['visibility'] ? 'private' : 'public';
			$info              = isset( $_POST['info'] ) ? wp_kses_post( wp_unslash( $_POST['info'] ) ) : '';
			$time_slot_length  = isset( $_POST['time_slot_length'] ) ? absint( $_POST['time_slot_length'] ) : 0;
			$buffer_before     = isset( $_POST['buffer_before'] ) ? absint( $_POST['buffer_before'] ) : 0;
			$buffer_after      = isset( $_POST['buffer_after'] ) ? absint( $_POST['buffer_after'] ) : 0;
			$any_provider_rule = isset( $_POST['any_provider_rule'] ) ? sanitize_text_field( $_POST['any_provider_rule'] ) : 'random';

			if ( empty( $title ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&error=' . urlencode( 'Il titolo del servizio è obbligatorio.' ) ) );
				exit;
			}

			$service = new FB_Service( array(
				'id'                => $service_id,
				'category_id'       => $category_id,
				'title'             => $title,
				'duration'          => $duration,
				'price'             => $price,
				'capacity'          => $capacity,
				'image_url'         => $image_url,
				'color'             => $color,
				'visibility'        => $visibility,
				'info'              => $info,
				'time_slot_length'  => $time_slot_length,
				'buffer_before'     => $buffer_before,
				'buffer_after'      => $buffer_after,
				'any_provider_rule' => $any_provider_rule,
			) );

			$service->save();

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&message=saved' ) );
			exit;
		}

		// 2. Eliminazione Servizio
		if ( isset( $_GET['page'] ) && 'free-bookly-services' === $_GET['page'] && isset( $_GET['action'] ) && 'fb_delete_service' === $_GET['action'] && isset( $_GET['service_id'] ) ) {
			check_admin_referer( 'fb_delete_service_action' );
			$service_id = absint( $_GET['service_id'] );

			$service = FB_Service::get_by_id( $service_id );
			if ( $service ) {
				$service->delete();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&message=deleted' ) );
			exit;
		}

		// 3. Salvataggio Categoria
		if ( isset( $_POST['action'] ) && 'fb_save_category' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_category_action', 'fb_category_nonce' );

			$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
			$image_url   = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
			$sort_order  = isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0;

			if ( ! empty( $name ) ) {
				$cat = new FB_Category( array(
					'name'        => $name,
					'description' => $description,
					'image_url'   => $image_url,
					'sort_order'  => $sort_order,
				) );
				$cat->save();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&view=categories&message=saved' ) );
			exit;
		}

		// 4. Eliminazione Categoria
		if ( isset( $_GET['page'] ) && 'free-bookly-services' === $_GET['page'] && isset( $_GET['action'] ) && 'fb_delete_category' === $_GET['action'] && isset( $_GET['cat_id'] ) ) {
			check_admin_referer( 'fb_delete_category_action' );
			$cat_id = absint( $_GET['cat_id'] );

			$cat = FB_Category::get_by_id( $cat_id );
			if ( $cat ) {
				$cat->delete();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-services&view=categories&message=deleted' ) );
			exit;
		}

		// 5. Salvataggio Staff
		if ( isset( $_POST['action'] ) && 'fb_save_staff' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_staff_action', 'fb_staff_nonce' );

			$staff_id   = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;
			$full_name  = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
			$email      = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
			$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
			$wp_user_id = ! empty( $_POST['wp_user_id'] ) ? absint( $_POST['wp_user_id'] ) : null;
			$avatar_url = isset( $_POST['avatar_url'] ) ? esc_url_raw( wp_unslash( $_POST['avatar_url'] ) ) : '';
			$color      = isset( $_POST['color'] ) ? sanitize_hex_color( $_POST['color'] ) : '#0284c7';
			$visibility = isset( $_POST['visibility'] ) && 'private' === $_POST['visibility'] ? 'private' : 'public';
			$info       = isset( $_POST['info'] ) ? wp_kses_post( wp_unslash( $_POST['info'] ) ) : '';
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
				'avatar_url' => $avatar_url,
				'color'      => $color,
				'visibility' => $visibility,
				'info'       => $info,
			) );

			if ( $staff->save() ) {
				$staff->set_services( $services );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&message=saved' ) );
			exit;
		}

		// 6. Eliminazione Staff
		if ( isset( $_GET['page'] ) && 'free-bookly-staff' === $_GET['page'] && isset( $_GET['action'] ) && 'fb_delete_staff' === $_GET['action'] && isset( $_GET['staff_id'] ) ) {
			check_admin_referer( 'fb_delete_staff_action' );
			$staff_id = absint( $_GET['staff_id'] );

			$staff = FB_Staff::get_by_id( $staff_id );
			if ( $staff ) {
				$staff->delete();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&message=deleted' ) );
			exit;
		}

		// 7. Archivia / Ripristina Staff
		if ( isset( $_GET['page'] ) && 'free-bookly-staff' === $_GET['page'] && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'fb_archive_staff', 'fb_unarchive_staff' ), true ) && isset( $_GET['staff_id'] ) ) {
			$action = sanitize_key( $_GET['action'] );
			check_admin_referer( $action . '_action' );
			$staff_id = absint( $_GET['staff_id'] );
			$staff = FB_Staff::get_by_id( $staff_id );

			if ( $staff ) {
				if ( 'fb_archive_staff' === $action ) {
					$staff->archive();
					$msg = 'archived';
				} else {
					$staff->unarchive();
					$msg = 'unarchived';
				}
				wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&message=' . $msg ) );
				exit;
			}
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

		// 9. Gestione Ferie e Giorni Liberi Staff
		if ( isset( $_POST['action'] ) && 'fb_add_staff_holiday' === $_POST['action'] ) {
			check_admin_referer( 'fb_staff_holiday_action', 'fb_staff_holiday_nonce' );

			$staff_id      = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;
			$holiday_date  = isset( $_POST['holiday_date'] ) ? sanitize_text_field( $_POST['holiday_date'] ) : '';
			$holiday_title = isset( $_POST['holiday_title'] ) ? sanitize_text_field( $_POST['holiday_title'] ) : '';
			$repeat_yearly = ! empty( $_POST['repeat_yearly'] ) ? 1 : 0;

			if ( $staff_id && $holiday_date ) {
				$holiday = new FB_Holiday( array(
					'staff_id'      => $staff_id,
					'holiday_date'  => $holiday_date,
					'title'         => $holiday_title,
					'repeat_yearly' => $repeat_yearly,
				) );
				$holiday->save();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&view=holidays&staff_id=' . $staff_id . '&updated=1' ) );
			exit;
		}

		if ( isset( $_GET['page'] ) && 'free-bookly-staff' === $_GET['page'] && isset( $_GET['action'] ) && 'fb_delete_staff_holiday' === $_GET['action'] && isset( $_GET['holiday_id'] ) ) {
			check_admin_referer( 'fb_delete_staff_holiday_action' );
			$staff_id   = absint( $_GET['staff_id'] );
			$holiday_id = absint( $_GET['holiday_id'] );
			FB_Holiday::delete_by_id( $holiday_id );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-staff&view=holidays&staff_id=' . $staff_id . '&updated=1' ) );
			exit;
		}

		// 10. Gestione Impostazioni: Azienda
		if ( isset( $_POST['action'] ) && 'fb_save_settings_company' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_settings_action', 'fb_settings_nonce' );

			FB_Settings::set( 'company_name', sanitize_text_field( wp_unslash( $_POST['fb_company_name'] ) ) );
			FB_Settings::set( 'company_logo', esc_url_raw( wp_unslash( $_POST['fb_company_logo'] ) ) );
			FB_Settings::set( 'company_address', sanitize_textarea_field( wp_unslash( $_POST['fb_company_address'] ) ) );
			FB_Settings::set( 'company_phone', sanitize_text_field( wp_unslash( $_POST['fb_company_phone'] ) ) );
			FB_Settings::set( 'company_email', sanitize_email( wp_unslash( $_POST['fb_company_email'] ) ) );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-settings&tab=company&updated=1' ) );
			exit;
		}

		// 11. Gestione Impostazioni: Generale
		if ( isset( $_POST['action'] ) && 'fb_save_settings_general' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_settings_action', 'fb_settings_nonce' );

			FB_Settings::set( 'time_slot_length', absint( $_POST['fb_time_slot_length'] ) );
			FB_Settings::set( 'default_appointment_status', sanitize_key( $_POST['fb_default_appointment_status'] ) );
			FB_Settings::set( 'script_loading', sanitize_key( $_POST['fb_script_loading'] ) );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-settings&tab=general&updated=1' ) );
			exit;
		}

		// 12. Gestione Impostazioni: Orari Aziendali
		if ( isset( $_POST['action'] ) && 'fb_save_settings_working_hours' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_settings_action', 'fb_settings_nonce' );

			$days_input = isset( $_POST['days'] ) && is_array( $_POST['days'] ) ? $_POST['days'] : array();
			$company_hours = array();

			for ( $day = 1; $day <= 7; $day++ ) {
				$d_in = isset( $days_input[ $day ] ) ? $days_input[ $day ] : array();
				$company_hours[ $day ] = array(
					'is_working'  => ! empty( $d_in['is_working'] ) ? 1 : 0,
					'start_time'  => ! empty( $d_in['start_time'] ) ? sanitize_text_field( $d_in['start_time'] ) : '09:00',
					'end_time'    => ! empty( $d_in['end_time'] ) ? sanitize_text_field( $d_in['end_time'] ) : '18:00',
					'break_start' => ! empty( $d_in['break_start'] ) ? sanitize_text_field( $d_in['break_start'] ) : '',
					'break_end'   => ! empty( $d_in['break_end'] ) ? sanitize_text_field( $d_in['break_end'] ) : '',
				);
			}

			FB_Settings::set_company_working_hours( $company_hours );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-settings&tab=working_hours&updated=1' ) );
			exit;
		}

		// 13. Gestione Impostazioni: Festività Aziendali
		if ( isset( $_POST['action'] ) && 'fb_add_company_holiday' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_settings_action', 'fb_settings_nonce' );

			$date   = isset( $_POST['holiday_date'] ) ? sanitize_text_field( $_POST['holiday_date'] ) : '';
			$title  = isset( $_POST['holiday_title'] ) ? sanitize_text_field( $_POST['holiday_title'] ) : '';
			$repeat = ! empty( $_POST['repeat_yearly'] ) ? 1 : 0;

			if ( $date ) {
				$holiday = new FB_Holiday( array(
					'staff_id'      => 0, // Aziendale
					'holiday_date'  => $date,
					'title'         => $title,
					'repeat_yearly' => $repeat,
				) );
				$holiday->save();
			}

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-settings&tab=holidays&updated=1' ) );
			exit;
		}

		if ( isset( $_GET['page'] ) && 'free-bookly-settings' === $_GET['page'] && isset( $_GET['action'] ) && 'fb_delete_holiday' === $_GET['action'] && isset( $_GET['id'] ) ) {
			check_admin_referer( 'fb_delete_holiday_action' );
			$holiday_id = absint( $_GET['id'] );
			FB_Holiday::delete_by_id( $holiday_id );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-settings&tab=holidays&updated=1' ) );
			exit;
		}

		// 14. Gestione Impostazioni: Pagamenti e Valuta
		if ( isset( $_POST['action'] ) && 'fb_save_settings_payments' === $_POST['action'] ) {
			check_admin_referer( 'fb_save_settings_action', 'fb_settings_nonce' );

			FB_Settings::set( 'currency', sanitize_text_field( $_POST['fb_currency'] ) );
			FB_Settings::set( 'currency_format', sanitize_text_field( $_POST['fb_currency_format'] ) );

			wp_safe_redirect( admin_url( 'admin.php?page=free-bookly-settings&tab=payments&updated=1' ) );
			exit;
		}

		// 15. Gestione Cambio Stato Appuntamento (Approva / Annulla)
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

		// 16. Gestione Eliminazione Appuntamento
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

		// 17. Gestione Salvataggio Moduli Attivi
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

	public function display_settings() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-settings.php';
	}

	public function display_modules() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/free-bookly-admin-modules.php';
	}
}
