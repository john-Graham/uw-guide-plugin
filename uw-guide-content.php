<?php

/**
 * Plugin Name:       UW Guide Content
 * Description:       Wordpress plugin for CPT guide and api/xml call to guide.wisc.edu.
 * Requires at least: 6.3
 * Requires PHP:      8.1
 * Version:           0.13
 * Author:            John Graham
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       uw-guide-content
 *
 * @package           uw-guide-content
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define our handy constants.
define('UW_GUIDE_CONTENT_VERSION', '0.13');
define('UW_GUIDE_CONTENT_PLUGIN_DIR', __DIR__);
define('UW_GUIDE_CONTENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UW_GUIDE_CONTENT_PLUGIN_BLOCKS', UW_GUIDE_CONTENT_PLUGIN_DIR . '/blocks/');
define('UW_GUIDE_CONTENT_INCLUDES', UW_GUIDE_CONTENT_PLUGIN_DIR . '/includes/');

// Autoload classes
spl_autoload_register(function ($class) {
    // Only handle our own classes
    if (strpos($class, 'UWGuide_') !== 0) {
        return;
    }
    
    $class_name = str_replace('UWGuide_', '', $class);
    $class_name = str_replace('_', '-', strtolower($class_name));
    $class_path = UW_GUIDE_CONTENT_INCLUDES . 'class-' . $class_name . '.php';
    
    if (file_exists($class_path)) {
        require_once $class_path;
    }
});

// Set custom load & save JSON points for ACF sync.
require UW_GUIDE_CONTENT_INCLUDES . 'acf-json.php';
// Register blocks and other handy ACF Block helpers.
require UW_GUIDE_CONTENT_INCLUDES . 'acf-blocks.php';
// Register a default "Site Settings" Options Page.
require UW_GUIDE_CONTENT_INCLUDES . 'acf-settings-page.php';
// Restrict access to ACF Admin screens.
require UW_GUIDE_CONTENT_INCLUDES . 'acf-restrict-access.php';
// Display and template helpers.
require UW_GUIDE_CONTENT_INCLUDES . 'template-tags.php';

// Core functions
require UW_GUIDE_CONTENT_INCLUDES . 'core-functions.php';

// Admin UI functionality
if (is_admin()) {
    require UW_GUIDE_CONTENT_INCLUDES . 'class-admin-ui.php';
}

// Add caching and logging capabilities
require UW_GUIDE_CONTENT_INCLUDES . 'class-cache.php';
require UW_GUIDE_CONTENT_INCLUDES . 'class-logger.php';

// setting a default value for shortcode_id when blocks are created
function generate_alphanumeric_id($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $length > $i; $i++) {
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

// Improved hash generation function that includes proper serialization of arrays
function generate_fields_hash($fields) {
    // Ensure arrays are properly sorted for consistent hashing
    if (isset($fields['find_replace']) && is_array($fields['find_replace'])) {
        usort($fields['find_replace'], function($a, $b) {
            return strcmp($a['find'] . $a['replace'], $b['find'] . $b['replace']);
        });
    }
    
    if (isset($fields['adjust_tags']) && is_array($fields['adjust_tags'])) {
        usort($fields['adjust_tags'], function($a, $b) {
            return strcmp($a['first_tag'] . $a['second_tag'], $b['first_tag'] . $b['second_tag']);
        });
    }
    
    if (isset($fields['unwrap_tags']) && is_array($fields['unwrap_tags'])) {
        sort($fields['unwrap_tags']);
    }
    
    if (isset($fields['h_select']) && is_array($fields['h_select'])) {
        ksort($fields['h_select']);
    }
    
    // Create a consistent string representation
    return md5(json_encode($fields));
}