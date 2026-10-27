<?php

/**
 * Modello che rappresenta le Categorie dei Servizi.
 * Si occupa di leggere e scrivere i dati dalla tabella wp_fb_categories.
 */
class FB_Category {

	public $id;
	public $name;
	public $description;
	public $image_url;
	public $sort_order;

	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->id          = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
			$this->name        = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
			$this->description = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
			$this->image_url   = isset( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '';
			$this->sort_order  = isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0;
		}
	}

	/**
	 * Salva la categoria nel database (Insert o Update).
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_categories';

		$data = array(
			'name'        => $this->name,
			'description' => $this->description,
			'image_url'   => $this->image_url,
			'sort_order'  => $this->sort_order,
		);

		$format = array( '%s', '%s', '%s', '%d' );

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
	 * Recupera una categoria per ID.
	 *
	 * @param int $id
	 * @return FB_Category|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_categories';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ), ARRAY_A );

		if ( $row ) {
			return new self( $row );
		}

		return null;
	}

	/**
	 * Recupera tutte le categorie ordinate per sort_order e nome.
	 *
	 * @return FB_Category[]
	 */
	public static function get_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_categories';
		$rows = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY sort_order ASC, name ASC", ARRAY_A );

		$categories = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$categories[] = new self( $row );
			}
		}

		return $categories;
	}

	/**
	 * Conta quanti servizi appartengono a questa categoria.
	 *
	 * @param int $category_id
	 * @return int
	 */
	public static function count_services( $category_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_services';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE category_id = %d", $category_id ) );
	}

	/**
	 * Elimina la categoria.
	 * I servizi associati vengono riassegnati alla categoria 0 o alla prima disponibile.
	 *
	 * @return bool
	 */
	public function delete() {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table_cat = $wpdb->prefix . 'fb_categories';
		$table_srv = $wpdb->prefix . 'fb_services';

		// Trova un'altra categoria di fallback se presente
		$fallback_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_cat} WHERE id != %d ORDER BY id ASC LIMIT 1", $this->id ) );

		// Riassegna i servizi
		$wpdb->update( $table_srv, array( 'category_id' => $fallback_id ), array( 'category_id' => $this->id ), array( '%d' ), array( '%d' ) );

		return $wpdb->delete( $table_cat, array( 'id' => $this->id ), array( '%d' ) ) !== false;
	}
}
