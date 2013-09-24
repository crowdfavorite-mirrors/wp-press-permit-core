<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_multisite() && ( ! pp_wp_ver('3.5') || get_site_option( 'ms_files_rewriting' ) ) ) {
	require_once( dirname(__FILE__).'/rewrite-ms_pp.php' );
}

/**
 * PP_Rewrite class
 * 
 * @package PP
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 * 
 */
 
class PP_Rewrite {
	public function insert_with_markers( $file_path, $marker_text, $insertion ) {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			if ( file_exists( ABSPATH . '/wp-admin/includes/misc.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/misc.php' );
			else
				return;
		}

		if ( $insertion || file_exists($file_path) )	
			insert_with_markers( $file_path, $marker_text, explode( "\n", $insertion ) );
	}

	function site_config_supports_rewrite() {
		$uploads = ppfx_get_upload_info();
		
		if ( false === strpos( $uploads['baseurl'], untrailingslashit( get_option('siteurl') ) ) )
			return false;
		
		// don't risk leaving custom .htaccess files in content folder at deactivation due to difficulty of reconstructing custom path for each blog
		if ( is_multisite() && ( ! pp_wp_ver('3.5') || get_site_option( 'ms_files_rewriting' ) ) ) {
			global $blog_id, $pagenow;
			
			if ( 'site-new.php' == $pagenow )
				return true;
			
			if ( UPLOADS != UPLOADBLOGSDIR . "/$blog_id/files/" )
				return false;
				
			if ( BLOGUPLOADDIR != WP_CONTENT_DIR . "/blogs.dir/$blog_id/files/" )
				return false;
		}
		
		return true;
	}
	
	function update_site_file_rules( $include_pp_rules = true ) {
		global $blog_id;

		pp_update_option( 'file_htaccess_date', pp_time_gmt() );
		
		if ( ! PP_Rewrite::site_config_supports_rewrite() ) {
			return;
		} else {
			$rules = PP_Rewrite::build_site_file_rules();
		}

		$uploads = ppfx_get_upload_info();

		// If a filter has changed MU basedir, don't filter file attachments for this site because we might not be able to regenerate the basedir for rule removal at RS deactivation
		if ( ! is_multisite() || ( pp_wp_ver('3.5') && ! get_site_option( 'ms_files_rewriting' ) ) || ( strpos( $uploads['basedir'], "/blogs.dir/$blog_id/files" ) || ( false !== strpos( $uploads['basedir'], trailingslashit(WP_CONTENT_DIR) . 'uploads' ) ) ) ) {
			$htaccess_path = trailingslashit($uploads['basedir']) . '.htaccess';
			self::insert_with_markers( $htaccess_path, 'Press Permit', $rules );
		}
	}
	
	function &build_site_file_rules() {
		$new_rules = '';
		
		require_once( dirname(__FILE__).'/analyst_pp.php' );
		if ( ! $attachment_results = PP_Analyst::identify_protected_attachments() ) {
			return $new_rules;
		}

		global $wpdb;

		$home_root = parse_url(get_option('home'));
		$home_root = trailingslashit( $home_root['path'] );
		
		$uploads = ppfx_get_upload_info();
		
		$baseurl = trailingslashit( $uploads['baseurl'] );
		
		$arr_url = parse_url( $baseurl );
		$rewrite_base = $arr_url['path'];
		
		$file_keys = array();
		$has_postmeta = array();
		
		if ( $key_results = $wpdb->get_results( "SELECT pm.meta_value, p.guid, p.ID FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON p.ID = pm.post_id WHERE pm.meta_key = '_rs_file_key'" ) ) {
			foreach ( $key_results as $row ) {
				$file_keys[$row->guid] = $row->meta_value;
				$has_postmeta[$row->ID] = $row->meta_value;
			}
		}
	
		$new_rules = "<IfModule mod_rewrite.c>\n";
		$new_rules .= "RewriteEngine On\n";
		$new_rules .= "RewriteBase $rewrite_base\n\n";
	
		$main_rewrite_rule = "RewriteRule ^(.*) {$home_root}index.php?attachment=$1&pp_rewrite=1 [NC,L]\n";
	
		$htaccess_urls = array();
	
		$unfiltered_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pp_file_filtering' AND meta_value = '0' AND post_id IN ('" . implode( "','", array_keys( $attachment_results ) ) . "')" );
		
		if ( $pass_small_thumbs = pp_get_option( 'small_thumbnails_unfiltered' ) )
			$thumb_filtered_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pp_file_filtering' AND meta_value = 'all' AND post_id IN ('" . implode( "','", array_keys( $attachment_results ) ) . "')" );
		else
			$thumb_filtered_ids = array();
	
		foreach ( $attachment_results as $row ) {
			if ( false === strpos( $row->guid, $baseurl ) )	// no need to include any attachments which are not in the uploads folder
				continue;

			if ( in_array( $row->ID, $unfiltered_ids ) )
				continue;
				
			if ( ! empty($file_keys[ $row->guid ] ) ) {
				$key = $file_keys[ $row->guid ];
			} else {
				$key = urlencode( str_replace( '.', '', uniqid( strval( rand() ), true ) ) );
				$file_keys[ $row->guid ] = $key;
			}

			if ( ! isset( $has_postmeta[$row->ID] ) || ( $key != $has_postmeta[$row->ID] ) )
				update_post_meta( $row->ID, "_rs_file_key", $key );

			if ( isset( $htaccess_urls[$row->guid] ) )  // if a file is attached to multiple protected posts, use a single rewrite rule for it
				continue;

			$htaccess_urls[$row->guid] = true;
			
			$rel_path =  str_replace( $baseurl, '', $row->guid );
			
			// escape spaces
			$file_path =  str_replace( ' ', '\s', $rel_path );
			
			// escape horiz tabs (yes, at least one user has them in filenames)
			$file_path =  str_replace( chr(9), '\t', $file_path );

			// strip out all other nonprintable characters.  Affected files will not be filtered, but we avoid 500 error.  Possible TODO: advisory in file attachment utility
			$file_path =  preg_replace( '/[\x00-\x1f\x7f]/', '', $file_path );

			// escape all other regular expression operator characters
			$file_path =  preg_replace( '/[\^\$\.\+\[\]\(\)\{\}]/', '\\\$0', $file_path );

			if ( 0 === strpos( $row->post_mime_type, 'image' ) && $pos_ext = strrpos( $file_path, '\.' ) ) {				
				$thumb_path = substr( $file_path, 0, $pos_ext );
				$ext = substr( $file_path, $pos_ext + 2 );	

				$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$thumb_path" . '(|-[0-9]{2,4}x[0-9]{2,4})\.' . $ext . "$ [NC]\n";  // covers main file and thumbnails that use standard naming pattern
				if ( $pass_small_thumbs && ! in_array( $row->ID, $thumb_filtered_ids ) )
					$new_rules .= "RewriteCond %{REQUEST_URI} !^(.*)" . (int) get_option('thumbnail_size_w') . 'x' . (int) get_option('thumbnail_size_h') . "\.jpg$ [NC]\n";

				$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
				$new_rules .= $main_rewrite_rule;
				
				// if resized image file(s) exist, include rules for them
				$guid_pos_ext = strrpos( $rel_path, '.' );
				$pattern = $uploads['path'] . '/' . substr( $rel_path, 0, $guid_pos_ext ) . '-??????????????' . substr( $rel_path, $guid_pos_ext );
				if ( glob( $pattern ) ) {
					$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$thumb_path" . '-[0-9,a-f]{14}\.' . $ext . "$ [NC]\n";
					$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
					$new_rules .= $main_rewrite_rule;
				}
			} else {
				$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$file_path" . "$ [NC]\n";
				$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
				$new_rules .= $main_rewrite_rule;
			}
		} // end foreach protected attachment
		
		if ( ppff_ms_blogs_filtering() ) {
			global $blog_id;
			$file_filtered_sites = (array) get_site_option( 'pp_file_filtered_sites' );
			if ( ! in_array( $blog_id, $file_filtered_sites ) ) {
				// this site needs a file redirect rule in root .htaccess
				pp_flush_main_rules();
			}
		}
		
		$new_rules .= "</IfModule>\n";
		
		return $new_rules;
	}
	
	// called by agp_return_file() in abnormal cases where file access is approved, but key for protected file is lost/corrupted in postmeta record or .htaccess file
	function resync_file_rules() {
		// Don't allow this to execute too frequently, to prevent abuse or accidental recursion
		if ( pp_time_gmt() - get_option( 'pp_last_htaccess_resync' ) > 30 ) {
			update_option( 'pp_last_htaccess_resync', pp_time_gmt() );
			
			// Only the files / uploads .htaccess for current site is regenerated
			pp_flush_file_rules();
			
			usleep(10000); // Allow 10 milliseconds for server to regather itself following .htaccess update
		} 
	}
} // end class PP_Rewrite
?>