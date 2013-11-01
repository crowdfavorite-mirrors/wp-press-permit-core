<?php
class PP_FrontTags {
	public static function flt_get_tags( $results, $taxonomies, $args ) {
		global $wpdb;

		$defaults = array(
		'exclude' => '', 'include' => '',
		'number' => '', 'offset' => '', 'slug' => '', 
		'name__like' => '', 'search' => '', 'hide_empty' => true );
		$args = wp_parse_args( $args, $defaults );
		extract($args, EXTR_SKIP);
		
		if ( ( 'ids' == $fields ) || ! $hide_empty )
			return $results;
		
		global $pp, $pp_current_user;

		//------------ WP argument application code from get_terms(), with hierarchy-related portions removed -----------------
		//
		// NOTE: must change 'tt.count' to 'count' in orderby and hide_empty settings
		//		 Also change default orderby to name
		//
		$where = '';
		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';
			$exclude_tree = '';
			$interms = wp_parse_id_list($include);
			if ( count($interms) ) {
				foreach ( (array) $interms as $interm ) {
					if (empty($inclusions))
						$inclusions = ' AND ( t.term_id = ' . intval($interm) . ' ';
					else
						$inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
				}
			}
		}
	
		if ( !empty($inclusions) )
			$inclusions .= ')';
		$where .= $inclusions;
	
		$exclusions = '';
		if ( !empty($exclude) ) {
			$exterms = wp_parse_id_list($exclude);
			if ( count($exterms) ) {
				foreach ( (array) $exterms as $exterm ) {
					if ( empty($exclusions) )
						$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
					else
						$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
				}
			}
		}
	
		if ( !empty($exclusions) )
			$exclusions .= ')';
		$exclusions = apply_filters('list_terms_exclusions', $exclusions, $args );
		$where .= $exclusions;
	
		if ( !empty($slug) ) {
			$slug = sanitize_title($slug);
			$where .= " AND t.slug = '$slug'";
		}
	
		if ( !empty($name__like) )
			$where .= " AND t.name LIKE '{$name__like}%'";
	
		// don't limit the query results when we have to descend the family tree 
		if ( ! empty($number) ) {
			if( $offset )
				$limit = 'LIMIT ' . (int) $offset . ',' . (int) $number;
			else
				$limit = 'LIMIT ' . (int) $number;
	
		} else
			$limit = '';
	
		if ( !empty($search) ) {
			$search = like_escape($search);
			$where .= $wpdb->prepare( " AND (t.name LIKE %s)", "%$search%" );
		}
		// ------------- end get_terms() argument application code --------------
		
		$post_type = pp_find_post_type();
		
		// embedded select statement for posts ID IN clause
		$posts_qry = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE 1=1";
		$posts_qry = apply_filters( 'pp_posts_request', $posts_qry, array('post_types' => $post_type, 'skip_teaser' => true) );

		$qry = "SELECT DISTINCT t.*, tt.*, COUNT(p.ID) AS count FROM $wpdb->terms AS t"
			. " INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id AND tt.taxonomy = 'post_tag'"
			. " INNER JOIN $wpdb->term_relationships AS tagr ON tagr.term_taxonomy_id = tt.term_taxonomy_id"
			. " INNER JOIN $wpdb->posts AS p ON p.ID = tagr.object_id WHERE p.ID IN ($posts_qry)"
			. " $where GROUP BY t.term_id ORDER BY count DESC $limit";  // must hardcode orderby clause to always query top tags
			
		$results = $wpdb->get_results( $qry );

		return apply_filters('pp_get_tags', $results, 'post_tag', $args);
	}
} // end class
