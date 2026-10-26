<?php

/**
 * Fired during plugin activation
 */
class Free_Bookly_Activator {

	/**
	 * Crea le tabelle del database necessarie al primo avvio.
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$tables = self::get_schema( $wpdb->prefix, $charset_collate );

		foreach ( $tables as $table ) {
			dbDelta( $table );
		}
	}

	/**
	 * Ritorna le definizioni CREATE TABLE per le nostre tabelle custom.
	 */
	private static function get_schema( $prefix, $charset_collate ) {
		$schema = array();

		// Staff Table
		$schema[] = "CREATE TABLE {$prefix}fb_staff (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			full_name varchar(255) NOT NULL,
			email varchar(255) DEFAULT '' NOT NULL,
			phone varchar(255) DEFAULT '' NOT NULL,
			wp_user_id bigint(20) DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Services Table
		$schema[] = "CREATE TABLE {$prefix}fb_services (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			duration int(11) NOT NULL DEFAULT 60,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			capacity int(11) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id)
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

		return $schema;
	}
}
