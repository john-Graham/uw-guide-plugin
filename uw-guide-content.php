<?php

/**
 * Plugin Name:       UW Guide Content
 * Description:       Wordpress plugin for CPT guide and api/xml call to guide.wisc.edu.
 * Requires at least: 6.3
 * Requires PHP:      8.1
 * Version:           0.12
 * Author:            John Graham
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       uw-guide-content
 *
 * @package           uw-guide-content
 */



// Define our handy constants.
define('UW_GUIDE_CONTENT_VERSION', '0.1');
define('UW_GUIDE_CONTENT_PLUGIN_DIR', __DIR__);
define('UW_GUIDE_CONTENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UW_GUIDE_CONTENT_PLUGIN_BLOCKS', UW_GUIDE_CONTENT_PLUGIN_DIR . '/blocks/');

// define('WP_POST_REVISIONS', 3);

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
require_once plugin_dir_path(__FILE__) . 'includes/core-functions.php';

// functions used for cleaning up the content
require_once plugin_dir_path(__FILE__) . 'includes/node-functions.php';


// default plugin options
// function guide_content_options_default()
// {

// 	return array(
// 		'custom_title'   => 'default variables if needed',

// 	);
// }

// setting a default value for shortcode_id when blocks are created
function generate_alphanumeric_id($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

add_filter('acf/load_value/name=shortcode_id', 'set_default_shortcode_id', 10, 3);

function set_default_shortcode_id($value, $post_id, $field) {
    if (empty($value)) {
        $value = generate_alphanumeric_id();
    }
    return $value;
}

// creating a hash to check if fields have changed
function generate_fields_hash($fields) {
    return md5(serialize($fields));
}