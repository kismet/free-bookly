<?php

class FB_Appointment {

	public $id;
	public $staff_id;
	public $service_id;
	public $customer_id;
	public $start_date;
	public $end_date;
	public $status;
	public $custom_fields;
	public $extras_price;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id            = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->staff_id      = isset( $data['staff_id'] ) ? absint( $data['staff_id'] ) : 0;
			$this->service_id    = isset( $data['service_id'] ) ? absint( $data['service_id'] ) : 0;
			$this->customer_id   = isset( $data['customer_id'] ) ? absint( $data['customer_id'] ) : 0;
			$this->start_date    = isset( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : '';
			$this->end_date      = isset( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : '';
			$this->status        = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'pending';
			$this->custom_fields = isset( $data['custom_fields'] ) ? $data['custom_fields'] : '';
			$this->extras_price  = isset( $data['extras_price'] ) ? floatval( $data['extras_price'] ) : 0.00;
		}
	}

	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_appointments';

		$data = array(
			'staff_id'      => $this->staff_id,
			'service_id'    => $this->service_id,
			'customer_id'   => $this->customer_id,
			'start_date'    => $this->start_date,
			'end_date'      => $this->end_date,
			'status'        => $this->status,
			'custom_fields' => is_array( $this->custom_fields ) ? wp_json_encode( $this->custom_fields ) : (string) $this->custom_fields,
			'extras_price'  => $this->extras_price,
		);

		$format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f' );

		if ( $this->id ) {
			return $wpdb->update( $table_name, $data, array( 'id' => $this->id ), $format, array( '%d' ) ) !== false;
		} else {
			$result = $wpdb->insert( $table_name, $data, $format );
			if ( $result ) {
				$this->id = $wpdb->insert_id;
				return true;
			}
		}

		return false;
	}

	public static function get_by_id( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_appointments';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

		if ( $row ) {
			return new self( $row );
		}

		return null;
	}

	/**
	 * Aggiorna lo stato di un appuntamento e notifica i moduli interessati.
	 *
	 * Scatta l'azione `fb_appointment_status_changed( $id, $new_status, $old_status )`
	 * solo se lo stato è realmente cambiato.
	 *
	 * @param int    $id
	 * @param string $new_status pending | approved | cancelled
	 * @return bool
	 */
	public static function update_status( $id, $new_status ) {
		global $wpdb;

		$appointment = self::get_by_id( $id );
		if ( ! $appointment ) {
			return false;
		}

		$old_status = $appointment->status;
		if ( $old_status === $new_status ) {
			return true;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'fb_appointments',
			array( 'status' => $new_status ),
			array( 'id' => (int) $id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return false;
		}

		do_action( 'fb_appointment_status_changed', (int) $id, $new_status, $old_status );
		return true;
	}

	/**
	 * Recupera gli extra selezionati per questo appuntamento.
	 *
	 * @return array
	 */
	public function get_extras() {
		global $wpdb;
		if ( ! $this->id ) {
			return array();
		}

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fb_appointment_extras WHERE appointment_id = %d ORDER BY id ASC", $this->id ),
			ARRAY_A
		);
	}

	/**
	 * Ritorna i campi personalizzati decodificati in array.
	 *
	 * @return array
	 */
	public function get_custom_fields_data() {
		if ( empty( $this->custom_fields ) ) {
			return array();
		}

		if ( is_array( $this->custom_fields ) ) {
			return $this->custom_fields;
		}

		$decoded = json_decode( $this->custom_fields, true );
		return is_array( $decoded ) ? $decoded : array();
	}
}
