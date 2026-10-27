<?php

/**
 * Modello che rappresenta i Giorni Festivi aziendali e i Giorni Liberi dello Staff.
 * Si occupa di leggere e scrivere i dati dalla tabella wp_fb_holidays.
 */
class FB_Holiday {

	public $id;
	public $staff_id;
	public $holiday_date;
	public $title;
	public $repeat_yearly;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id            = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->staff_id      = isset( $data['staff_id'] ) ? absint( $data['staff_id'] ) : 0;
			$this->holiday_date  = isset( $data['holiday_date'] ) ? sanitize_text_field( $data['holiday_date'] ) : '';
			$this->title         = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
			$this->repeat_yearly = isset( $data['repeat_yearly'] ) ? ( $data['repeat_yearly'] ? 1 : 0 ) : 0;
		}
	}

	/**
	 * Salva la festività o giorno libero nel database.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_holidays';

		$data = array(
			'staff_id'      => $this->staff_id,
			'holiday_date'  => $this->holiday_date,
			'title'         => $this->title,
			'repeat_yearly' => $this->repeat_yearly,
		);

		$format = array( '%d', '%s', '%s', '%d' );

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

	/**
	 * Recupera tutte le festività aziendali (staff_id = 0).
	 *
	 * @return FB_Holiday[]
	 */
	public static function get_company_holidays() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_holidays';
		$rows = $wpdb->get_results( "SELECT * FROM {$table_name} WHERE staff_id = 0 ORDER BY holiday_date ASC", ARRAY_A );

		$holidays = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$holidays[] = new self( $row );
			}
		}

		return $holidays;
	}

	/**
	 * Recupera i giorni liberi specifici di un membro dello staff.
	 *
	 * @param int $staff_id
	 * @return FB_Holiday[]
	 */
	public static function get_staff_holidays( $staff_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_holidays';
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE staff_id = %d ORDER BY holiday_date ASC", $staff_id ), ARRAY_A );

		$holidays = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$holidays[] = new self( $row );
			}
		}

		return $holidays;
	}

	/**
	 * Verifica se una data specifica è una festività aziendale o un giorno di riposo per lo staff.
	 *
	 * @param string $date Formato Y-m-d.
	 * @param int    $staff_id ID dello staff opzionale.
	 * @return bool
	 */
	public static function is_holiday( $date, $staff_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_holidays';
		$md = date( 'm-d', strtotime( $date ) );

		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} 
			WHERE (staff_id = 0 OR staff_id = %d) 
			AND (holiday_date = %s OR (repeat_yearly = 1 AND DATE_FORMAT(holiday_date, '%%m-%%d') = %s))",
			$staff_id,
			$date,
			$md
		);

		return (int) $wpdb->get_var( $query ) > 0;
	}

	/**
	 * Elimina una festività per ID.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete_by_id( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_holidays';
		return $wpdb->delete( $table_name, array( 'id' => absint( $id ) ), array( '%d' ) ) !== false;
	}
}
