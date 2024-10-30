<?php

/**
 * Matprat Add Meta Boxes
 * 
 * @copyright Copyright (c), Metronet
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryan@metronet.no>
 * @since 1.0
 */
class Matprat_Add_Meta_Boxes {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'init', array( $this, 'meta_boxes_save' ) );
	}

	/**
	 * Add admin metabox for thumbnail chooser
	 *
	 * @return void
	 */
	public function add_metabox() {
		add_meta_box(
			'matprat',
			'Matprat',
			array(
				$this,
				'meta_box',
			),
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Output the thumbnail meta box
	 *
	 * @return string HTML output
	 */
	public function meta_box() {
		global $post;

		if ( isset( $_GET['post'] ) )
			$post_ID = (int) $_GET['post'];
		else
			$post_ID = '';

		?>
		<input type="hidden" name="_matprat_hidden" id="_matprat_hidden" value="1" />  
		<p>
			<label for="_matprat_post_title"><?php _e( 'Post title', 'matprat' ); ?></label>  
			<br />
			<input type="text" name="_matprat_post_title" id="_matprat_post_title" value="<?php echo get_post_meta( $post_ID, '_matprat_post_title', true ); ?>" />  
		</p>
		<p>
			<label for="_matprat_post_excerpt"><?php _e( 'Post excerpt', 'matprat' ); ?></label>  
			<br />
			<input type="text" name="_matprat_post_excerpt" id="_matprat_post_excerpt" value="<?php echo get_post_meta( $post_ID, '_matprat_post_excerpt', true ); ?>" />  
		</p>
		<p>
			<h4><?php _e( 'Category', 'matprat' ); ?></h4>
			<?php
			$categories = array(
				'frokost',
				'lunsj',
				'middag',
				'småretter',
				'dessert',
				'kake',
				'brød',
				'søt bakst',
				'andre bakevarer',
			);
			foreach( $categories as $cat ) {
				echo '<input type="radio" name="_matprat_category" value="' . $cat . '"';
				if ( $cat == get_post_meta( $post_ID, '_matprat_category', true ) ) {
					echo 'checked';
				}
				echo '> <label>' . $cat . '</label><br />';
			}

			echo '<small>' . __( 'If no category is selected then the post will not appear on Matprat.', 'matprat' ) . '</small>
		</p>';
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 */
	public function enqueue_admin_scripts() {

		// Bail out now if not on edit post/page page
		if ( !isset( $_GET['post'] ) && !isset( $_GET['post_id'] ) )
			return;

		// Enqueue the script
		wp_enqueue_script(
			'featured-image-custom',
			MATPRAT_URL . '/scripts/multi-post-thumbnails-admin.js',
			array(
				'jquery'
			)
		);
	}

	/**
	 * Save opening times meta box data
	 * 
	 * @since 1.0
	 */
	function meta_boxes_save() {

		// Bail out now if something not set
		if (
			isset( $_POST['_wpnonce'] ) &&
			isset( $_POST['post_ID'] ) &&
			isset( $_POST['_matprat_hidden'] ) // This is required to ensure that auto-saves are not processed
		) {

			// Do nonce security check
			wp_verify_nonce( '_wpnonce', $_POST['_wpnonce'] );

			// Sanitizing data
			if ( isset( $_POST['_matprat_post_title'] ) )
				$_matprat_post_title = $_POST['_matprat_post_title'];
			else
				$_matprat_post_title = '';
			$_matprat_post_title = esc_html( $_matprat_post_title );
			if ( isset( $_POST['_matprat_post_excerpt'] ) )
				$_matprat_post_excerpt = $_POST['_matprat_post_excerpt'];
			else
				$_matprat_post_excerpt = '';
			$_matprat_post_excerpt  = esc_html( $_matprat_post_excerpt );
			if ( isset( $_POST['_matprat_category'] ) )
				$_matprat_category = $_POST['_matprat_category'];
			else
				$_matprat_category = '';
			$_matprat_category = esc_html( $_matprat_category );
			if ( isset( $_POST['_matprat_othercategory'] ) )
				$_matprat_othercategory = $_POST['_matprat_othercategory'];
			else
				$_matprat_othercategory = '';
			$_matprat_othercategory = esc_html( $_matprat_othercategory );
	
			// Grab post ID (cast as integer for security reasons)
			$post_ID = (int) $_POST['post_ID'];

			// Stash the post meta in DB
			update_post_meta( $post_ID, '_matprat_post_title',    $_matprat_post_title );
			update_post_meta( $post_ID, '_matprat_post_excerpt',  $_matprat_post_excerpt );
			update_post_meta( $post_ID, '_matprat_category',      $_matprat_category );
		}

	}

}
