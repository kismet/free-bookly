<?php
/**
 * Modello Campi Personalizzati (Custom Fields)
 *
 * Consente la definizione e gestione di campi personalizzati addizionali (testo, select, radio, checkbox, textarea)
 * raccolti durante lo step dei dati cliente nel form di prenotazione.
 *
 * @package Free_Bookly
 * @author Stefano Lenzi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FB_Custom_Field {

	public $id;
	public $label;
	public $field_type;
	public $options;
	public $is_required;
	public $service_id;
	public $sort_order;

	public static $allowed_types = array(
		'text'     => 'Testo breve (Testo)',
		'textarea' => 'Area di testo (Textarea)',
		'select'   => 'Menu a tendina (Select)',
		'radio'    => 'Pulsanti di scelta (Radio)',
		'checkbox' => 'Casella di spunta (Checkbox)',
	);

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id          = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->label       = isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '';
			$this->field_type  = isset( $data['field_type'] ) && array_key_exists( $data['field_type'], self::$allowed_types ) ? $data['field_type'] : 'text';
			$this->options     = isset( $data['options'] ) ? sanitize_textarea_field( $data['options'] ) : '';
			$this->is_required = ! empty( $data['is_required'] ) ? 1 : 0;
			$this->service_id  = isset( $data['service_id'] ) ? absint( $data['service_id'] ) : 0;
			$this->sort_order  = isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : 0;
		}
	}

	public function save() {
		global $wpdb;
		$table = $wpdb->prefix . 'fb_custom_fields';

		$data = array(
			'label'       => $this->label,
			'field_type'  => $this->field_type,
			'options'     => $this->options,
			'is_required' => $this->is_required,
			'service_id'  => $this->service_id,
			'sort_order'  => $this->sort_order,
		);

		$format = array( '%s', '%s', '%s', '%d', '%d', '%d' );

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
			$wpdb->prefix . 'fb_custom_fields',
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);
	}

	public static function get_by_id( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fb_custom_fields WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);

		return $row ? new self( $row ) : null;
	}

	public static function get_all() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}fb_custom_fields ORDER BY sort_order ASC, id ASC",
			ARRAY_A
		);

		$list = array();
		foreach ( $rows as $row ) {
			$list[] = new self( $row );
		}

		return $list;
	}

	/**
	 * Ritorna tutti i campi attivi applicabili a un servizio (globali con service_id=0 o specifici).
	 *
	 * @param int $service_id
	 * @return array<FB_Custom_Field>
	 */
	public static function get_by_service( $service_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fb_custom_fields';
		$service_id = absint( $service_id );

		if ( $service_id > 0 ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE (service_id = 0 OR service_id = %d) ORDER BY sort_order ASC, id ASC",
				$service_id
			);
		} else {
			$sql = "SELECT * FROM {$table} WHERE service_id = 0 ORDER BY sort_order ASC, id ASC";
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$list = array();
		foreach ( $rows as $row ) {
			$list[] = new self( $row );
		}

		return $list;
	}

	/**
	 * Converte la stringa options in un array di opzioni singole.
	 *
	 * @return array
	 */
	public function get_options_array() {
		if ( empty( $this->options ) ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', $this->options );
		$result = array();
		foreach ( $lines as $line ) {
			$trimmed = trim( $line );
			if ( '' !== $trimmed ) {
				$result[] = $trimmed;
			}
		}

		return $result;
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
