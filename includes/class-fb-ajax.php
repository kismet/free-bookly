<?php

class FB_Ajax {

	public function __construct() {
		// Categorie
		add_action( 'wp_ajax_fb_get_categories', array( $this, 'get_categories' ) );
		add_action( 'wp_ajax_nopriv_fb_get_categories', array( $this, 'get_categories' ) );

		// Servizi
		add_action( 'wp_ajax_fb_get_services', array( $this, 'get_services' ) );
		add_action( 'wp_ajax_nopriv_fb_get_services', array( $this, 'get_services' ) );

		// Staff
		add_action( 'wp_ajax_fb_get_staff', array( $this, 'get_staff' ) );
		add_action( 'wp_ajax_nopriv_fb_get_staff', array( $this, 'get_staff' ) );

		// Slot orari disponibili
		add_action( 'wp_ajax_fb_get_available_slots', array( $this, 'get_available_slots' ) );
		add_action( 'wp_ajax_nopriv_fb_get_available_slots', array( $this, 'get_available_slots' ) );

		// Salvataggio Appuntamento
		add_action( 'wp_ajax_fb_save_appointment', array( $this, 'save_appointment' ) );
		add_action( 'wp_ajax_nopriv_fb_save_appointment', array( $this, 'save_appointment' ) );
	}

	/**
	 * Ritorna l'elenco delle categorie di servizi.
	 */
	public function get_categories() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-fb-category.php';
		$categories = FB_Category::get_all();

		$data = array();
		foreach ( $categories as $cat ) {
			$data[] = array(
				'id'          => $cat->id,
				'name'        => $cat->name,
				'description' => $cat->description,
				'image_url'   => $cat->image_url,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Ritorna l'elenco dei servizi (filtrabili per categoria e solo pubblici).
	 */
	public function get_services() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';

		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;

		if ( $category_id > 0 ) {
			$services = FB_Service::get_by_category( $category_id, true );
		} else {
			$services = FB_Service::get_all( true );
		}

		$data = array();
		foreach ( $services as $service ) {
			$data[] = array(
				'id'              => $service->id,
				'category_id'     => $service->category_id,
				'category_name'   => $service->get_category_name(),
				'title'           => $service->title,
				'duration'        => $service->duration,
				'price'           => number_format( (float) $service->price, 2, '.', '' ),
				'formatted_price' => $service->get_formatted_price(),
				'image_url'       => $service->image_url,
				'color'           => $service->color,
				'info'            => $service->info,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Ritorna l'elenco dei membri dello staff abilitati (solo pubblici e attivi).
	 */
	public function get_staff() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		require_once dirname( __FILE__ ) . '/class-fb-staff.php';
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$service = $service_id ? FB_Service::get_by_id( $service_id ) : null;

		if ( $service_id > 0 ) {
			$staff_list = FB_Staff::get_by_service_id( $service_id, true );
			if ( empty( $staff_list ) ) {
				$staff_list = FB_Staff::get_all( false, true );
			}
		} else {
			$staff_list = FB_Staff::get_all( false, true );
		}

		$data = array();
		foreach ( $staff_list as $staff ) {
			$custom_price = $service_id ? $staff->get_custom_price( $service_id ) : null;
			$price_to_format = ( null !== $custom_price ) ? $custom_price : ( $service ? $service->price : 0 );

			$data[] = array(
				'id'              => $staff->id,
				'full_name'       => $staff->full_name,
				'avatar_url'      => $staff->avatar_url,
				'color'           => $staff->color,
				'info'            => $staff->info,
				'custom_price'    => $custom_price,
				'formatted_price' => FB_Settings::format_price( $price_to_format ),
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Calcola e ritorna gli slot disponibili per servizio, staff e data.
	 */
	public function get_available_slots() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$staff_id   = isset( $_POST['staff_id'] ) ? sanitize_text_field( $_POST['staff_id'] ) : '';
		$date       = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';

		if ( ! $service_id || ! $date ) {
			wp_send_json_error( __( 'Parametri mancanti.', 'free-bookly' ) );
		}

		require_once dirname( __FILE__ ) . '/class-fb-availability.php';
		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service_id, $staff_id, $date );

		wp_send_json_success( $slots );
	}

	/**
	 * Salva la prenotazione nel database con stato configurato e fornitore automatico.
	 */
	public function save_appointment() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		$payload_raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
		$payload = json_decode( $payload_raw, true );

		if ( ! $payload || empty( $payload['service_id'] ) || empty( $payload['date'] ) || empty( $payload['time'] ) ) {
			wp_send_json_error( __( 'Dati prenotazione non validi.', 'free-bookly' ) );
		}

		// 1. Salva o aggiorna Cliente
		require_once dirname( __FILE__ ) . '/class-fb-customer.php';
		$customer = new FB_Customer( array(
			'full_name' => sanitize_text_field( $payload['user']['name'] ),
			'email'     => sanitize_email( $payload['user']['email'] ),
			'phone'     => sanitize_text_field( $payload['user']['phone'] ),
		) );
		$customer->save();

		// 2. Calcola Date e Orari
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';

		$service = FB_Service::get_by_id( $payload['service_id'] );
		$duration = $service ? $service->duration : 60;
		
		$start_date = $payload['date'] . ' ' . $payload['time'] . ':00';
		$start_obj = new DateTime( $start_date );
		$end_obj = clone $start_obj;
		$end_obj->modify( '+' . $duration . ' minutes' );
		$end_date = $end_obj->format( 'Y-m-d H:i:s' );

		// 3. Risolvi lo Staff: se "any" o non specificato, assegna secondo la regola del servizio
		require_once dirname( __FILE__ ) . '/class-fb-availability.php';
		if ( empty( $payload['staff_id'] ) || 'any' === (string) $payload['staff_id'] ) {
			$assigned_staff_id = FB_Availability::assign_staff_for_any( $payload['service_id'], $payload['date'], $payload['time'] );
		} else {
			$assigned_staff_id = absint( $payload['staff_id'] );
		}

		// 4. Stato predefinito configurato in Impostazioni Generali
		$default_status = FB_Settings::get_default_appointment_status();

		// 5. Salva Appuntamento
		require_once dirname( __FILE__ ) . '/class-fb-appointment.php';
		$appointment = new FB_Appointment( array(
			'staff_id'    => $assigned_staff_id,
			'service_id'  => $payload['service_id'],
			'customer_id' => $customer->id,
			'start_date'  => $start_date,
			'end_date'    => $end_date,
			'status'      => $default_status,
		) );

		if ( $appointment->save() ) {
			wp_send_json_success( array(
				'message'        => __( 'Prenotazione confermata con successo!', 'free-bookly' ),
				'appointment_id' => $appointment->id,
				'status'         => $default_status,
			) );
		} else {
			wp_send_json_error( __( 'Errore durante il salvataggio della prenotazione.', 'free-bookly' ) );
		}
	}
}

new FB_Ajax();
