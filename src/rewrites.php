<?php
/**
 * Rewrite handler for O2O 
 */
class O2O_Rewrites {

	/**
	 * Initialization method.  Adds any needed hooks to activate rewrite rules 
	 */
	public static function Init() {
		add_filter('query_vars', array( __CLASS__, 'filter_query_vars'));
		add_action( 'delete_option_rewrite_rules', array( __CLASS__, 'add_rewrite_rules' ), 11 );
	}
	
	/**
	 * Filters the query_vars filter to add rewrite rule based variables
	 * @param array $query_vars
	 * @return array 
	 */
	public static function filter_query_vars($query_vars) {
		return array_merge($query_vars, array('connection_name', 'connected_name', 'connection_dir'));
	}

	/**
	 * Generates the rewrite rules and adds them through the rewrite API based
	 * off of the connection arguments
	 * @throws Exception 
	 */
	public static function add_rewrite_rules() {
		foreach ( O2O_Connection_Factory::Get_Connections() as $connection ) {
			$args = $connection->get_args();
			if ( !empty( $args['rewrite'] ) ) {

				$base_direction = $args['rewrite'] == 'to' ? 'to' : 'from';
				$attached_direction = $base_direction == 'to' ? 'from' : 'to';
				$connection = $this->get_connection( $connection_name );

				foreach ( $connection->$base_direction() as $base_post_type ) {

					//get the connected to post's permastructure
					if ( $base_post_type === 'post' ) { //stupid posts, always break the rules
						$base_post_type_root = trailingslashit( $wp_rewrite->permalink_structure );
						if ( 0 === strpos( $base_post_type_root, '/' ) )
							$base_post_type_root = substr( $base_post_type_root, 1 );
					} else {
						$base_post_type_obj = get_post_type_object( $base_post_type );
						$base_post_type_root = $wp_rewrite->get_extra_permastruct( $base_post_type_obj->query_var );
					}
					$base_post_type_root = str_replace( $wp_rewrite->rewritecode, $wp_rewrite->rewritereplace, $base_post_type_root );

					//create the connected from post's permastructure
					if ( count( $connection->$attached_direction ) === 1 ) {
						$attached_types = $connection->$attached_direction;
						$connected_post_type = $attached_types[0];
						if ( $connected_post_type === 'post' ) { //stupid posts, always break the rules
							$connected_post_type_root = trailingslashit( $wp_rewrite->front );
							if ( 0 === strpos( $connected_post_type_root, '/' ) )
								$connected_post_type_root = substr( $connected_post_type_root, 1 );
						} else {
							$connected_post_type_root = $wp_rewrite->get_extra_permastruct( $connected_post_type );
							$connected_post_type_root = trailingslashit( substr( $connected_post_type_root, 0, strpos( $connected_post_type_root, '%' ) ) );
						}
					} else {
						throw new Exception( "Rewrites to multiple post type connections not yet implemented" );
					}

					//now add the new rules
					add_rewrite_rule( $base_post_type_root . '/' . $connected_post_type_root . 'feed/(feed|rdf|rss|rss2|atom)/?$', $wp_rewrite->index . '?connected_type=' . $connection_name . '&connected_name=$matches[1]&feed=$matches[2]&connection_dir=' . $base_direction, 'top' );
					add_rewrite_rule( $base_post_type_root . '/' . $connected_post_type_root . '(feed|rdf|rss|rss2|atom)/?$', $wp_rewrite->index . '?connected_type=' . $connection_name . '&connected_name=$matches[1]&feed=$matches[2]&connection_dir=' . $base_direction, 'top' );
					add_rewrite_rule( $base_post_type_root . '/' . $connected_post_type_root . 'page/?([0-9]{1,})/?$', $wp_rewrite->index . '?connected_type=' . $connection_name . '&connected_name=$matches[1]&paged=$matches[2]&connection_dir=' . $base_direction, 'top' );
					add_rewrite_rule( $base_post_type_root . '/' . $connected_post_type_root . '?$', $wp_rewrite->index . '?connected_type=' . $connection_name . '&connected_name=$matches[1]&connection_dir=' . $base_direction, 'top' );
				}
			}
		}
	}

}