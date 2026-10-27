<?php

/**
 * Fired during plugin activation
 */
class Free_Bookly_Activator {

	const DB_VERSION = '1.2.0';

	/**
	 * Crea le tabelle del database necessarie al primo avvio o aggiornamento.
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$tables = self::get_schema( $wpdb->prefix, $charset_collate );

		foreach ( $tables as $table ) {
			dbDelta( $table );
		}

		self::seed_defaults();
		update_option( 'fb_db_version', self::DB_VERSION );
	}

	/**
	 * Inserisce dati predefiniti se non ancora presenti (categoria di default, impostazioni aziendali).
	 */
	public static function seed_defaults() {
		global $wpdb;

		// 1. Categoria predefinita se la tabella è vuota
		$cat_table = $wpdb->prefix . 'fb_categories';
		$cat_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$cat_table}'" ) === $cat_table;
		if ( $cat_exists ) {
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cat_table}" );
			if ( 0 === $count ) {
				$wpdb->insert(
					$cat_table,
					array(
						'name'        => 'Generale',
						'description' => 'Categoria predefinita per i servizi',
						'image_url'   => '',
						'sort_order'  => 1,
					),
					array( '%s', '%s', '%s', '%d' )
				);
				$default_cat_id = $wpdb->insert_id;

				// Assegna la categoria di default ai servizi privi di categoria
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$wpdb->prefix}fb_services SET category_id = %d WHERE category_id = 0 OR category_id IS NULL",
					$default_cat_id
				) );
			}
		}

		// 2. Impostazioni aziendali di default
		if ( false === get_option( 'fb_company_name' ) ) {
			update_option( 'fb_company_name', get_bloginfo( 'name' ) );
		}
		if ( false === get_option( 'fb_company_email' ) ) {
			update_option( 'fb_company_email', get_bloginfo( 'admin_email' ) );
		}
		if ( false === get_option( 'fb_company_phone' ) ) {
			update_option( 'fb_company_phone', '' );
		}
		if ( false === get_option( 'fb_company_address' ) ) {
			update_option( 'fb_company_address', '' );
		}
		if ( false === get_option( 'fb_company_logo' ) ) {
			update_option( 'fb_company_logo', '' );
		}
		if ( false === get_option( 'fb_default_appointment_status' ) ) {
			update_option( 'fb_default_appointment_status', 'approved' );
		}
		if ( false === get_option( 'fb_time_slot_length' ) ) {
			update_option( 'fb_time_slot_length', 15 );
		}
		if ( false === get_option( 'fb_script_loading' ) ) {
			update_option( 'fb_script_loading', 'all_pages' );
		}
		if ( false === get_option( 'fb_currency' ) ) {
			update_option( 'fb_currency', 'EUR' );
		}
		if ( false === get_option( 'fb_currency_format' ) ) {
			update_option( 'fb_currency_format', '{price} €' );
		}

		// 3. Orari aziendali di default (Lunedì-Venerdì 09:00-18:00 con pausa 13:00-14:00, Sabato-Domenica riposo)
		if ( false === get_option( 'fb_company_working_hours' ) ) {
			$default_hours = array();
			for ( $d = 1; $d <= 7; $d++ ) {
				$default_hours[ $d ] = array(
					'is_working'  => ( $d <= 5 ) ? 1 : 0,
					'start_time'  => '09:00',
					'end_time'    => '18:00',
					'break_start' => ( $d <= 5 ) ? '13:00' : '',
					'break_end'   => ( $d <= 5 ) ? '14:00' : '',
				);
			}
			update_option( 'fb_company_working_hours', $default_hours );
		}
	}

	/**
	 * Ritorna le definizioni CREATE TABLE per le nostre tabelle custom.
	 */
	private static function get_schema( $prefix, $charset_collate ) {
		$schema = array();

		// Categories Table
		$schema[] = "CREATE TABLE {$prefix}fb_categories (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text DEFAULT '' NOT NULL,
			image_url varchar(255) DEFAULT '' NOT NULL,
			sort_order int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Staff Table
		$schema[] = "CREATE TABLE {$prefix}fb_staff (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			full_name varchar(255) NOT NULL,
			email varchar(255) DEFAULT '' NOT NULL,
			phone varchar(255) DEFAULT '' NOT NULL,
			wp_user_id bigint(20) DEFAULT NULL,
			avatar_url varchar(255) DEFAULT '' NOT NULL,
			color varchar(20) DEFAULT '#0284c7' NOT NULL,
			visibility varchar(20) DEFAULT 'public' NOT NULL,
			info text DEFAULT '' NOT NULL,
			status varchar(20) DEFAULT 'active' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Services Table
		$schema[] = "CREATE TABLE {$prefix}fb_services (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			category_id bigint(20) DEFAULT 0 NOT NULL,
			title varchar(255) NOT NULL,
			duration int(11) NOT NULL DEFAULT 60,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			capacity int(11) NOT NULL DEFAULT 1,
			image_url varchar(255) DEFAULT '' NOT NULL,
			color varchar(20) DEFAULT '#0284c7' NOT NULL,
			visibility varchar(20) DEFAULT 'public' NOT NULL,
			info text DEFAULT '' NOT NULL,
			time_slot_length int(11) DEFAULT 0 NOT NULL,
			buffer_before int(11) DEFAULT 0 NOT NULL,
			buffer_after int(11) DEFAULT 0 NOT NULL,
			any_provider_rule varchar(50) DEFAULT 'random' NOT NULL,
			PRIMARY KEY  (id),
			KEY category_id (category_id)
		) $charset_collate;";

		// Staff-Services Pivot Table
		$schema[] = "CREATE TABLE {$prefix}fb_staff_services (
			staff_id bigint(20) NOT NULL,
			service_id bigint(20) NOT NULL,
			custom_price decimal(10,2) DEFAULT NULL,
			PRIMARY KEY  (staff_id, service_id)
		) $charset_collate;";

		// Customers Table
		$schema[] = "CREATE TABLE {$prefix}fb_customers (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			full_name varchar(255) NOT NULL,
			email varchar(255) DEFAULT '' NOT NULL,
			phone varchar(255) DEFAULT '' NOT NULL,
			wp_user_id bigint(20) DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Appointments Table
		$schema[] = "CREATE TABLE {$prefix}fb_appointments (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			staff_id bigint(20) NOT NULL,
			service_id bigint(20) NOT NULL,
			customer_id bigint(20) NOT NULL,
			start_date datetime NOT NULL,
			end_date datetime NOT NULL,
			status varchar(50) NOT NULL DEFAULT 'pending',
			PRIMARY KEY  (id),
			KEY staff_id (staff_id),
			KEY customer_id (customer_id)
		) $charset_collate;";

		// Staff Schedule Table (1 per day of the week, Monday=1 to Sunday=7)
		$schema[] = "CREATE TABLE {$prefix}fb_staff_schedule (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			staff_id bigint(20) NOT NULL,
			day_index tinyint(4) NOT NULL,
			start_time time NOT NULL DEFAULT '09:00:00',
			end_time time NOT NULL DEFAULT '18:00:00',
			is_working_day tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY staff_day (staff_id, day_index)
		) $charset_collate;";

		// Staff Breaks Table
		$schema[] = "CREATE TABLE {$prefix}fb_staff_breaks (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			staff_id bigint(20) NOT NULL,
			day_index tinyint(4) NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			PRIMARY KEY  (id),
			KEY staff_day (staff_id, day_index)
		) $charset_collate;";

		// Holidays Table (staff_id = 0 for company holidays, >0 for staff days off)
		$schema[] = "CREATE TABLE {$prefix}fb_holidays (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			staff_id bigint(20) DEFAULT 0 NOT NULL,
			holiday_date date NOT NULL,
			title varchar(255) DEFAULT '' NOT NULL,
			repeat_yearly tinyint(1) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			KEY staff_date (staff_id, holiday_date)
		) $charset_collate;";

		return $schema;
	}
}
