<?php
/**
 * Eseguito automaticamente quando il plugin viene disinstallato (eliminato) da WordPress.
 * Pulisce in modo sicuro tutte le tabelle personalizzate e le impostazioni del database.
 *
 * @package Free_Bookly
 */

// Se la disinstallazione non è invocata da WordPress, interrompi.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Rimuovi le tabelle custom nell'ordine corretto
$tables = array(
	$wpdb->prefix . 'fb_appointment_extras',
	$wpdb->prefix . 'fb_service_extras',
	$wpdb->prefix . 'fb_custom_fields',
	$wpdb->prefix . 'fb_appointments',
	$wpdb->prefix . 'fb_holidays',
	$wpdb->prefix . 'fb_staff_breaks',
	$wpdb->prefix . 'fb_staff_schedule',
	$wpdb->prefix . 'fb_customers',
	$wpdb->prefix . 'fb_staff_services',
	$wpdb->prefix . 'fb_staff',
	$wpdb->prefix . 'fb_services',
	$wpdb->prefix . 'fb_categories',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// 2. Rimuovi le opzioni di configurazione registrate
$options_to_delete = array(
	'free_bookly_active_modules',
	'fb_active_modules',
	'fb_db_version',
	'fb_company_name',
	'fb_company_email',
	'fb_company_phone',
	'fb_company_address',
	'fb_company_logo',
	'fb_default_appointment_status',
	'fb_time_slot_length',
	'fb_script_loading',
	'fb_currency',
	'fb_currency_format',
	'fb_company_working_hours',
	'fb_email_log',
	'fb_email_templates',
	'fb_email_from_name',
	'fb_email_from_address',
	'fb_email_enable_customer_created',
	'fb_email_enable_customer_approved',
	'fb_email_enable_customer_cancelled',
	'fb_email_enable_staff_created',
	'fb_email_enable_attach_ics',
);

foreach ( $options_to_delete as $opt ) {
	delete_option( $opt );
}
