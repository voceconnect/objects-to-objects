<?php

class O2O {

	const DB_VERSION = '1.2';
	
	private static $instance;
	public $connection_factory;
	public $rewrites_enabled = false;

	protected function __construct( $connection_factory ) {
		$this->connection_factory = $connection_factory;
	}

	/**
	 *
	 * @return O2O_Connection_Factory
	 */
	public function get_connection_factory() {
		return $this->connection_factory;
	}

	/**
	 *
	 * @return O2O
	 */
	public static function GetInstance() {
		if ( self::$instance === null ) {
			$connection_factory = new O2O_Connection_Factory();
			$query = new O2O_Query( $connection_factory );

			self::$instance = new O2O( $connection_factory, $query );
		}
		return self::$instance;
	}

	public static function Register_Connection( $name, $from_object_types, $to_object_types, $args = array() ) {
		self::GetInstance()->connection_factory->register( $name, $from_object_types, $to_object_types, $args );
	}

	public static function Enable_Rewrites( $enabled = true ) {
		self::GetInstance()->rewrites_enabled = $enabled;
	}

	public function init() {

		$query = new O2O_Query( $this->connection_factory );
		$query->init();

		if ( function_exists( 'wpcom_vip_enable_term_order_functionality' ) ) {
			//ensure that the ability to sort terms is setup on WordPress.com VIP
			wpcom_vip_enable_term_order_functionality();
		}

		if ( $this->rewrites_enabled ) {
			$rewrites = new O2O_Rewrites( $this->connection_factory );
			$rewrites->init();
		}

		if ( is_admin() ) {
			if ( !class_exists( 'O2O_Admin' ) ) {
				require_once( dirname( __DIR__ ) . '/admin/admin.php' );
			}
			$admin = new O2O_Admin( $this->connection_factory );
			$admin->init();
		}

		//@todo, move the below to a better location
		//allow custom templates based on connection type
		add_filter( 'archive_template', function($template) {
			global $wp_query;
			if ( is_o2o_connection() ) {
				$additional_templates = array();

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
		add_filter( 'template_redirect', function() {
			global $wp_query, $wpdb;
			if ( is_404() && is_o2o_connection() && !get_queried_object_id() ) {
				$o2o_query = $wp_query->query_vars['o2o_query'];

				if ( $connection = O2O_Connection_Factory::Get_Connection( $o2o_query['connection'] ) ) {
					if ( isset( $o2o_query['post_name'] ) ) {
						$post_name = $o2o_query['post_name'];
						$name_post_types = $o2o_query['direction'] == 'to' ? $connection->from() : $connection->to();

						$post_name = rawurlencode( urldecode( $post_name ) );
						$post_name = str_replace( '%2F', '/', $post_name );
						$post_name = str_replace( '%20', ' ', $post_name );
						$post_name = array_pop( explode( '/', trim( $post_name, '/' ) ) );

						$post_types = array_map( 'esc_sql', ( array ) $name_post_types );
						$post_types_in = "('" . implode( ', ', $post_types ) . "')";
						$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id from $wpdb->postmeta PM JOIN $wpdb->posts P ON P.ID = PM.post_id " .
								"WHERE meta_key = '_wp_old_slug' AND meta_value = %s AND post_type in {$post_types_in} limit 1", $post_name ) );
						if ( $post_id ) {
							if ( $link = get_permalink( $post_id ) ) {
								wp_redirect( $link, 301 );
							}
						}
					}
				}
			}
		}, 10, 2 );

		if( !defined( 'O2O_CLI_UPDATES_ONLY' ) || !O2O_CLI_UPDATES_ONLY ) {
			add_action( 'wp_loaded', array( $this, 'db_update' ) );
		}

		if( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once __DIR__ . '/cli/o2o.php';
		}
	}

	public function db_update() {
		$current_db_version = get_option( 'o2o_db_version' );
		if ( version_compare( $current_db_version, '1.2', '<' ) ) {
			//convert all o2o_term_id meta_keys to be taxonomy specific to adjust for term splitting
			//and fix any previously split terms
			$connections = $this->get_connection_factory()->get_connections();
			$taxonomy_map = array();
			foreach ( $connections as $connection ) {
				if ( method_exists( $connection, 'get_taxonomy' ) ) {
					foreach ( $connection->from() as $post_type ) {
						if ( !isset( $taxonomy_map[$post_type] ) ) {
							$taxonomy_map[$post_type] = array();
						}
						$taxonomy_map[$post_type][] = $connection->get_taxonomy();
					}
					foreach ( $connection->to() as $post_type ) {
						if ( !isset( $taxonomy_map[$post_type] ) ) {
							$taxonomy_map[$post_type] = array();
						}
						$taxonomy_map[$post_type][] = $connection->get_taxonomy();
					}
				}
			}

			$wp_query = new WP_Query( array(
				'fields' => 'ids',
				'meta_query' => array(
					array(
						'key' => 'o2o_term_id',
						'compare' => 'EXISTS'
					)
				),
				'post_status' => 'any',
				'post_type' => array_keys( $taxonomy_map ),
				'posts_per_page' => 100,
				'update_post_term_cache' => false,
				'cache_results' => false,
				'orderby' => 'ID'
				) );

			foreach ( $wp_query->posts as $post_id ) {
				$term_id = get_post_meta( $post_id, 'o2o_term_id', true );
				$post_type = get_post_type( $post_id );
				if ( isset( $taxonomy_map[$post_type] ) ) {
					foreach ( $taxonomy_map[$post_type] as $taxonomy ) {
						if ( function_exists( 'wp_get_split_term' ) && false !== ( $new_term_id = wp_get_split_term( $term_id, $taxonomy ) ) ) {
							add_post_meta( $post_id, 'o2o_term_id_' . $taxonomy, $new_term_id, true );
						} else {
							add_post_meta( $post_id, 'o2o_term_id_' . $taxonomy, $term_id, true );
						}
					}
				}
				delete_post_meta( $post_id, 'o2o_term_id' );
			}

			if ( $wp_query->found_posts < 100 ) {
				update_option( 'o2o_db_version', '1.2' );
			}
		}
	}

}
