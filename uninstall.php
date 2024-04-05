<?php


// exit if uninstall constant is not defined
if (!defined('WP_UNINSTALL_PLUGIN')) {

	exit;
}

$delete_data = get_option('uw_guide_delete_content_from_database_on_uninstall');

if ('yes' === $delete_data) {
	// Query to get all posts of uw-guide CPT
	$args = array(
		'post_type'      => 'uw-guide',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$posts = get_posts($args);

	// Loop through and delete each post
	foreach ($posts as $post_id) {
		wp_delete_post($post_id, true); // Set to true to force delete
	}

	// Optionally, delete the option itself and any other data you wish to clean up
	delete_option('uw_guide_delete_content_from_database_on_uninstall');

	// Need to add code to delete the acf field groups and fields
	

}
