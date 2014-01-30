<?php

class O2O_Mock_Connection extends aO2O_Connection {
	public function get_query_modifier() {
		return 'O2O_Mock_Query_Modifier';
	}

	/**
	 * Relates the given object to the connected_to objects
	 * @param int $object_id ID of the object being connected from
	 * @param array $connected_to_ids
	 * @param bool $append whether to append to the current connected is or overwrite
	 */
	public function set_connected_to( $from_object_id, $connected_ids, $append ) {}

	/**
	 * Returns the IDs which are connected to the given object
	 * @param int $object_id ID of the object from which connections are set
	 * @return array|WP_Error 
	 */
	public function get_connected_to_objects( $from_object_id ) {
		return array( 1, 2 );
	}

	/**
	 *
	 * Returns the IDs which are connected from the given object
	 * @param type $object_id 
	 * @return array of connected from object ids
	 */
	public function get_connected_from_objects( $to_object_id ) {
		return array( 1, 2 );
	}
}