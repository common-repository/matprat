<?php
/*
Plugin Name: Matprat
Plugin URI: http://blogg.matprat.no/
Description: Matprat
Version: 1.0.2
Author: Ryan Hellyer / Metronet
Author URI: http://metronet.no/

------------------------------------------------------------------------
Copyright Ryan Hellyer

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*/



/**
 * Do not continue processing since file was called directly
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
if ( !defined( 'ABSPATH' ) )
	die( 'Eh! What you doin in here?' );

/**
 * Definitions
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
define( 'MATPRAT_DIR', dirname( __FILE__ ) . '/' ); // Plugin folder DIR
define( 'MATPRAT_URL', plugins_url( '', __FILE__ ) ); // Plugin folder URL

/**
 * Load required files
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
require( 'inc/class-matprat-add-meta-boxes.php' );
require( 'inc/class-matprat-modify-feed.php' );
require( 'inc/class-multi-post-thumbnails.php' );

/**
 * Instantiate classes
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
new MultiPostThumbnails (
	array(
		'label'     => __( 'Matprat image', 'matprat' ),
		'id'        => 'matprat',
		'post_type' => 'post'
	)
);
new Matprat_Modify_feed();
new Matprat_Add_Meta_Boxes();
