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
define('COE_GUIDES_VERSION', '0.1');
define('COE_GUIDES_PLUGIN_DIR', __DIR__);
define('COE_GUIDES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COE_GUIDES_PLUGIN_BLOCKS', COE_GUIDES_PLUGIN_DIR . '/blocks/');

define('WP_POST_REVISIONS', 3);


// echo error_log('guide-content.php file loaded');
// echo 'guide-content.php file loaded';
// add_action('acf/save_post', 'check_and_create_shortcode_id', 20);

// function set_default_shortcode_id($value, $post_id, $field)
// {
// 	// Log for debugging
// 	error_log('set_default_shortcode_id called for post/block ID: ' . $post_id);

// 	// Static variable to prevent multiple executions within the same request
// 	static $executed = false;

// 	// Check if the function has already been executed in this request
// 	if ($executed) {
// 		return $value;
// 	}

// 	// Ensure this is not an autosave
// 	if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
// 		return $value;
// 	}

// 	// Get the post content and parse blocks
// 	$post = get_post($post_id);
// 	if (!$post) {
// 		return $value;
// 	}

// 	$blocks = parse_blocks($post->post_content);

// 	foreach ($blocks as $block) {
// 		if ('acf/guide' === $block['blockName']) {
// 			// Check if the block's shortcode_id is empty
// 			if (empty($value)) {
// 				// Mark the function as executed
// 				$executed = true;

// 				// Generate a UUID
// 				$guid = wp_generate_uuid4();

// 				// Create a new CPT entry
// 				$new_cpt = array(
// 					'post_title'    => $guid,
// 					'post_content'  => '',
// 					'post_status'   => 'publish',
// 					'post_type'     => 'uw-guide',
// 					'meta_input'    => array(
// 						'shortcode_id' => $guid,
// 					),
// 				);
// 				$new_cpt_id = wp_insert_post($new_cpt);

// 				// Return the new UUID instead of the CPT ID
// 				return $guid;
// 			}
// 		}
// 	}

// 	return $value;
// }
// add_filter('acf/load_value', 'set_default_shortcode_id', 10, 3);

// add_filter('acf/load_value/name=shortcode_id', 'my_acf_load_unique_id', 10, 3);
// function my_acf_load_unique_id($value, $post_id, $field) {
//     // If the unique ID is empty, generate a new one
//     if (empty($value)) {
//         $value = wp_generate_uuid4(); // Generate a unique UUID
//     }
//     return $value;
// }




// function set_unique_id_and_update_blocks($post_id)
// {
// 	// Ensure this is not an autosave
// 	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
// 		return;
// 	}

// 	// Ensure this is not a revision
// 	if (wp_is_post_revision($post_id)) {
// 		return;
// 	}

// 	// Get the post content
// 	$post_content = get_post_field('post_content', $post_id);
// 	$blocks = parse_blocks($post_content);
// 	$updated_content = '';

// 	// Flag to track if content was updated
// 	$content_updated = false;

// 	foreach ($blocks as &$block) {
// 		if ($block['blockName'] === 'acf/guide') { // Replace with your block name
// 			// Check if the shortcode_id attribute exists in block attributes
// 			if (!isset($block['attrs']['data']['shortcode_id']) || empty($block['attrs']['data']['shortcode_id'])) {
// 				// Generate and set a unique ID
// 				$shortcode_id = wp_generate_uuid4();

// 				// Add the unique ID to block attributes
// 				$block['attrs']['data']['shortcode_id'] = $shortcode_id;

// 				// Update the ACF field directly
// 				update_field('shortcode_id', $shortcode_id, $post_id);

// 				$content_updated = true;
// 			}

// 			// Ensure your existing CPT check function is called
// 			$shortcode_id = $block['attrs']['data']['shortcode_id'];
// 			$result = uwguide_check_if_cpt_exists($shortcode_id);


// 		}
// 		// Serialize the updated block content
// 		$updated_content .= serialize_block($block);
// 	}

// 	// If the content was updated, save the new post content
// 	if ($content_updated) {
// 		wp_update_post(array(
// 			'ID' => $post_id,
// 			'post_content' => $updated_content,
// 		));
// 	}
// }

// // Hook into save_post for all post types
// add_action('save_post', 'set_unique_id_and_update_blocks', 20);







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

// function check_and_create_shortcode_id($post_id) {
//     // Ensure we're not running on options or other non-post saves
//     if ($post_id == 'options') {
//         return;
//     }

//     // Log for debugging
//     error_log('check_and_create_shortcode_id called for post ID: ' . $post_id);

//     // Get the post content
//     $post_content = get_post_field('post_content', $post_id);

//     // Parse the blocks in the post content
//     $blocks = parse_blocks($post_content);

//     foreach ($blocks as $block) {
//         // Ensure we only process the ACF guide block
//         if ($block['blockName'] === 'acf/guide') {
//             error_log('acf/guide block found');

//             // Get the block's ID
//             $block_id = $block['attrs']['id'];
//             error_log('Block ID: ' . $block_id);

//             // Check if the shortcode_id field is empty
//             $shortcode_id = get_field('shortcode_id', $block_id);
//             error_log('Current shortcode_id: ' . $shortcode_id);

//             if (empty($shortcode_id)) {
//                 error_log('shortcode_id is empty, generating new one');

//                 // Generate a new shortcode_id
//                 $shortcode_id = wp_generate_uuid4();

//                 // Update the block's shortcode_id field
//                 update_field('shortcode_id', $shortcode_id, $block_id);
//                 error_log('shortcode_id updated in block: ' . $shortcode_id);

//                 // Create a new CPT entry
//                 $url = get_field('url', $block_id);
//                 $section = get_field('section', $block_id);
//                 $last_modified = get_field('last_modified', $block_id);
//                 $content = ''; // Fetch content as needed

//                 // uwguide_create_cpt($url, $section, $last_modified, $content, $post_id, $shortcode_id);
//                 error_log('CPT created for shortcode_id: ' . $shortcode_id);
//             }
//         }
//     }
// }





// functions used in admin and public
require_once plugin_dir_path(__FILE__) . 'includes/core-functions.php';


// default plugin options
function guide_content_options_default()
{

	return array(
		'custom_title'   => 'default variables if needed',

	);
}
