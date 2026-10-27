<?php

/**
 * Modello che rappresenta i Servizi prenotabili.
 * Si occupa di leggere e scrivere i dati dalla tabella wp_fb_services.
 */
class FB_Service {

	public $id;
	public $category_id;
	public $title;
	public $duration;
	public $price;
	public $capacity;
	public $image_url;
	public $color;
	public $visibility;
	public $info;
	public $time_slot_length;
	public $buffer_before;
	public $buffer_after;
	public $any_provider_rule;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id                = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->category_id       = isset( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;
			$this->title             = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
			$this->duration          = isset( $data['duration'] ) ? absint( $data['duration'] ) : 60;
			$this->price             = isset( $data['price'] ) ? floatval( $data['price'] ) : 0.00;
			$this->capacity          = isset( $data['capacity'] ) ? absint( $data['capacity'] ) : 1;
			$this->image_url         = isset( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '';
			$this->color             = isset( $data['color'] ) && ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#0284c7';
			$this->visibility        = isset( $data['visibility'] ) && in_array( $data['visibility'], array( 'public', 'private' ), true ) ? $data['visibility'] : 'public';
			$this->info              = isset( $data['info'] ) ? wp_kses_post( $data['info'] ) : '';
			$this->time_slot_length  = isset( $data['time_slot_length'] ) ? absint( $data['time_slot_length'] ) : 0;
			$this->buffer_before     = isset( $data['buffer_before'] ) ? absint( $data['buffer_before'] ) : 0;
			$this->buffer_after      = isset( $data['buffer_after'] ) ? absint( $data['buffer_after'] ) : 0;
			$this->any_provider_rule = isset( $data['any_provider_rule'] ) ? sanitize_text_field( $data['any_provider_rule'] ) : 'random';
		}
	}

	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_services';

		$data = array(
			'category_id'       => $this->category_id,
			'title'             => $this->title,
			'duration'          => $this->duration,
			'price'             => $this->price,
			'capacity'          => $this->capacity,
			'image_url'         => $this->image_url,
			'color'             => $this->color,
			'visibility'        => $this->visibility,
			'info'              => $this->info,
			'time_slot_length'  => $this->time_slot_length,
			'buffer_before'     => $this->buffer_before,
			'buffer_after'      => $this->buffer_after,
			'any_provider_rule' => $this->any_provider_rule,
		);

		$format = array( '%d', '%s', '%d', '%f', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' );

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
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ), ARRAY_A );

		if ( $row ) {
			return new self( $row );
		}

		return null;
	}

	/**
	 * Recupera tutti i servizi con opzione per filtrare solo i pubblici.
	 *
	 * @param bool $only_public
	 * @return FB_Service[]
	 */
	public static function get_all( $only_public = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_services';
		$where = $only_public ? "WHERE visibility = 'public'" : '';
		$rows = $wpdb->get_results( "SELECT * FROM {$table_name} {$where} ORDER BY category_id ASC, id ASC", ARRAY_A );

		$services = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$services[] = new self( $row );
			}
		}

		return $services;
	}

	/**
	 * Recupera i servizi appartenenti a una specifica categoria.
	 *
	 * @param int  $category_id
	 * @param bool $only_public
	 * @return FB_Service[]
	 */
	public static function get_by_category( $category_id, $only_public = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_services';
		$public_clause = $only_public ? "AND visibility = 'public'" : '';
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE category_id = %d {$public_clause} ORDER BY id ASC", $category_id ),
			ARRAY_A
		);

		$services = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$services[] = new self( $row );
			}
		}

		return $services;
	}

	/**
	 * Ritorna il nome della categoria di appartenenza.
	 *
	 * @return string
	 */
	public function get_category_name() {
		if ( ! $this->category_id ) {
			return __( 'Nessuna categoria', 'free-bookly' );
		}
		if ( class_exists( 'FB_Category' ) ) {
			$cat = FB_Category::get_by_id( $this->category_id );
			if ( $cat ) {
				return $cat->name;
			}
		}
		return __( 'Generale', 'free-bookly' );
	}

	/**
	 * Ritorna il prezzo formattato.
	 *
	 * @return string
	 */
	public function get_formatted_price() {
		if ( class_exists( 'FB_Settings' ) ) {
			return FB_Settings::format_price( $this->price );
		}
		return number_format( (float) $this->price, 2, ',', '.' ) . ' €';
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
