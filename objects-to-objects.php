<?php

require_once (__DIR__ . '/src/factory.php');
require_once (__DIR__ . '/src/query.php');
require_once (__DIR__ . '/src/rewrites.php');

class O2O {

	private static $rewrites_enabled = false;

	public static function Register_Connection( $name, $from_object_types, $to_object_types, $args = array( ) ) {
		return O2O_Connection_Factory::Register( $name, $from_object_types, $to_object_types, $args );
	}

	public static function init() {
		
		O2O_Query::init();
		
		if ( self::$rewrites_enabled ) {
			O2O_Rewrites::Init();
		}

		if ( is_admin() ) {
			require_once(__DIR__ . '/admin/admin.php');
			O2O_Admin::init();
		}
	}

	private static function Enable_Rewrites( $enabled = true ) {
		self::$rewrites_enabled = $enabled;
	}

}

add_action( 'init', array( 'O2O', 'init' ), 99 );