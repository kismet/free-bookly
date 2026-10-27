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
	public $avatar_url;
	public $color;
	public $visibility;
	public $info;
	public $status;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id         = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->full_name  = isset( $data['full_name'] ) ? sanitize_text_field( $data['full_name'] ) : '';
			$this->email      = isset( $data['email'] ) ? sanitize_text_field( $data['email'] ) : ''; // Supporta email multiple separate da virgola
			$this->phone      = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
			$this->wp_user_id = isset( $data['wp_user_id'] ) ? absint( $data['wp_user_id'] ) : null;
			$this->avatar_url = isset( $data['avatar_url'] ) ? esc_url_raw( $data['avatar_url'] ) : '';
			$this->color      = isset( $data['color'] ) && ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#0284c7';
			$this->visibility = isset( $data['visibility'] ) && in_array( $data['visibility'], array( 'public', 'private' ), true ) ? $data['visibility'] : 'public';
			$this->info       = isset( $data['info'] ) ? wp_kses_post( $data['info'] ) : '';
			$this->status     = isset( $data['status'] ) && in_array( $data['status'], array( 'active', 'archived' ), true ) ? $data['status'] : 'active';
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
			'avatar_url' => $this->avatar_url,
			'color'      => $this->color,
			'visibility' => $this->visibility,
			'info'       => $this->info,
			'status'     => $this->status,
		);

		$format = array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' );

		if ( $this->id ) {
			// Update
			$result = $wpdb->update( $table_name, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
			return $result !== false;
		} else {
			// Insert
			$result = $wpdb->insert( $table_name, $data, $format );
			if ( $result ) {
				$this->id = $wpdb->insert_id;

				// Inizializza gli orari predefiniti ereditando quelli aziendali
				if ( class_exists( 'FB_Staff_Schedule' ) ) {
					$company_hours = class_exists( 'FB_Settings' ) ? FB_Settings::get_company_working_hours() : array();
					FB_Staff_Schedule::init_default_schedule( $this->id, $company_hours );
				}

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

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ), ARRAY_A );

		if ( $row ) {
			return new self( $row );
		}

		return null;
	}

	/**
	 * Recupera i membri dello staff con filtri per archiviazione e visibilità.
	 *
	 * @param bool $include_archived Se includere i membri archiviati.
	 * @param bool $only_public Se filtrare solo quelli pubblici.
	 * @return FB_Staff[]
	 */
	public static function get_all( $include_archived = false, $only_public = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff';

		$where = array();
		if ( ! $include_archived ) {
			$where[] = "status != 'archived'";
		}
		if ( $only_public ) {
			$where[] = "visibility = 'public'";
		}

		$where_clause = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$rows = $wpdb->get_results( "SELECT * FROM {$table_name} {$where_clause} ORDER BY full_name ASC", ARRAY_A );

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
	 * @param int  $service_id
	 * @param bool $only_public
	 * @return FB_Staff[]
	 */
	public static function get_by_service_id( $service_id, $only_public = false ) {
		global $wpdb;
		$table_staff = $wpdb->prefix . 'fb_staff';
		$table_pivot = $wpdb->prefix . 'fb_staff_services';

		$public_clause = $only_public ? "AND s.visibility = 'public'" : "";

		$query = $wpdb->prepare(
			"SELECT s.* FROM {$table_staff} s 
			INNER JOIN {$table_pivot} p ON s.id = p.staff_id 
			WHERE p.service_id = %d AND s.status != 'archived' {$public_clause}
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
	 * Archivia il membro dello staff (lo nasconde senza eliminare storico appuntamenti).
	 *
	 * @return bool
	 */
	public function archive() {
		$this->status = 'archived';
		return $this->save();
	}

	/**
	 * Ripristina un membro dello staff archiviato.
	 *
	 * @return bool
	 */
	public function unarchive() {
		$this->status = 'active';
		return $this->save();
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
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT service_id FROM {$table_name} WHERE staff_id = %d", $this->id ) );

		return ! empty( $results ) ? array_map( 'absint', $results ) : array();
	}

	/**
	 * Recupera il prezzo personalizzato per un servizio se impostato, altrimenti ritorna null.
	 *
	 * @param int $service_id
	 * @return float|null
	 */
	public function get_custom_price( $service_id ) {
		if ( ! $this->id ) {
			return null;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff_services';
		$val = $wpdb->get_var( $wpdb->prepare(
			"SELECT custom_price FROM {$table_name} WHERE staff_id = %d AND service_id = %d",
			$this->id,
			$service_id
		) );

		return ( null !== $val && '' !== $val ) ? (float) $val : null;
	}

	/**
	 * Imposta (sincronizza) i servizi associati a questo membro dello staff con eventuali prezzi personalizzati.
	 *
	 * @param array $service_data Array di service_id oppure array( service_id => custom_price ).
	 * @return bool
	 */
	public function set_services( array $service_data ) {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff_services';

		// Rimuovi associazioni precedenti
		$wpdb->delete( $table_name, array( 'staff_id' => $this->id ), array( '%d' ) );

		// Inserisci le nuove
		foreach ( $service_data as $key => $value ) {
			$service_id   = is_numeric( $key ) ? absint( $key ) : absint( $value );
			$custom_price = ( is_numeric( $key ) && is_numeric( $value ) && $value >= 0 ) ? (float) $value : null;

			// Se formato lista semplice [1, 2, 3]
			if ( ! is_numeric( $key ) || $key === 0 ) {
				$service_id = absint( $value );
				$custom_price = null;
			}

			if ( $service_id > 0 ) {
				$wpdb->insert(
					$table_name,
					array(
						'staff_id'     => $this->id,
						'service_id'   => $service_id,
						'custom_price' => $custom_price,
					),
					array( '%d', '%d', '%f' )
				);
			}
		}

		return true;
	}

	/**
	 * Elimina il membro dello staff e le sue associazioni ai servizi, orari e festività.
	 */
	public function delete() {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table_staff = $wpdb->prefix . 'fb_staff';
		$table_staff_services = $wpdb->prefix . 'fb_staff_services';
		$table_holidays = $wpdb->prefix . 'fb_holidays';
		
		// Rimuovi orari e pause
		if ( class_exists( 'FB_Staff_Schedule' ) ) {
			FB_Staff_Schedule::delete_by_staff( $this->id );
		}

		// Rimuovi giorni liberi
		$wpdb->delete( $table_holidays, array( 'staff_id' => $this->id ), array( '%d' ) );

		// Rimuovi associazioni servizi
		$wpdb->delete( $table_staff_services, array( 'staff_id' => $this->id ), array( '%d' ) );

		// Elimina membro staff
		return $wpdb->delete( $table_staff, array( 'id' => $this->id ), array( '%d' ) ) !== false;
	}
}
