<?php

/**
 * ACF Set custom load and save JSON points.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/
 */

add_filter('acf/json/load_paths', 'coe_guides_json_load_paths');


// setting where to save the json files
//How to set custom save points for all ACF field group JSON files
// add_filter( 'acf/settings/save_json/type=acf-field-group', 'coe_guides_json_save_path_for_field_groups' );

// How to set custom save points for all ACF Options Page JSON files
// add_filter( 'acf/settings/save_json/type=acf-ui-options-page', 'coe_guides_json_save_path_for_option_pages' );

// How to set custom save points for all ACF Post type JSON files
// add_filter( 'acf/settings/save_json/type=acf-post-type', 'coe_guides_json_save_path_for_post_types' );

// How to set custom save points for all ACF Taxonomy JSON files
// add_filter( 'acf/settings/save_json/type=acf-taxonomy', 'coe_guides_json_save_path_for_taxonomies' );



// Field Group - Block guide - group_656f812a0cd98
add_filter('acf/settings/save_json/key=group_656f812a0cd98', 'coe_guides_json_save_path_for_field_groups');

// Field Group - Options info group_65709c094a336 (UW guide settings)
add_filter('acf/settings/save_json/key=group_65709c094a336', 'coe_guides_json_save_path_for_field_groups');

// Field Group - Meta guide - group_6572128615b58
add_filter('acf/settings/save_json/key=group_6572128615b58', 'coe_guides_json_save_path_for_field_groups');


// Options pages - UW guide settings - ui_options_page_656f7ffd8476f 
add_filter('acf/settings/save_json/key=ui_options_page_656f7ffd8476f', 'coe_guides_json_save_path_for_option_pages');

// Post Type - UW Guide - post_type_656f9893ed527
add_filter('acf/settings/save_json/key=post_type_656f9893ed527', 'coe_guides_json_save_path_for_post_types');



add_filter('acf/json/save_file_name', 'coe_guides_json_filename', 10, 3);

/**
 * Set a custom ACF JSON load path.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/#loading-explained
 *
 * @param array $paths Existing, incoming paths.
 *
 * @return array $paths New, outgoing paths.
 *
 * @since 0.1.1
 */
function coe_guides_json_load_paths($paths)
{
	$paths[] = COE_GUIDES_PLUGIN_DIR . '/acf-json/field-groups';
	$paths[] = COE_GUIDES_PLUGIN_DIR . '/acf-json/options-pages';
	$paths[] = COE_GUIDES_PLUGIN_DIR . '/acf-json/post-types';
	$paths[] = COE_GUIDES_PLUGIN_DIR . '/acf-json/taxonomies';
	// error_log( print_r( $paths, true ) );

	return $paths;
}

/**
 * Set custom ACF JSON save point for
 * ACF generated post types.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/#saving-explained
 *
 * @return string $path New, outgoing path.
 *
 * @since 0.1.1
 */
function coe_guides_json_save_path_for_post_types()
{
	return COE_GUIDES_PLUGIN_DIR . '/acf-json/post-types';
}

/**
 * Set custom ACF JSON save point for
 * ACF generated field groups.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/#saving-explained
 *
 * @return string $path New, outgoing path.
 *
 * @since 0.1.1
 */
function coe_guides_json_save_path_for_field_groups()
{
	return COE_GUIDES_PLUGIN_DIR . '/acf-json/field-groups';
}

/**
 * Set custom ACF JSON save point for
 * ACF generated taxonomies.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/#saving-explained
 *
 * @return string $path New, outgoing path.
 *
 * @since 0.1.1
 */
function coe_guides_json_save_path_for_taxonomies()
{
	return COE_GUIDES_PLUGIN_DIR . '/acf-json/taxonomies';
}

/**
 * Set custom ACF JSON save point for
 * ACF generated Options Pages.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/#saving-explained
 *
 * @return string $path New, outgoing path.
 *
 * @since 0.1.1
 */
function coe_guides_json_save_path_for_option_pages()
{
	return COE_GUIDES_PLUGIN_DIR . '/acf-json/options-pages';
}

/**
 * Customize the file names for each file.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/#saving-explained
 *
 * @param string $filename  The default filename.
 * @param array  $post      The main post array for the item being saved.
 *
 * @return string $filename
 *
 * @since  0.1.1
 */
function coe_guides_json_filename($filename, $post)
{
	$filename = str_replace(
		array(
			' ',
			'_',
		),
		array(
			'-',
			'-',
		),
		$post['title']
	);

	$filename = strtolower($filename) . '.json';

	return $filename;
}
