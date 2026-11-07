<?php

/**
 * Test del generatore iCalendar (RFC 5545) e del rendering dei template email.
 */
class Test_FB_ICS extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-ics.php';
	}

	private function sample( array $override = array() ) {
		$tz = new DateTimeZone( 'Europe/Rome' );
		return FB_ICS::build( array_merge( array(
			'uid'             => 'fb-appointment-7@example.com',
			'start'           => new DateTimeImmutable( '2026-11-05 09:30:00', $tz ),
			'end'             => new DateTimeImmutable( '2026-11-05 10:15:00', $tz ),
			'summary'         => 'Taglio, Piega; Colore',
			'description'     => "Riga 1\nRiga 2",
			'location'        => 'Via del Corso 120, Roma',
			'organizer_name'  => 'Salone',
			'organizer_email' => 'info@salone.it',
			'attendee_name'   => 'Mario Rossi',
			'attendee_email'  => 'mario@example.com',
		), $override ) );
	}

	public function test_structure_and_crlf() {
		$ics = $this->sample();
		$this->assertStringStartsWith( "BEGIN:VCALENDAR\r\n", $ics );
		$this->assertStringEndsWith( "END:VCALENDAR\r\n", $ics );
		$this->assertStringContainsString( "VERSION:2.0\r\n", $ics );
		$this->assertStringContainsString( "UID:fb-appointment-7@example.com\r\n", $ics );
		$this->assertStringNotContainsString( "\n\n", str_replace( "\r\n", '', $ics ) );
	}

	public function test_times_converted_to_utc() {
		$ics = $this->sample();
		// Europe/Rome è UTC+1 a novembre
		$this->assertStringContainsString( "DTSTART:20261105T083000Z\r\n", $ics );
		$this->assertStringContainsString( "DTEND:20261105T091500Z\r\n", $ics );
	}

	public function test_text_escaping() {
		$ics = $this->sample();
		$this->assertStringContainsString( 'SUMMARY:Taglio\\, Piega\\; Colore', $ics );
		$this->assertStringContainsString( 'DESCRIPTION:Riga 1\\nRiga 2', $ics );
	}

	public function test_line_folding_max_75_octets() {
		$ics = $this->sample( array( 'description' => str_repeat( 'àèìòù ', 40 ) ) );
		foreach ( explode( "\r\n", $ics ) as $line ) {
			$this->assertLessThanOrEqual( 75, strlen( $line ) );
		}
	}

	public function test_cancel_method_has_no_alarm() {
		$ics = $this->sample( array( 'status' => 'CANCELLED', 'method' => 'CANCEL', 'sequence' => 2 ) );
		$this->assertStringContainsString( "METHOD:CANCEL\r\n", $ics );
		$this->assertStringContainsString( "STATUS:CANCELLED\r\n", $ics );
		$this->assertStringContainsString( "SEQUENCE:2\r\n", $ics );
		$this->assertStringNotContainsString( 'VALARM', $ics );
	}
}
