<?php

/**
 * Matprat Modify Feed
 * Modifies the sites RSS feed based on Matprat specific settings chosen
 * 
 * @copyright Copyright (c), Metronet
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryan@metronet.no>
 * @since 1.0
 */
class Matprat_Modify_feed {

	/*
	 * Constructor
	 * Loads filters and hooks
	 */
	public function __construct() {

		if ( empty( $_GET['matprat_feed'] ) )
			return;

		add_filter( 'get_the_content_feed', array( $this, 'matprat_get_the_content_feed' ) );
		add_filter( 'the_excerpt_rss',      array( $this, 'matprat_the_excerpt_rss' ) );
		add_filter( 'get_the_categories',   array( $this, 'matprat_get_the_categories' ) );
		add_filter( 'the_title_rss',        array( $this, 'matprat_the_title_rss' ) );
		add_action( 'rss2_item',            array( $this, 'matprat_add_post_featured_image_as_rss_item_enclosure' ) );
		add_filter('the_content',           array( $this, 'add_fields_to_content' ) );
	}

	/*
	 * Adds filelds to the RSS feed content
	 * This is used as a crude method of moving specific content via RSS.
	 */
	public function add_fields_to_content( $content ) {
		global $post, $id;
		
		// Bail out now if not in feed
		if ( ! is_feed() )
			return $content;
		
		// Grab post author ID
		$author_id = $post->post_author;
		
		// Create Gravatar URL
		$email = get_the_author_meta( 'user_email' , $author_id );
		$hash = md5( strtolower( trim ( $email ) ) );
		$gravatar_url = 'http://gravatar.com/avatar/' . $hash;

		$categories = get_post_meta( $id, '_matprat_category', true );

		// Add extra content to the_content
		$content = ";</p>-->\n\n\n\n\n\n" . $content;
		$content = 'Author first name: ' . get_the_author_meta( 'first_name' , $author_id ) . $content;
		$content = 'Author last name: ' . get_the_author_meta( 'last_name' , $author_id ) . ';' . $content;
		$content = 'Author nice name: ' . get_the_author_meta( 'user_nicename' , $author_id ) . ';' . $content;
		$content = 'Author image URL: ' . $gravatar_url . ";" . $content;
		$content = '<!--<p>Categories: ' . $categories . ";" . $content;

		return $content;
	} // End function
	
	/*
	 * Code adapted from work by Kaspars Dambis
	 * https://github.com/kasparsd/feed-image-enclosure/
	 *
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 */
	public function matprat_add_post_featured_image_as_rss_item_enclosure() {
		$thumbnail_size = 'large';

		$thumbnail_id = get_post_thumbnail_id( get_the_ID() );
		$thumbnail = image_get_intermediate_size( $thumbnail_id, $thumbnail_size );

		// Set Matprat specific thumbnail if it exists
		$matprat_thumbnail_id = get_post_meta( get_the_ID(), 'post_matprat_thumbnail_id', true );
		if ( $matprat_thumbnail_id ) {
			$thumbnail_id = $matprat_thumbnail_id;
			$thumbnail = image_get_intermediate_size( $thumbnail_id, $thumbnail_size );
		}
	
		if ( empty( $thumbnail ) )
			return;
	
		$upload_dir = wp_upload_dir();
	
		printf( 
			'<enclosure url="%s" length="%s" type="%s" />',
			$thumbnail['url'], 
			filesize( path_join( $upload_dir['basedir'], $thumbnail['path'] ) ), 
			get_post_mime_type( $thumbnail_id ) 
		);
	}

	/*
	 * Adds the Matprat specific title to the RSS feed
	 */
	public function matprat_the_title_rss( $title ) {
		global $post;
		$post_ID = $post->ID;
		$new_title = get_post_meta( $post_ID, '_matprat_post_title', true );
		if ( $new_title ) {
			$title = $new_title;
		}
	
		return $title;
	}

	/*
	 * Sets the Matprat specific execerpt in the RSS feed
	 */
	public function matprat_the_excerpt_rss( $excerpt ) {
		global $post;
		$post_ID = $post->ID;
		$new_excerpt = get_post_meta( $post_ID, '_matprat_post_excerpt', true );
		if ( $new_excerpt ) {
			$excerpt = $new_excerpt;
		}
	
		return $excerpt;
	}
	
	/*
	 * Grabs the categories used for Matprat
	 * Instead of using the default categories for the individual blog
	 */
	public function matprat_get_the_categories( $category ) {
		global $post;
		$post_ID = $post->ID;
		$matprat_category = get_post_meta( $post_ID, '_matprat_category', true );
	
		if ( $matprat_category ) {
			$new_category_array = $category[0];
			$new_category_name = $new_category_array->name;
			$new_category_slug = $new_category_array->slug;
		
			$new_category_array->name = $matprat_category;
			$new_category_array->slug = sanitize_title( $matprat_category );
			$category[0] = $new_category_array;
		}
	
		return $category;
	}
}
