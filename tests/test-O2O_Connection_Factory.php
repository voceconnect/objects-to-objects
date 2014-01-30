<?php

class O2O_Connection_Factory_Tests extends WP_UnitTestCase {

	public function test_registration() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$this->assertInstanceOf( 'aO2O_Connection', $connection);
		$this->assertEquals( $name, $connection->get_name() );
	}

	public function test_duplicate_registration() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$originalConnection = $factory->register( $name, 'post', 'post' );

		$duplicateConnection = $factory->register( $name, 'post', 'post' );

		$this->assertSame( $originalConnection, $duplicateConnection );
	}

	public function test_get_all_connections() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$connections = $factory->get_connections();
		$this->assertInternalType( 'array', $connections );
		$this->assertArrayHasKey( $name, $connections );
	}

	public function test_get_connection() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$this->assertFalse( $factory->get_connection( 'invalid_name' ) );

		$this->assertSame( $connection, $factory->get_connection( $name ) );
	}
}