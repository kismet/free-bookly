<?php

/**
 * Modello che rappresenta i Servizi prenotabili.
 * Si occupa di leggere e scrivere i dati dalla tabella wp_fb_services.
 */
class FB_Service {

	public $id;
	public $title;
	public $duration;
	public $price;
	public $capacity;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id       = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->title    = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
			$this->duration = isset( $data['duration'] ) ? absint( $data['duration'] ) : 60;
			$this->price    = isset( $data['price'] ) ? floatval( $data['price'] ) : 0.00;
			$this->capacity = isset( $data['capacity'] ) ? absint( $data['capacity'] ) : 1;
		}
	}

	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_services';

		$data = array(
			'title'    => $this->title,
			'duration' => $this->duration,
			'price'    => $this->price,
			'capacity' => $this->capacity,
		);

		$format = array( '%s', '%d', '%f', '%d' );

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
		$table_name = $wpdb->prefix . 'fb_services';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

		if ( $row ) {
			return new self( $row );
		}

		return null;
	}

	/**
	 * Recupera tutti i servizi.
	 *
	 * @return FB_Service[]
	 */
	public static function get_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_services';
		$rows = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A );

		$services = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$services[] = new self( $row );
			}
		}

		return $services;
	}

	/**
	 * Elimina il servizio e le relative relazioni con lo staff.
	 *
	 * @return bool
	 */
	public function delete() {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table_services = $wpdb->prefix . 'fb_services';
		$table_staff_services = $wpdb->prefix . 'fb_staff_services';

		// Rimuovi associazioni staff
		$wpdb->delete( $table_staff_services, array( 'service_id' => $this->id ), array( '%d' ) );

		// Elimina servizio
		return $wpdb->delete( $table_services, array( 'id' => $this->id ), array( '%d' ) ) !== false;
	}
}
