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
		
		//@todo, move this to a better location
		add_filter( 'archive_template', function($template) {
				global $wp_query;
				if ( is_o2o_connection() ) {
					$additional_templates = array( );

					if ( ($post_type = ( array ) get_query_var( 'post_type' )) && (count( $post_type ) == 1) ) {

						$additional_templates[] = "o2o-{$wp_query->o2o_connection}-{$wp_query->query_vars['o2o_query']['direction']}-{$post_type[0]}.php";

						$additional_templates[] = "o2o-{$wp_query->o2o_connection}-{$post_type[0]}.php";
					}

					$additional_templates[] = "o2o-{$wp_query->o2o_connection}.php";
					if ( $o2o_template = locate_template( $additional_templates ) ) {
						return $o2o_template;
					}
				}
				return $template;
			} );
	}

	public static function Enable_Rewrites( $enabled = true ) {
		self::$rewrites_enabled = $enabled;
	}

}

add_action( 'init', array( 'O2O', 'init' ), 99 );