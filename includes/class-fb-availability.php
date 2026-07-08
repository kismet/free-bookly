<?php

/**
 * Classe responsabile del calcolo degli slot disponibili per la prenotazione.
 */
class FB_Availability {

	/**
	 * Ritorna gli slot disponibili per una data specifica.
	 * 
	 * @param int $service_id ID del servizio richiesto
	 * @param int $staff_id ID del membro dello staff (opzionale)
	 * @param string $date Data nel formato 'Y-m-d'
	 * @return array Array di slot disponibili (es. ['09:00', '09:30', ...])
	 */
	public function get_available_slots( $service_id, $staff_id, $date ) {
		$service = FB_Service::get_by_id( $service_id );
		if ( ! $service ) {
			return array();
		}

		// 1. Recuperare gli orari di lavoro per quello staff nel giorno $date.
		// (Per MVP: mock orario di lavoro dalle 09:00 alle 18:00)
		$work_start = new DateTime( $date . ' 09:00:00' );
		$work_end   = new DateTime( $date . ' 18:00:00' );

		// 2. Recuperare gli appuntamenti già esistenti per quello staff in quel giorno.
		$existing_appointments = $this->get_appointments_for_date( $staff_id, $date );

		// 3. Generare gli slot in base alla durata del servizio (es. ogni 30 min)
		$slots = array();
		$duration_minutes = $service->duration;
		$current_time = clone $work_start;

		while ( $current_time < $work_end ) {
			$slot_end = clone $current_time;
			$slot_end->modify( '+' . $duration_minutes . ' minutes' );

			if ( $slot_end > $work_end ) {
				break;
			}

			// Controlla collisioni
			$is_free = true;
			foreach ( $existing_appointments as $appointment ) {
				$app_start = new DateTime( $appointment->start_date );
				$app_end   = new DateTime( $appointment->end_date );

				// Sovrapposizione: Max(StartA, StartB) < Min(EndA, EndB)
				if ( max( $current_time, $app_start ) < min( $slot_end, $app_end ) ) {
					$is_free = false;
					break;
				}
			}

			if ( $is_free ) {
				$slots[] = $current_time->format( 'H:i' );
			}

			// Salta al prossimo intervallo (es. step di base = durata del servizio)
			$current_time->modify( '+' . $duration_minutes . ' minutes' );
		}

		return $slots;
	}

	/**
	 * Recupera gli appuntamenti di un membro dello staff per un determinato giorno.
	 */
	private function get_appointments_for_date( $staff_id, $date ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fb_appointments';
		
		$start_of_day = $date . ' 00:00:00';
		$end_of_day   = $date . ' 23:59:59';

		$query = $wpdb->prepare(
			"SELECT * FROM $table_name 
			WHERE staff_id = %d 
			AND status != 'cancelled' 
			AND (start_date <= %s AND end_date >= %s)",
			$staff_id,
			$end_of_day,
			$start_of_day
		);

		$results = $wpdb->get_results( $query, ARRAY_A );
		$appointments = array();
		
		if ( $results ) {
			foreach ( $results as $row ) {
				$appointments[] = new FB_Appointment( $row );
			}
		}

		return $appointments;
	}
}
