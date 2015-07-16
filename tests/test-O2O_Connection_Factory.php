<?php
/**
 * @coversDefaultClass O2O_Connection_Factory
 */
class O2O_Connection_Factory_Tests extends WP_UnitTestCase {

	/**
	 * @covers ::register
	 */
	public function test_register_returns_aO2O_Connection() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$this->assertInstanceOf( 'aO2O_Connection', $connection );
		$this->assertEquals( $name, $connection->get_name() );
	}

	/**
	 * @covers ::register
	 * @depends test_register_returns_aO2O_Connection
	 */
	public function test_register_returns_previous_instance() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$originalConnection = $factory->register( $name, 'post', 'post' );

		$duplicateConnection = $factory->register( $name, 'post', 'post' );

		$this->assertSame( $originalConnection, $duplicateConnection );
	}


	/**
	 * @covers ::get_connections
	 */
	public function test_get_connections_returns_array() {
		$factory = new O2O_Connection_Factory();

		$connections = $factory->get_connections();
		$this->assertInternalType( 'array', $connections );
	}

	/**
	 * @covers ::get_connections
	 * @depends test_get_connections_returns_array
	 */
	public function test_get_connections_returns_registered_connection_by_name() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$connections = $factory->get_connections();
		$this->assertArrayHasKey( $name, $connections );
	}

	/**
	 * @covers ::get_connection
	 */
	public function test_get_connection_returns_valid_connection() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$this->assertSame( $connection, $factory->get_connection( $name ) );
	}

	/**
	 * @covers ::get_connection
	 */
	public function test_get_connection_returns_false_for_invalid_connection() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$this->assertFalse( $factory->get_connection( 'invalid_name' ) );
	}

	/**
	 * @covers ::add
	 */
	public function test_add_sets_connection_by_name() {
		$name = 'test';
		$factory = new O2O_Connection_Factory();
		$connection = $factory->register( $name, 'post', 'post' );

		$factory->add($connection);
		$connections = PHPUnit_Framework_Assert::readAttribute($factory, 'connections');
		$this->assertInternalType( 'array', $connections );
		$this->assertArrayHasKey( $name, $connections );
		$this->assertSame( $connections[$name], $connection );
	}

}