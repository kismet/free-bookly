<?php

class FB_Appointment {

	public $id;
	public $staff_id;
	public $service_id;
	public $customer_id;
	public $start_date;
	public $end_date;
	public $status;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id          = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->staff_id    = isset( $data['staff_id'] ) ? absint( $data['staff_id'] ) : 0;
			$this->service_id  = isset( $data['service_id'] ) ? absint( $data['service_id'] ) : 0;
			$this->customer_id = isset( $data['customer_id'] ) ? absint( $data['customer_id'] ) : 0;
			$this->start_date  = isset( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : '';
			$this->end_date    = isset( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : '';
			$this->status      = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'pending';
		}
	}

	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_appointments';

		$data = array(
			'staff_id'    => $this->staff_id,
			'service_id'  => $this->service_id,
			'customer_id' => $this->customer_id,
			'start_date'  => $this->start_date,
			'end_date'    => $this->end_date,
			'status'      => $this->status,
		);

		$format = array( '%d', '%d', '%d', '%s', '%s', '%s' );

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
}
