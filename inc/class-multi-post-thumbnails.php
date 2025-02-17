<?php
/*
	Code abstracted from the Multiple Post Thumbnails plugin by Chris Scott (http://vocecommuncations.com/)
	http://wordpress.org/extend/plugins/multiple-post-thumbnails/

	Copyright 2010 Chris Scott (cscott@voceconnect.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( !class_exists( 'MultiPostThumbnails' ) ) :
class MultiPostThumbnails {

	public function __construct($args = array()) {
		$this->register($args);
	}

	/**
	 * Register a new post thumbnail.
	 *
	 * Required $args contents:
	 *
	 * label - The name of the post thumbnail to display in the admin metabox
	 *
	 * id - Used to build the CSS class for the admin meta box. Needs to be unique and valid in a CSS class selector.
	 *
	 * Optional $args contents:
	 *
	 * post_type - The post type to register this thumbnail for. Defaults to post.
	 *
	 * priority - The admin metabox priority. Defaults to low to show after normal post thumbnail meta box.
	 *
	 * @param array|string $args See above description.
	 * @return void
	 */
	public function register($args = array()) {
		$defaults = array(
			'label' => null,
			'id' => null,
			'post_type' => 'post',
			'priority' => 'low',
		);

		$args = wp_parse_args($args, $defaults);

		// Create and set properties
		foreach($args as $k => $v) {
			$this->$k = $v;
		}

		// Need these args to be set at a minimum
		if (null === $this->label || null === $this->id) {
			if (WP_DEBUG) {
				trigger_error(sprintf("The 'label' and 'id' values of the 'args' parameter of '%s::%s()' are required", __CLASS__, __FUNCTION__));
			}
			return;
		}

		// add theme support if not already added
		if (!current_theme_supports('post-thumbnails')) {
			add_theme_support( 'post-thumbnails' );
		}

		add_action('add_meta_boxes', array($this, 'add_metabox'));
		add_filter('attachment_fields_to_edit', array($this, 'add_attachment_field'), 20, 2);
		add_action( 'admin_init', array( $this, 'enqueue_admin_scripts' ) );
		add_action("wp_ajax_set-{$this->post_type}-{$this->id}-thumbnail", array($this, 'set_thumbnail'));
		add_action('delete_attachment', array($this, 'action_delete_attachment'));
	}

	/**
	 * Add admin metabox for thumbnail chooser
	 *
	 * @return void
	 */
	public function add_metabox() {
		add_meta_box("{$this->post_type}-{$this->id}", __($this->label), array($this, 'thumbnail_meta_box'), $this->post_type, 'side', $this->priority);
	}

	/**
	 * Output the thumbnail meta box
	 *
	 * @return string HTML output
	 */
	public function thumbnail_meta_box() {
		global $post;
		$thumbnail_id = get_post_meta($post->ID, "{$this->post_type}_{$this->id}_thumbnail_id", true);
		echo $this->post_thumbnail_html($thumbnail_id);
	}

	/**
	 * Throw this in the media attachment fields
	 *
	 * @param string $form_fields
	 * @param string $post
	 * @return void
	 */
	public function add_attachment_field($form_fields, $post) {
		$calling_post_id = 0;
		if (isset($_GET['post_id']))
			$calling_post_id = absint($_GET['post_id']);
		elseif (isset($_POST) && count($_POST)) // Like for async-upload where $_GET['post_id'] isn't set
			$calling_post_id = $post->post_parent;

		// check the post type to see if link needs to be added
		$calling_post = get_post($calling_post_id);
		if (is_null($calling_post) || $calling_post->post_type != $this->post_type) {
			return $form_fields;
		}

		$ajax_nonce = wp_create_nonce("set_post_thumbnail-{$this->post_type}-{$this->id}-{$calling_post_id}");
		$link = sprintf('<a id="%4$s-%1$s-thumbnail-%2$s" class="%1$s-thumbnail" href="#" onclick="MultiPostThumbnailsSetAsThumbnail(\'%2$s\', \'%1$s\', \'%4$s\', \'%5$s\');return false;">Set as %3$s</a>', $this->id, $post->ID, $this->label, $this->post_type, $ajax_nonce);
		$form_fields["{$this->post_type}-{$this->id}-thumbnail"] = array(
			'label' => $this->label,
			'input' => 'html',
			'html' => $link);
		return $form_fields;
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
	 * Deletes the post meta data for posts when an attachment used as a
	 * multiple post thumbnail is deleted from the Media Libray
	 *
	 * @global object $wpdb
	 * @param int $post_id
	 */
	public function action_delete_attachment( $post_id ) {
		global $wpdb;
		$meta_key = "{$this->post_type}_{$this->id}_thumbnail_id";
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = '%s' AND meta_value = %d", $meta_key, $post_id ));
	}

	private function plugins_url($relative_path, $plugin_path) {
		$template_dir = get_template_directory();

		foreach ( array('template_dir', 'plugin_path') as $var ) {
			$$var = str_replace('\\' ,'/', $$var); // sanitize for Win32 installs
			$$var = preg_replace('|/+|', '/', $$var);
		}
		if(0 === strpos($plugin_path, $template_dir)) {
			$url = get_template_directory_uri();
			$folder = str_replace($template_dir, '', dirname($plugin_path));
			if ( '.' != $folder ) {
				$url .= '/' . ltrim($folder, '/');
			}
			if ( !empty($relative_path) && is_string($relative_path) && strpos($relative_path, '..') === false ) {
				$url .= '/' . ltrim($relative_path, '/');
			}
			return $url;
		} else {
			return plugins_url($relative_path, $plugin_path);
		}
	}

	/**
	 * Check if post has an image attached.
	 *
	 * @param string $post_type The post type.
	 * @param string $id The id used to register the thumbnail.
	 * @param string $post_id Optional. Post ID.
	 * @return bool Whether post has an image attached.
	 */
	public static function has_post_thumbnail($post_type, $id, $post_id = null) {
		if (null === $post_id) {
			$post_id = get_the_ID();
		}

		if (!$post_id) {
			return false;
		}

		return get_post_meta($post_id, "{$post_type}_{$id}_thumbnail_id", true);
	}

	/**
	 * Display Post Thumbnail.
	 *
	 * @param string $post_type The post type.
	 * @param string $thumb_id The id used to register the thumbnail.
	 * @param string $post_id Optional. Post ID.
	 * @param int $size Optional. Image size.  Defaults to 'post-thumbnail', which theme sets using set_post_thumbnail_size( $width, $height, $crop_flag );.
	 * @param string|array $attr Optional. Query string or array of attributes.
	 * @param bool $link_to_original Optional. Wrap link to original image around thumbnail?
	 */
	public static function the_post_thumbnail($post_type, $thumb_id, $post_id = null, $size = 'post-thumbnail', $attr = '', $link_to_original = false) {
		echo self::get_the_post_thumbnail($post_type, $thumb_id, $post_id, $size, $attr, $link_to_original);
	}

	/**
	 * Retrieve Post Thumbnail.
	 *
	 * @param string $post_type The post type.
	 * @param string $thumb_id The id used to register the thumbnail.
	 * @param int $post_id Optional. Post ID.
	 * @param string $size Optional. Image size.  Defaults to 'thumbnail'.
	 * @param bool $link_to_original Optional. Wrap link to original image around thumbnail?
	 * @param string|array $attr Optional. Query string or array of attributes.
	  */
	public static function get_the_post_thumbnail($post_type, $thumb_id, $post_id = NULL, $size = 'post-thumbnail', $attr = '' , $link_to_original = false) {
		global $id;
		$post_id = (NULL === $post_id) ? get_the_ID() : $post_id;
		$post_thumbnail_id = self::get_post_thumbnail_id($post_type, $thumb_id, $post_id);
		$size = apply_filters("{$post_type}_{$post_id}_thumbnail_size", $size);
		if ($post_thumbnail_id) {
			do_action("begin_fetch_multi_{$post_type}_thumbnail_html", $post_id, $post_thumbnail_id, $size); // for "Just In Time" filtering of all of wp_get_attachment_image()'s filters
			$html = wp_get_attachment_image( $post_thumbnail_id, $size, false, $attr );
			do_action("end_fetch_multi_{$post_type}_thumbnail_html", $post_id, $post_thumbnail_id, $size);
		} else {
			$html = '';
		}

		if ($link_to_original && $html) {
			$html = sprintf('<a href="%s">%s</a>', wp_get_attachment_url($post_thumbnail_id), $html);
		}

		return apply_filters("{$post_type}_{$thumb_id}_thumbnail_html", $html, $post_id, $post_thumbnail_id, $size, $attr);
	}

	/**
	 * Retrieve Post Thumbnail ID.
	 *
	 * @param string $post_type The post type.
	 * @param string $id The id used to register the thumbnail.
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function get_post_thumbnail_id($post_type, $id, $post_id) {
		return get_post_meta($post_id, "{$post_type}_{$id}_thumbnail_id", true);
	}

	/**
	 *
	 * @param string $post_type The post type.
	 * @param string $id The id used to register the thumbnail.
	 * @param int $post_id Optional. The post ID. If not set, will attempt to get it.
	 * @return mixed Thumbnail url or false if the post doesn't have a thumbnail for the given post type, and id.
	 */
	public static function get_post_thumbnail_url($post_type, $id, $post_id = 0) {
		if (!$post_id) {
			$post_id = get_the_ID();
		}

		$post_thumbnail_id = self::get_post_thumbnail_id($post_type, $id, $post_id);

		return wp_get_attachment_url($post_thumbnail_id);
	}

	/**
	 * Output the post thumbnail HTML for the metabox and AJAX callbacks
	 *
	 * @param string $thumbnail_id The thumbnail's post ID.
	 * @return string HTML
	 */
	private function post_thumbnail_html($thumbnail_id = null) {
		global $content_width, $_wp_additional_image_sizes, $post_ID;

		$set_thumbnail_link = sprintf('<p class="hide-if-no-js"><a title="%1$s" href="%2$s" id="set-%3$s-%4$s-thumbnail" class="thickbox">%%s</a></p>', esc_attr__( "Set {$this->label}" ), get_upload_iframe_src('image'), $this->post_type, $this->id);
		$content = sprintf($set_thumbnail_link, esc_html__( "Set {$this->label}" ));


		if ($thumbnail_id && get_post($thumbnail_id)) {
			$old_content_width = $content_width;
			$content_width = 266;
			if ( !isset($_wp_additional_image_sizes["{$this->post_type}-{$this->id}-thumbnail"]))
				$thumbnail_html = wp_get_attachment_image($thumbnail_id, array($content_width, $content_width));
			else
				$thumbnail_html = wp_get_attachment_image($thumbnail_id, "{$this->post_type}-{$this->id}-thumbnail");
			if (!empty($thumbnail_html)) {
				$ajax_nonce = wp_create_nonce("set_post_thumbnail-{$this->post_type}-{$this->id}-{$post_ID}");
				$content = sprintf($set_thumbnail_link, $thumbnail_html);
				$content .= sprintf('<p class="hide-if-no-js"><a href="#" id="remove-%1$s-%2$s-thumbnail" onclick="MultiPostThumbnailsRemoveThumbnail(\'%2$s\', \'%1$s\', \'%4$s\');return false;">%3$s</a></p>', $this->post_type, $this->id, esc_html__( "Remove {$this->label}" ), $ajax_nonce);
			}
			$content_width = $old_content_width;
		}

		return $content;
	}

	/**
	 * Set/remove the post thumbnail. AJAX handler.
	 *
	 * @return string Updated post thumbnail HTML.
	 */
	public function set_thumbnail() {
		global $post_ID; // have to do this so get_upload_iframe_src() can grab it
		$post_ID = intval($_POST['post_id']);
		if ( !current_user_can('edit_post', $post_ID))
			die('-1');
		$thumbnail_id = intval($_POST['thumbnail_id']);

		check_ajax_referer("set_post_thumbnail-{$this->post_type}-{$this->id}-{$post_ID}");

		if ($thumbnail_id == '-1') {
			delete_post_meta($post_ID, "{$this->post_type}_{$this->id}_thumbnail_id");
			die($this->post_thumbnail_html(null));
		}

		if ($thumbnail_id && get_post($thumbnail_id)) {
			$thumbnail_html = wp_get_attachment_image($thumbnail_id, 'thumbnail');
			if (!empty($thumbnail_html)) {
				update_post_meta($post_ID, "{$this->post_type}_{$this->id}_thumbnail_id", $thumbnail_id);
				die($this->post_thumbnail_html($thumbnail_id));
			}
		}

		die('0');
	}

}
endif;
