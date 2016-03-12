<?php

class O2O_Mock_Query_Modifier extends O2O_Query_Modifier {

	private static $calledMethods = array( );

	public static function wasCalled( $method ) {
		return in_array( $method, self::$calledMethods );
	}

	public static function posts_results( $posts, $wp_query, $connection, $o2o_query ) {
		self::$calledMethods[] = __FUNCTION__;
	}

	public static function parse_query( $wp_query, $connection, $o2o_query ) {
		self::$calledMethods[] = __FUNCTION__;
	}

}