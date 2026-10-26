<?php

/**
 * Classe responsabile del calcolo degli slot disponibili per la prenotazione,
 * considerando orari di lavoro personalizzati, pause e appuntamenti esistenti.
 */
class FB_Availability {

	/**
	 * Ritorna gli slot disponibili per una data specifica.
	 * 
	 * @param int $service_id ID del servizio richiesto
	 * @param mixed $staff_id ID del membro dello staff (int) o 'any'/0
	 * @param string $date Data nel formato 'Y-m-d'
	 * @return array Array di slot disponibili ordinati (es. ['09:00', '09:30', ...])
	 */
	public function get_available_slots( $service_id, $staff_id, $date ) {
		require_once dirname( __FILE__ ) . '/class-fb-service.php';
		require_once dirname( __FILE__ ) . '/class-fb-staff.php';
		require_once dirname( __FILE__ ) . '/class-fb-staff-schedule.php';

		$service = FB_Service::get_by_id( $service_id );
		if ( ! $service ) {
			return array();
		}

		// Se lo staff è "any" o 0, controlla tutti i membri del team abilitati al servizio
		if ( empty( $staff_id ) || 'any' === (string) $staff_id ) {
			$staff_list = FB_Staff::get_by_service_id( $service_id );
			if ( empty( $staff_list ) ) {
				$staff_list = FB_Staff::get_all();
			}

			if ( empty( $staff_list ) ) {
				return array();
			}

			$merged_slots = array();
			foreach ( $staff_list as $member ) {
				$slots = $this->get_staff_slots( $service, $member->id, $date );
				$merged_slots = array_merge( $merged_slots, $slots );
			}

			$unique_slots = array_values( array_unique( $merged_slots ) );
			sort( $unique_slots );
			return $unique_slots;
		}

		return $this->get_staff_slots( $service, absint( $staff_id ), $date );
	}

	/**
	 * Calcola gli slot per un singolo membro dello staff considerando orari e pause.
	 *
	 * @param FB_Service $service
	 * @param int $staff_id
	 * @param string $date
	 * @return array
	 */
	public function get_staff_slots( $service, $staff_id, $date ) {
		$day_index = (int) date( 'N', strtotime( $date ) ); // 1 = Lunedì, 7 = Domenica

		// 1. Recupera la pianificazione del collaboratore per questo giorno
		$day_schedule = FB_Staff_Schedule::get_day_schedule( $staff_id, $day_index );

		if ( ! $day_schedule || empty( $day_schedule['is_working_day'] ) ) {
			// Giorno di riposo / chiuso
			return array();
		}

		$work_start = new DateTime( $date . ' ' . $day_schedule['start_time'] . ':00' );
		$work_end   = new DateTime( $date . ' ' . $day_schedule['end_time'] . ':00' );

		if ( $work_start >= $work_end ) {
			return array();
		}

		$breaks = ! empty( $day_schedule['breaks'] ) ? $day_schedule['breaks'] : array();

		// 2. Recupera appuntamenti esistenti per questo collaboratore in questa data
		$existing_appointments = $this->get_appointments_for_date( $staff_id, $date );

		// 3. Genera slot
		$slots = array();
		$duration_minutes = $service->duration;
		$current_time = clone $work_start;

		while ( $current_time < $work_end ) {
			$slot_end = clone $current_time;
			$slot_end->modify( '+' . $duration_minutes . ' minutes' );

			if ( $slot_end > $work_end ) {
				break;
			}

			$is_free = true;
			$break_jump = false;

			// Controlla collisioni con pause
			foreach ( $breaks as $break ) {
				$break_start = new DateTime( $date . ' ' . $break['start_time'] . ':00' );
				$break_end   = new DateTime( $date . ' ' . $break['end_time'] . ':00' );

				// Sovrapposizione intervallo: Max(A1, B1) < Min(A2, B2)
				if ( max( $current_time, $break_start ) < min( $slot_end, $break_end ) ) {
					$is_free = false;
					if ( $current_time < $break_end ) {
						$current_time = clone $break_end;
						$break_jump = true;
					}
					break;
				}
			}

			if ( $break_jump ) {
				continue;
			}

			// Controlla collisioni con appuntamenti esistenti (se non già occupato da pausa)
			if ( $is_free ) {
				foreach ( $existing_appointments as $appointment ) {
					$app_start = new DateTime( $appointment->start_date );
					$app_end   = new DateTime( $appointment->end_date );

					if ( max( $current_time, $app_start ) < min( $slot_end, $app_end ) ) {
						$is_free = false;
						break;
					}
				}
			}

			if ( $is_free ) {
				$slots[] = $current_time->format( 'H:i' );
			}

			// Salta al prossimo intervallo (step pari alla durata del servizio)
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
