<?php

class Test_FB_Service extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-service.php';
	}

	public function test_service_creation() {
		$service = new FB_Service( array(
			'title'    => 'Massaggio Relax',
			'duration' => 50,
			'price'    => 65.00,
			'capacity' => 2,
		) );
		$result = $service->save();

		$this->assertTrue( $result );
		$this->assertGreaterThan( 0, $service->id );

		$retrieved = FB_Service::get_by_id( $service->id );
		$this->assertNotNull( $retrieved );
		$this->assertEquals( 'Massaggio Relax', $retrieved->title );
		$this->assertEquals( 50, $retrieved->duration );
		$this->assertEquals( 65.00, (float) $retrieved->price );
		$this->assertEquals( 2, $retrieved->capacity );
	}

	public function test_service_update() {
		$service = new FB_Service( array( 'title' => 'Taglio Uomo', 'price' => 20.00 ) );
		$service->save();

		$service->price = 25.00;
		$service->save();

		$updated = FB_Service::get_by_id( $service->id );
		$this->assertEquals( 25.00, (float) $updated->price );
	}

	public function test_service_deletion() {
		$service = new FB_Service( array( 'title' => 'Da Cancellare' ) );
		$service->save();
		$id = $service->id;

		$result = $service->delete();
		$this->assertTrue( $result );

		$deleted = FB_Service::get_by_id( $id );
		$this->assertNull( $deleted );
	}

	public function test_service_get_all() {
		$service1 = new FB_Service( array( 'title' => 'Servizio A' ) );
		$service1->save();
		$service2 = new FB_Service( array( 'title' => 'Servizio B' ) );
		$service2->save();

		$services = FB_Service::get_all();
		$this->assertGreaterThanOrEqual( 2, count( $services ) );
	}
}
