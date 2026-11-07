<?php

/**
 * Generatore di file iCalendar (.ics) conforme a RFC 5545.
 * Compatibile con Google Calendar, Apple Calendar e Microsoft Outlook.
 *
 * Classe volutamente priva di dipendenze da WordPress per essere testabile in isolamento.
 *
 * @author Stefano Lenzi
 */
class FB_ICS {

	/**
	 * Escape dei valori TEXT (RFC 5545 §3.3.11).
	 */
	public static function escape( $text ) {
		$text = (string) $text;
		$text = str_replace( '\\', '\\\\', $text );
		$text = str_replace( array( "\r\n", "\r", "\n" ), '\\n', $text );
		$text = str_replace( array( ';', ',' ), array( '\\;', '\\,' ), $text );
		return $text;
	}

	/**
	 * Line folding a 75 ottetti (RFC 5545 §3.1), senza spezzare sequenze UTF-8.
	 */
	public static function fold( $line ) {
		if ( strlen( $line ) <= 75 ) {
			return $line;
		}

		$out   = '';
		$chunk = '';
		$limit = 75;

		$len = strlen( $line );
		$i   = 0;
		while ( $i < $len ) {
			$byte = ord( $line[ $i ] );
			if ( $byte < 0x80 ) {
				$char_len = 1;
			} elseif ( $byte >= 0xF0 ) {
				$char_len = 4;
			} elseif ( $byte >= 0xE0 ) {
				$char_len = 3;
			} else {
				$char_len = 2;
			}
			$char = substr( $line, $i, $char_len );

			if ( strlen( $chunk ) + $char_len > $limit ) {
				$out  .= $chunk . "\r\n";
				$chunk = ' ';
				$limit = 75;
			}
			$chunk .= $char;
			$i     += $char_len;
		}

		return $out . $chunk;
	}

	/**
	 * Formatta un DateTimeInterface in UTC (es. 20261105T093000Z).
	 */
	public static function format_utc( DateTimeInterface $date ) {
		$utc = DateTimeImmutable::createFromInterface( $date )->setTimezone( new DateTimeZone( 'UTC' ) );
		return $utc->format( 'Ymd\THis\Z' );
	}

	/**
	 * Costruisce il contenuto del file .ics.
	 *
	 * @param array $args {
	 *     @type string            $uid             Identificativo univoco e stabile dell'evento.
	 *     @type DateTimeInterface $start
	 *     @type DateTimeInterface $end
	 *     @type string            $summary
	 *     @type string            $description
	 *     @type string            $location
	 *     @type string            $status          CONFIRMED | TENTATIVE | CANCELLED
	 *     @type string            $method          REQUEST | CANCEL | PUBLISH
	 *     @type int               $sequence        Incrementare per aggiornamenti/annullamenti.
	 *     @type string            $organizer_name
	 *     @type string            $organizer_email
	 *     @type string            $attendee_name
	 *     @type string            $attendee_email
	 *     @type string            $prodid
	 * }
	 * @return string
	 */
	public static function build( array $args ) {
		$args = array_merge(
			array(
				'uid'             => uniqid( 'fb-', true ) . '@free-bookly',
				'summary'         => '',
				'description'     => '',
				'location'        => '',
				'status'          => 'CONFIRMED',
				'method'          => 'REQUEST',
				'sequence'        => 0,
				'organizer_name'  => '',
				'organizer_email' => '',
				'attendee_name'   => '',
				'attendee_email'  => '',
				'prodid'          => '-//Stefano Lenzi//Free Bookly//IT',
			),
			$args
		);

		$lines   = array();
		$lines[] = 'BEGIN:VCALENDAR';
		$lines[] = 'VERSION:2.0';
		$lines[] = 'PRODID:' . $args['prodid'];
		$lines[] = 'CALSCALE:GREGORIAN';
		$lines[] = 'METHOD:' . $args['method'];
		$lines[] = 'BEGIN:VEVENT';
		$lines[] = 'UID:' . $args['uid'];
		$lines[] = 'DTSTAMP:' . self::format_utc( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) );
		$lines[] = 'DTSTART:' . self::format_utc( $args['start'] );
		$lines[] = 'DTEND:' . self::format_utc( $args['end'] );
		$lines[] = 'SEQUENCE:' . (int) $args['sequence'];
		$lines[] = 'STATUS:' . $args['status'];
		$lines[] = 'SUMMARY:' . self::escape( $args['summary'] );

		if ( '' !== $args['description'] ) {
			$lines[] = 'DESCRIPTION:' . self::escape( $args['description'] );
		}
		if ( '' !== $args['location'] ) {
			$lines[] = 'LOCATION:' . self::escape( $args['location'] );
		}
		if ( '' !== $args['organizer_email'] ) {
			$cn      = '' !== $args['organizer_name'] ? ';CN=' . self::escape_param( $args['organizer_name'] ) : '';
			$lines[] = 'ORGANIZER' . $cn . ':mailto:' . $args['organizer_email'];
		}
		if ( '' !== $args['attendee_email'] ) {
			$cn      = '' !== $args['attendee_name'] ? ';CN=' . self::escape_param( $args['attendee_name'] ) : '';
			$lines[] = 'ATTENDEE;ROLE=REQ-PARTICIPANT' . $cn . ':mailto:' . $args['attendee_email'];
		}

		if ( 'CANCELLED' !== $args['status'] ) {
			$lines[] = 'BEGIN:VALARM';
			$lines[] = 'ACTION:DISPLAY';
			$lines[] = 'DESCRIPTION:' . self::escape( $args['summary'] );
			$lines[] = 'TRIGGER:-PT1H';
			$lines[] = 'END:VALARM';
		}

		$lines[] = 'END:VEVENT';
		$lines[] = 'END:VCALENDAR';

		return implode( "\r\n", array_map( array( __CLASS__, 'fold' ), $lines ) ) . "\r\n";
	}

	/**
	 * Escape dei parametri (es. CN): le virgolette non sono ammesse, i caratteri speciali richiedono quoting.
	 */
	private static function escape_param( $value ) {
		$value = str_replace( '"', "'", (string) $value );
		return '"' . $value . '"';
	}
}
