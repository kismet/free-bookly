<?php

class Test_FB_Staff extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		
		// Forziamo l'installazione delle tabelle prima di ogni test
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-free-bookly-activator.php';
		Free_Bookly_Activator::activate();
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-fb-staff.php';
	}

	public function test_staff_creation() {
		$data = array(
			'full_name' => 'Mario Rossi',
			'email'     => 'mario@example.com',
			'phone'     => '+39123456789'
		);

		$staff = new FB_Staff( $data );
		$result = $staff->save();

		$this->assertTrue( $result, 'Lo staff non è stato salvato nel database.' );
		$this->assertGreaterThan( 0, $staff->id, 'L ID non è stato valorizzato dopo il salvataggio.' );

		// Lettura dal database
		$saved_staff = FB_Staff::get_by_id( $staff->id );
		$this->assertNotNull( $saved_staff );
		$this->assertEquals( 'Mario Rossi', $saved_staff->full_name );
	}
	
	public function test_staff_update() {
		$staff = new FB_Staff( array( 'full_name' => 'Luigi Verdi' ) );
		$staff->save();
		
		$staff->full_name = 'Luigi Gialli';
		$staff->save();
		
		$updated_staff = FB_Staff::get_by_id( $staff->id );
		$this->assertEquals( 'Luigi Gialli', $updated_staff->full_name );
	}
	
	public function test_staff_deletion() {
		$staff = new FB_Staff( array( 'full_name' => 'Da Eliminare' ) );
		$staff->save();
		$id = $staff->id;
		
		$result = $staff->delete();
		$this->assertTrue( $result );
		
		$deleted_staff = FB_Staff::get_by_id( $id );
		$this->assertNull( $deleted_staff );
	}
}
