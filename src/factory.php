<?php

class O2O_Connection_Factory {

	private static $instances = array( );

	/**
	 * Registers a new O2O_Connection
	 * @param string $name
	 * @param array $from_object_types Object types that can be connected from
	 * @param array $to_object_types Object types that can be connected to
	 * @param array $args
	 * @return O2O_Connection_Taxonomy 
	 */
	public static function Register( $name, $from_object_types, $to_object_types, $args = array( ) ) {

		if ( !$connection = self::Get_Connection( $name ) ) {

			$args = wp_parse_args( $args, array(
				) );

			if ( !class_exists( 'O2O_Connection_Taxonomy' ) )
				require_once(__DIR__ . '/connection-types/taxonomy.php');

			$connection = new O2O_Connection_Taxonomy( $name, $from_object_types, $to_object_types, $args );

			self::$instances[$name] = $connection;
		}
		return $connection;
	}

	public static function Get_Connections() {
		return self::$instances;
	}

	/**
	 * Returns the connection for the named instance
	 * @param string $name
	 * @return iO2O_Connection|boolean 
	 */
	public static function Get_Connection( $name ) {
		if ( isset( self::$instances[$name] ) ) {
			return self::$instances[$name];
		}
		return false;
	}

}

interface iO2O_Connection {

	public function get_args();

	public function to();

	public function from();

	public function set_connected_to( $object_id, $connected_ids, $append );

	public function get_connected_to_objects( $object_id );

	public function get_connected_from_objects( $object_id );
}

abstract class aO2O_Connection implements iO2O_Connection {

	public $name;
	public $from_object_types;
	public $to_object_types;
	public $args;

	public function __construct( $name, $from_object_types, $to_object_types, $args = array( ) ) {
		$this->name = $name;
		$this->from_object_types = (array) $from_object_types;
		$this->to_object_types = (array) $to_object_types;
		
		$args = wp_parse_args($args, array(
			'sortable' => false
		));
		
		$this->args = $args;
	}

	public function get_args() {
		return $this->args;
	}

	public function to() {
		return ( array ) $this->to_object_types;
	}

	public function from() {
		return ( array ) $this->from_object_types;
	}

}