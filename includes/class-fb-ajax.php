<?php

class FB_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_fb_get_available_slots', array( $this, 'get_available_slots' ) );
		add_action( 'wp_ajax_nopriv_fb_get_available_slots', array( $this, 'get_available_slots' ) );

		add_action( 'wp_ajax_fb_save_appointment', array( $this, 'save_appointment' ) );
		add_action( 'wp_ajax_nopriv_fb_save_appointment', array( $this, 'save_appointment' ) );
	}

	public function get_available_slots() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$staff_id   = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;
		$date       = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';

		if ( ! $service_id || ! $date ) {
			wp_send_json_error( 'Parametri mancanti.' );
		}

		require_once dirname( __FILE__ ) . '/class-fb-availability.php';
		$checker = new FB_Availability();
		$slots = $checker->get_available_slots( $service_id, $staff_id, $date );

		wp_send_json_success( $slots );
	}

	public function save_appointment() {
		check_ajax_referer( 'fb_wizard_nonce', 'nonce' );

		$payload_raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
		$payload = json_decode( $payload_raw, true );

		if ( ! $payload ) {
			wp_send_json_error( 'Payload non valido.' );
		}

		// 1. Salva o recupera Cliente
		require_once dirname( __FILE__ ) . '/class-fb-customer.php';
		$customer = new FB_Customer( array(
			'full_name' => $payload['user']['name'],
			'email'     => $payload['user']['email'],
			'phone'     => $payload['user']['phone'],
		) );
		$customer->save();

		// 2. Calcola Date
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		$service = FB_Service::get_by_id( $payload['service_id'] );
		$duration = $service ? $service->duration : 60;
		
		$start_date = $payload['date'] . ' ' . $payload['time'] . ':00';
		$start_obj = new DateTime( $start_date );
		$end_obj = clone $start_obj;
		$end_obj->modify( '+' . $duration . ' minutes' );
		$end_date = $end_obj->format( 'Y-m-d H:i:s' );

		// 3. Salva Appuntamento
		require_once dirname( __FILE__ ) . '/class-fb-appointment.php';
		$appointment = new FB_Appointment( array(
			'staff_id'    => $payload['staff_id'] ? $payload['staff_id'] : 1, // Fallback
			'service_id'  => $payload['service_id'],
			'customer_id' => $customer->id,
			'start_date'  => $start_date,
			'end_date'    => $end_date,
			'status'      => 'pending'
		) );

		if ( $appointment->save() ) {
			wp_send_json_success( 'Prenotazione confermata!' );
		} else {
			wp_send_json_error( 'Errore durante il salvataggio.' );
		}
	}
}

new FB_Ajax();
