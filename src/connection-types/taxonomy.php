<?php

class O2O_Connection_Taxonomy extends aO2O_Connection implements iO2O_Connection {

	private $taxonomy;

	public function __construct( $name, $from_object_types, $to_object_types, $args = array( ) ) {
		parent::__construct( $name, $from_object_types, $to_object_types, $args );

		$this->args['from']['sortable'] = false;
		
		
		if( ( $this->args['to']['limit'] <= -1 || $this->args['to']['limit'] > 50 ) && $this->is_sortable( 'to') ) {
			//force a limit if the 'to' direction is sortable since we'll have to pull in all 
			//results and reorder to implement paging for user sorts
			$this->args['to']['limit'] = 50;
		}
		
		$this->taxonomy = 'o2o_' . $name;

		register_taxonomy( $this->taxonomy, $from_object_types, array(
			'rewrite' => false,
			'public' => false,
			'sort' => $this->is_sortable('to'),
		) );
	}

	/**
	 * Relates the given object to the connected_to objects
	 * @param int $object_id ID of the object being connected from
	 * @param array $connected_to_ids
	 * @param bool $append whether to append to the current connected is or overwrite
	 */
	public function set_connected_to( $from_object_id, $connected_to_ids = array( ), $append = false ) {
		global $wpdb;

		if ( !in_array( get_post_type( $from_object_id ), $this->from_object_types ) ) {
			return new WP_Error( 'invalid_post_type', 'The given post type is not valid for this connection type.' );
		}

		$term_ids = array_map( array( __CLASS__, 'GetObjectTermID' ), $connected_to_ids );

		//because wp_set_object_terms will only work if the term exists for the given taxonomy, we need to verify that each term exists
		foreach ( $term_ids as $term_id ) {
			if ( !term_exists( $term_id, $this->taxonomy ) ) {
				//no core way to insert a term into a taxonomy by ID, so we need to get the slug
				$term_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug from $wpdb->terms where term_id = %d", $term_id ) );

				wp_insert_term( $term_slug, $this->taxonomy );
			}
		}

		wp_set_object_terms( $from_object_id, $term_ids, $this->taxonomy, $append );
	}

	/**
	 * Returns the IDs which are connected to the given object
	 * @param int $object_id ID of the object from which connections are set
	 * @return array|WP_Error 
	 */
	public function get_connected_to_objects( $from_object_id ) {
		if ( !in_array( get_post_type( $from_object_id ), $this->from_object_types ) ) {
			return new WP_Error( 'invalid_post_type', 'The given post type is not valid for this connection type.' );
		}

		$term_ids = $this->get_connected_terms( $from_object_id );
		$to_object_ids = array_map( array( __CLASS__, 'GetObjectForTerm' ), $term_ids );
		return $to_object_ids;
	}

	/**
	 *
	 * Returns the IDs which are connected from the given object
	 * @param type $object_id 
	 * @return array of connected from object ids
	 * 
	 * @todo add caching?
	 */
	public function get_connected_from_objects( $to_object_id ) {
		if ( !in_array( get_post_type( $to_object_id ), $this->to_object_types ) ) {
			return new WP_Error( 'invalid_post_type', 'The given post type is not valid for this connection type.' );
		}

		$term_id = self::GetObjectTermID( $to_object_id );

		$connected_from_objects = get_objects_in_term( $term_id, $this->taxonomy );

		return $connected_from_objects;
	}
	
	/**
	 * Returns the term_ids for the connected terms to an object
	 * @param intt $object_id
	 * @return array
	 */
	private function get_connected_terms( $object_id ) {
		$terms = get_object_term_cache( $object_id, $this->taxonomy );

		$terms = wp_cache_get( $this->taxonomy . '_relationships_ordered' );

		if ( false === $terms ) {
			$terms = wp_get_object_terms( $object_id, $this->taxonomy, array( 'orderby' => 'term_order', 'fields' => 'ids' ) );
			wp_cache_add( $object_id, $terms, $this->taxonomy . '_relationships_ordered' );
		}

		if ( empty( $terms ) )
			return array( );

		return $terms;
	}
	
	/**
	 * Returns the term_id for the given object_id.  A term is shared across all taxonomies
	 * for a single object.
	 * @param int $object_id
	 * @param bool $create Whether to create the term if it doesn't exist
	 * @return int 
	 */
	private static function GetObjectTermID( $object_id, $create = true ) {
		if ( !( $term_id = intval( get_post_meta( $object_id, 'o2o_term_id', true ) ) ) && $create ) {
			$term_id = self::CreateTermForObject( $object_id );
		}
		return $term_id;
	}

	/**
	 * Creates the representing term for the object.  A direct insert is used since WP Core
	 * doesn't support inserting terms except for a specific taxonomy.
	 * @global DB $wpdb
	 * @param int $object_id
	 * @return int|WP_Error 
	 */
	private static function CreateTermForObject( $object_id ) {
		global $wpdb;

		$name = $slug = 'o2o-post-' . $object_id;
		if ( $term_id = term_exists( $slug ) ) {

			$existing_object_id = self::GetObjectForTerm( $term_id );

			if ( $existing_object_id === false ) {
				//somehow have orphaned term, just reattach
			} elseif ( $existing_object_id !== $object_id ) {
				//we have a term pointing to the wrong object for some reason, this may change later, but for now, we're
				//assuming that the connections made to this term are for the object which has a matching id, so leave 
				//connections as they are and just fix term connection
				delete_post_meta( $existing_object_id, 'o2o_term_id' );
			}
		} else {
			//create the new term
			if ( false === $wpdb->insert( $wpdb->terms, compact( 'name', 'slug' ) ) )
				return new WP_Error( 'db_insert_error', __( 'Could not insert term into the database' ), $wpdb->last_error );
			$term_id = ( int ) $wpdb->insert_id;
		}

		add_post_meta( $object_id, 'o2o_term_id', $term_id, true );
		wp_cache_set( 'o2o_object_' . $term_id, $object_id );

		return $term_id;
	}

	/**
	 * Retrieves the object_id for the passed in o2o term_id
	 * @param int $term_id The term should be for an o2o term only
	 * @return int|bool the object_id of the matching term, or false if no object exists 
	 */
	private static function GetObjectForTerm( $term_id ) {
		$cache_key = 'o2o_object_' . $term_id;

		if ( !($object_id = wp_cache_get( $cache_key )) ) {
			$posts = get_posts( array( 'meta_query' => array( array( 'key' => 'o2o_term_id', 'value' => $term_id ) ), 'post_type' => get_post_types(), 'post_status' => 'any' ) );
			if ( count( $posts ) === 1 ) {
				$object_id = $posts[0]->ID;
				wp_cache_set( $cache_key, $object_id );
			} else {
				//A real o2o term having anything but 1 object should never happen
				return false;
			}
		}
		return $object_id;
	}

}

/**
 * NOTES:
 * -we're using a taxonomy per connection type instead of per object type because it allows multiple connections between
 * object types, the drawback is that heirachy can't be applied to the taxonomy terms, and it means we have to specifically create
 * terms since that functionality doesn't stand alone
 * 
 * -when getting the objects connected to an object, we'll need to get all children as well, since the taxonomy won't have that capability
 * doing it this way.
 */