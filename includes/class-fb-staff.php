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
	 * Elimina il membro dello staff.
	 */
	public function delete() {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_staff';
		
		return $wpdb->delete( $table_name, array( 'id' => $this->id ), array( '%d' ) ) !== false;
	}
}
