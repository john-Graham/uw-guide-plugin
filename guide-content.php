<?php
/**
 * Plugin Name:       CoE Guides
 * Description:       Wordpress plugin for CPT guide and api/xml call to guide.wisc.edu.
 * Requires at least: 6.3
 * Requires PHP:      8.1
 * Version:           0.11
 * Author:            John Graham
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       coe-guides
 *
 * @package           coe-guides
 */


 
// Define our handy constants.
define( 'COE_GUIDES_VERSION', '0.1' );
define( 'COE_GUIDES_PLUGIN_DIR', __DIR__ );
define( 'COE_GUIDES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'COE_GUIDES_PLUGIN_BLOCKS', COE_GUIDES_PLUGIN_DIR . '/blocks/' );

// Set custom load & save JSON points for ACF sync.
require 'includes/acf-json.php';
// Register blocks and other handy ACF Block helpers.
require 'includes/acf-blocks.php';
// Register a default "Site Settings" Options Page.
require 'includes/acf-settings-page.php';
// Restrict access to ACF Admin screens.
require 'includes/acf-restrict-access.php';
// Display and template helpers.
require 'includes/template-tags.php';




// functions used in admin and public
require_once plugin_dir_path( __FILE__) . 'includes/core-functions.php';


// default plugin options
function guide_content_options_default() {

	return array(
		'custom_title'   => 'default variables if needed',

	);

}


