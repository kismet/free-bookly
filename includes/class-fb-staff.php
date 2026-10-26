<?php

/**
 * Modello che rappresenta lo Staff.
 * Si occupa di leggere e scrivere i dati dalla tabella wp_fb_staff.
 */
class FB_Staff {

	public $id;
	public $full_name;
	public $email;
	public $phone;
	public $wp_user_id;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id         = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->full_name  = isset( $data['full_name'] ) ? sanitize_text_field( $data['full_name'] ) : '';
			$this->email      = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
			$this->phone      = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
			$this->wp_user_id = isset( $data['wp_user_id'] ) ? absint( $data['wp_user_id'] ) : null;
		}
	}

	/**
	 * Salva il record nel database (Insert o Update)
	 */
	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff';

		$data = array(
			'full_name'  => $this->full_name,
			'email'      => $this->email,
			'phone'      => $this->phone,
			'wp_user_id' => $this->wp_user_id,
		);

		$format = array( '%s', '%s', '%s', '%d' );

		if ( $this->id ) {
			// Update
			$result = $wpdb->update( $table_name, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
			return $result !== false;
		} else {
			// Insert
			$result = $wpdb->insert( $table_name, $data, $format );
			if ( $result ) {
				$this->id = $wpdb->insert_id;
				return true;
			}
		}

		return false;
	}

	/**
	 * Recupera un membro dello staff tramite ID.
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff';

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

		if ( $row ) {
			return new self( $row );
		}

		return null;
	}

	/**
	 * Recupera tutti i membri dello staff.
	 *
	 * @return FB_Staff[]
	 */
	public static function get_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff';
		$rows = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A );

		$staff_list = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$staff_list[] = new self( $row );
			}
		}

		return $staff_list;
	}

	/**
	 * Recupera i membri dello staff abilitati a uno specifico servizio.
	 *
	 * @param int $service_id
	 * @return FB_Staff[]
	 */
	public static function get_by_service_id( $service_id ) {
		global $wpdb;
		$table_staff = $wpdb->prefix . 'fb_staff';
		$table_pivot = $wpdb->prefix . 'fb_staff_services';

		$query = $wpdb->prepare(
			"SELECT s.* FROM $table_staff s 
			INNER JOIN $table_pivot p ON s.id = p.staff_id 
			WHERE p.service_id = %d 
			ORDER BY s.full_name ASC",
			$service_id
		);

		$rows = $wpdb->get_results( $query, ARRAY_A );
		$staff_list = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$staff_list[] = new self( $row );
			}
		}

		return $staff_list;
	}

	/**
	 * Recupera gli ID dei servizi associati a questo membro dello staff.
	 *
	 * @return int[]
	 */
	public function get_service_ids() {
		if ( ! $this->id ) {
			return array();
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff_services';
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT service_id FROM $table_name WHERE staff_id = %d", $this->id ) );

		return ! empty( $results ) ? array_map( 'absint', $results ) : array();
	}

	/**
	 * Imposta (sincronizza) i servizi associati a questo membro dello staff.
	 *
	 * @param int[] $service_ids
	 * @return bool
	 */
	public function set_services( array $service_ids ) {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff_services';

		// Rimuovi associazioni precedenti
		$wpdb->delete( $table_name, array( 'staff_id' => $this->id ), array( '%d' ) );

		// Inserisci le nuove
		foreach ( $service_ids as $service_id ) {
			$service_id = absint( $service_id );
			if ( $service_id > 0 ) {
				$wpdb->insert(
					$table_name,
					array(
						'staff_id'   => $this->id,
						'service_id' => $service_id,
					),
					array( '%d', '%d' )
				);
			}
		}

		return true;
	}

	/**
	 * Elimina il membro dello staff e le sue associazioni ai servizi e orari.
	 */
	public function delete() {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table_staff = $wpdb->prefix . 'fb_staff';
		$table_staff_services = $wpdb->prefix . 'fb_staff_services';
		
		// Rimuovi orari e pause
		if ( class_exists( 'FB_Staff_Schedule' ) ) {
			FB_Staff_Schedule::delete_by_staff( $this->id );
		}

		// Rimuovi associazioni servizi
		$wpdb->delete( $table_staff_services, array( 'staff_id' => $this->id ), array( '%d' ) );

		// Elimina membro staff
		return $wpdb->delete( $table_staff, array( 'id' => $this->id ), array( '%d' ) ) !== false;
	}
}
