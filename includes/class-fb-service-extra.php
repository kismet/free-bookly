<?php
/**
 * Modello Servizio Extra (Add-on)
 *
 * Rappresenta un servizio o trattamento aggiuntivo opzionale che il cliente può selezionare
 * durante la prenotazione, incrementando la durata complessiva e/o il prezzo.
 *
 * @package Free_Bookly
 * @author Stefano Lenzi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FB_Service_Extra {

	public $id;
	public $service_id;
	public $title;
	public $price;
	public $duration;
	public $status;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id         = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->service_id = isset( $data['service_id'] ) ? absint( $data['service_id'] ) : 0;
			$this->title      = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
			$this->price      = isset( $data['price'] ) ? floatval( $data['price'] ) : 0.00;
			$this->duration   = isset( $data['duration'] ) ? absint( $data['duration'] ) : 0;
			$this->status     = isset( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive' ), true ) ? $data['status'] : 'active';
		}
	}

	public function save() {
		global $wpdb;
		$table = $wpdb->prefix . 'fb_service_extras';

		$data = array(
			'service_id' => $this->service_id,
			'title'      => $this->title,
			'price'      => $this->price,
			'duration'   => $this->duration,
			'status'     => $this->status,
		);

		$format = array( '%d', '%s', '%f', '%d', '%s' );

		if ( $this->id ) {
			$res = $wpdb->update( $table, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
			return false !== $res;
		}

		$res = $wpdb->insert( $table, $data, $format );
		if ( $res ) {
			$this->id = $wpdb->insert_id;
			return true;
		}

		return false;
	}

	public static function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete(
			$wpdb->prefix . 'fb_service_extras',
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);
	}

	public static function get_by_id( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fb_service_extras WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);

		return $row ? new self( $row ) : null;
	}

	public static function get_all( $only_active = false ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}fb_service_extras";
		if ( $only_active ) {
			$sql .= " WHERE status = 'active'";
		}
		$sql .= " ORDER BY service_id ASC, title ASC";

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$list = array();
		foreach ( $rows as $row ) {
			$list[] = new self( $row );
		}

		return $list;
	}

	/**
	 * Recupera gli extra validi per un dato servizio (inclusi quelli globali con service_id = 0).
	 *
	 * @param int  $service_id
	 * @param bool $only_active
	 * @return array<FB_Service_Extra>
	 */
	public static function get_by_service( $service_id = 0, $only_active = true ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fb_service_extras';
		$service_id = absint( $service_id );

		$where = array();
		if ( $service_id > 0 ) {
			$where[] = $wpdb->prepare( '(service_id = 0 OR service_id = %d)', $service_id );
		} else {
			$where[] = 'service_id = 0';
		}

		if ( $only_active ) {
			$where[] = "status = 'active'";
		}

		$where_clause = implode( ' AND ', $where );
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY title ASC", ARRAY_A );

		$list = array();
		foreach ( $rows as $row ) {
			$list[] = new self( $row );
		}

		return $list;
	}

	public function get_formatted_price() {
		require_once dirname( __FILE__ ) . '/class-fb-settings.php';
		return FB_Settings::format_price( $this->price );
	}

	public function get_service_name() {
		if ( 0 === (int) $this->service_id ) {
			return __( 'Tutti i servizi (Globale)', 'free-bookly' );
		}

		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		$srv = FB_Service::get_by_id( $this->service_id );
		return $srv ? $srv->title : __( 'Servizio sconosciuto', 'free-bookly' );
	}
}
