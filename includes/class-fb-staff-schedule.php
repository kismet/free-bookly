<?php

/**
 * Modello e gestore degli orari di lavoro e delle pause per lo staff.
 * Gestisce le tabelle custom wp_fb_staff_schedule e wp_fb_staff_breaks.
 */
class FB_Staff_Schedule {

	/**
	 * Nomi dei giorni della settimana (ISO-8601: 1 = Lunedì, 7 = Domenica).
	 */
	public static function get_day_names() {
		return array(
			1 => __( 'Lunedì', 'free-bookly' ),
			2 => __( 'Martedì', 'free-bookly' ),
			3 => __( 'Mercoledì', 'free-bookly' ),
			4 => __( 'Giovedì', 'free-bookly' ),
			5 => __( 'Venerdì', 'free-bookly' ),
			6 => __( 'Sabato', 'free-bookly' ),
			7 => __( 'Domenica', 'free-bookly' ),
		);
	}

	/**
	 * Recupera la settimana lavorativa completa di un membro dello staff (giorni 1..7 con relative pause).
	 * Se non ancora configurata a database, ritorna i default di settore (Lun-Ven 09:00-18:00 con pausa 13:00-14:00, Sab-Dom riposo).
	 *
	 * @param int $staff_id
	 * @return array
	 */
	public static function get_schedule( $staff_id ) {
		global $wpdb;
		$table_schedule = $wpdb->prefix . 'fb_staff_schedule';
		$table_breaks   = $wpdb->prefix . 'fb_staff_breaks';

		$staff_id = absint( $staff_id );
		$day_names = self::get_day_names();

		// Leggi giorni salvati
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_schedule WHERE staff_id = %d ORDER BY day_index ASC", $staff_id ),
			ARRAY_A
		);

		$saved_days = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$saved_days[ (int) $row['day_index'] ] = $row;
			}
		}

		// Leggi pause salvate
		$break_rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_breaks WHERE staff_id = %d ORDER BY day_index ASC, start_time ASC", $staff_id ),
			ARRAY_A
		);

		$saved_breaks = array();
		if ( $break_rows ) {
			foreach ( $break_rows as $b ) {
				$d = (int) $b['day_index'];
				if ( ! isset( $saved_breaks[ $d ] ) ) {
					$saved_breaks[ $d ] = array();
				}
				$saved_breaks[ $d ][] = array(
					'id'         => (int) $b['id'],
					'start_time' => substr( $b['start_time'], 0, 5 ),
					'end_time'   => substr( $b['end_time'], 0, 5 ),
				);
			}
		}

		$schedule = array();
		for ( $day = 1; $day <= 7; $day++ ) {
			if ( isset( $saved_days[ $day ] ) ) {
				$row = $saved_days[ $day ];
				$schedule[ $day ] = array(
					'day_index'      => $day,
					'day_name'       => $day_names[ $day ],
					'is_working_day' => (int) $row['is_working_day'],
					'start_time'     => substr( $row['start_time'], 0, 5 ),
					'end_time'       => substr( $row['end_time'], 0, 5 ),
					'breaks'         => isset( $saved_breaks[ $day ] ) ? $saved_breaks[ $day ] : array(),
				);
			} else {
				// Eredita da impostazioni orari aziendali
				$company_hours = class_exists( 'FB_Settings' ) ? FB_Settings::get_company_working_hours() : array();
				$ch = isset( $company_hours[ $day ] ) ? $company_hours[ $day ] : null;

				$is_working = $ch ? ! empty( $ch['is_working'] ) : ( $day <= 5 );
				$start_time = ( $ch && ! empty( $ch['start_time'] ) ) ? $ch['start_time'] : '09:00';
				$end_time   = ( $ch && ! empty( $ch['end_time'] ) ) ? $ch['end_time'] : '18:00';

				$breaks = array();
				if ( $is_working ) {
					$b_start = ( $ch && ! empty( $ch['break_start'] ) ) ? $ch['break_start'] : '13:00';
					$b_end   = ( $ch && ! empty( $ch['break_end'] ) ) ? $ch['break_end'] : '14:00';
					if ( ! empty( $b_start ) && ! empty( $b_end ) ) {
						$breaks[] = array( 'start_time' => $b_start, 'end_time' => $b_end );
					}
				}

				$schedule[ $day ] = array(
					'day_index'      => $day,
					'day_name'       => $day_names[ $day ],
					'is_working_day' => $is_working ? 1 : 0,
					'start_time'     => $start_time,
					'end_time'       => $end_time,
					'breaks'         => $breaks,
				);
			}
		}

		return $schedule;
	}

	/**
	 * Recupera l'orario e le pause per un giorno specifico.
	 *
	 * @param int $staff_id
	 * @param int $day_index (1..7)
	 * @return array
	 */
	public static function get_day_schedule( $staff_id, $day_index ) {
		$schedule = self::get_schedule( $staff_id );
		return isset( $schedule[ $day_index ] ) ? $schedule[ $day_index ] : null;
	}

	/**
	 * Salva la settimana lavorativa completa con le relative pause.
	 *
	 * @param int $staff_id
	 * @param array $days_data
	 * @return bool
	 */
	public static function save_schedule( $staff_id, array $days_data ) {
		global $wpdb;
		$table_schedule = $wpdb->prefix . 'fb_staff_schedule';
		$table_breaks   = $wpdb->prefix . 'fb_staff_breaks';

		$staff_id = absint( $staff_id );
		if ( ! $staff_id ) {
			return false;
		}

		for ( $day = 1; $day <= 7; $day++ ) {
			$day_data = isset( $days_data[ $day ] ) ? $days_data[ $day ] : array();

			$is_working  = ! empty( $day_data['is_working_day'] ) ? 1 : 0;
			$start_time  = ! empty( $day_data['start_time'] ) ? sanitize_text_field( $day_data['start_time'] ) : '09:00';
			$end_time    = ! empty( $day_data['end_time'] ) ? sanitize_text_field( $day_data['end_time'] ) : '18:00';

			// Formatta in H:i:00
			if ( strlen( $start_time ) === 5 ) {
				$start_time .= ':00';
			}
			if ( strlen( $end_time ) === 5 ) {
				$end_time .= ':00';
			}

			// Upsert wp_fb_staff_schedule
			$existing_id = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM $table_schedule WHERE staff_id = %d AND day_index = %d", $staff_id, $day )
			);

			if ( $existing_id ) {
				$wpdb->update(
					$table_schedule,
					array(
						'is_working_day' => $is_working,
						'start_time'     => $start_time,
						'end_time'       => $end_time,
					),
					array( 'id' => $existing_id ),
					array( '%d', '%s', '%s' ),
					array( '%d' )
				);
			} else {
				$wpdb->insert(
					$table_schedule,
					array(
						'staff_id'       => $staff_id,
						'day_index'      => $day,
						'is_working_day' => $is_working,
						'start_time'     => $start_time,
						'end_time'       => $end_time,
					),
					array( '%d', '%d', '%d', '%s', '%s' )
				);
			}

			// Gestione pause: rimuovi pause preesistenti per questo giorno
			$wpdb->delete(
				$table_breaks,
				array( 'staff_id' => $staff_id, 'day_index' => $day ),
				array( '%d', '%d' )
			);

			// Inserisci nuove pause (se lavorativo e se presenti)
			if ( $is_working && ! empty( $day_data['breaks'] ) && is_array( $day_data['breaks'] ) ) {
				foreach ( $day_data['breaks'] as $b ) {
					$b_start = ! empty( $b['start_time'] ) ? sanitize_text_field( $b['start_time'] ) : '';
					$b_end   = ! empty( $b['end_time'] ) ? sanitize_text_field( $b['end_time'] ) : '';

					if ( $b_start && $b_end && $b_start < $b_end ) {
						if ( strlen( $b_start ) === 5 ) {
							$b_start .= ':00';
						}
						if ( strlen( $b_end ) === 5 ) {
							$b_end .= ':00';
						}

						$wpdb->insert(
							$table_breaks,
							array(
								'staff_id'   => $staff_id,
								'day_index'  => $day,
								'start_time' => $b_start,
								'end_time'   => $b_end,
							),
							array( '%d', '%d', '%s', '%s' )
						);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Inizializza la settimana lavorativa per un nuovo membro dello staff ereditando gli orari aziendali.
	 *
	 * @param int   $staff_id
	 * @param array $company_hours
	 * @return bool
	 */
	public static function init_default_schedule( $staff_id, array $company_hours = array() ) {
		if ( empty( $company_hours ) && class_exists( 'FB_Settings' ) ) {
			$company_hours = FB_Settings::get_company_working_hours();
		}

		$days_data = array();
		for ( $d = 1; $d <= 7; $d++ ) {
			$ch = isset( $company_hours[ $d ] ) ? $company_hours[ $d ] : null;
			$is_working = $ch ? ! empty( $ch['is_working'] ) : ( $d <= 5 );
			$start = ( $ch && ! empty( $ch['start_time'] ) ) ? $ch['start_time'] : '09:00';
			$end   = ( $ch && ! empty( $ch['end_time'] ) ) ? $ch['end_time'] : '18:00';

			$breaks = array();
			if ( $is_working ) {
				$b_start = ( $ch && ! empty( $ch['break_start'] ) ) ? $ch['break_start'] : '13:00';
				$b_end   = ( $ch && ! empty( $ch['break_end'] ) ) ? $ch['break_end'] : '14:00';
				if ( ! empty( $b_start ) && ! empty( $b_end ) ) {
					$breaks[] = array( 'start_time' => $b_start, 'end_time' => $b_end );
				}
			}

			$days_data[ $d ] = array(
				'is_working' => $is_working ? 1 : 0,
				'start_time' => $start,
				'end_time'   => $end,
				'breaks'     => $breaks,
			);
		}

		return self::save_schedule( $staff_id, $days_data );
	}

	/**
	 * Elimina tutti gli orari e le pause di un collaboratore.
	 *
	 * @param int $staff_id
	 */
	public static function delete_by_staff( $staff_id ) {
		global $wpdb;
		$table_schedule = $wpdb->prefix . 'fb_staff_schedule';
		$table_breaks   = $wpdb->prefix . 'fb_staff_breaks';

		$wpdb->delete( $table_schedule, array( 'staff_id' => $staff_id ), array( '%d' ) );
		$wpdb->delete( $table_breaks, array( 'staff_id' => $staff_id ), array( '%d' ) );
	}
}
