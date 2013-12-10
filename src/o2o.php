<?php

class O2O {

	private static $rewrites_enabled = false;

	public static function Register_Connection( $name, $from_object_types, $to_object_types, $args = array( ) ) {
		return O2O_Connection_Factory::Register( $name, $from_object_types, $to_object_types, $args );
	}

	public static function init() {
		
		O2O_Query::init();
		
		if ( function_exists( 'wpcom_vip_enable_term_order_functionality' ) ) {
			//ensure that the ability to sort terms is setup on WordPress.com VIP
			wpcom_vip_enable_term_order_functionality();
		}
		
		if ( self::$rewrites_enabled ) {
			O2O_Rewrites::Init();
		}

		if ( is_admin() ) {
			if ( ! class_exists( 'O2O_Admin', false ) ) {
				require_once( dirname( __DIR__ ) . '/admin/admin.php' );
			}
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
			
		//redirect canonical o2o based pages to canonical
		add_filter('template_redirect', function(){
			global $wp_query, $wpdb;
			if ( is_404() && is_o2o_connection() && !get_queried_object_id() ) {
				$o2o_query = $wp_query->query_vars['o2o_query'];
				
				if ( $connection = O2O_Connection_Factory::Get_Connection( $o2o_query['connection'] ) ) {
					if(isset( $o2o_query['post_name'] ) ) {
						$post_name = $o2o_query['post_name'];
						$name_post_types = $o2o_query['direction'] == 'to' ? $connection->from() : $connection->to();
						
						$post_name = rawurlencode( urldecode( $post_name ) );
						$post_name = str_replace( '%2F', '/', $post_name );
						$post_name = str_replace( '%20', ' ', $post_name );
						$post_name = array_pop( explode( '/', trim( $post_name, '/' ) ) );

						$post_types = array_map(array($wpdb, 'escape'), (array) $name_post_types);
						$post_types_in = "('" . implode(', ', $post_types) . "')";
						$post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id from $wpdb->postmeta PM JOIN $wpdb->posts P ON P.ID = PM.post_id ".
								"WHERE meta_key = '_wp_old_slug' AND meta_value = %s AND post_type in {$post_types_in} limit 1", $post_name));
						if($post_id) {
							if($link = get_permalink($post_id)) {
								wp_redirect( $link, 301 );
							}
						}
					}	
				}
			}
		}, 10, 2);
	}

	public static function Enable_Rewrites( $enabled = true ) {
		self::$rewrites_enabled = $enabled;
	}

}