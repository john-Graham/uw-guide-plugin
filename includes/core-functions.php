<?php //guide-content - Settings callbacks

if (!defined('ABSPATH')) exit;

// Starting the process of a guide entry
function uwguide_entry($url, $section, $find_replace, $adjust_tags, $graduate_section, $post_id, $unwrap_tags, $h_select = [], $block_id)
{
    $current_post_id = $post_id;
    // error_log('uwguide_entry called');
    $content = '';
    $cpt_post_id = null;

    // Collect current field values
    $current_fields = compact('url', 'section', 'find_replace', 'adjust_tags', 'unwrap_tags', 'h_select');

    // Generate current hash
    $current_hash = generate_fields_hash($current_fields);

    // Retrieve previous hash
    $previous_hash = get_post_meta($current_post_id, '_fields_hash', true);

    // Log initial states
    // error_log('Initial block_id: ' . $block_id);
    // error_log('Previous hash: ' . $previous_hash);
    // error_log('Current hash: ' . $current_hash);

    // Update previous hash if it has changed
    $update_needed = ($current_hash !== $previous_hash);

    if ($update_needed) {
        // Save the current hash as previous hash
        update_post_meta($current_post_id, '_fields_hash', $current_hash);
    }

    // Check if the CPT with this block ID already exists
    $cpt_check = uwguide_check_if_cpt_exists($block_id);

    if ($cpt_check['exists']) {
        // error_log('CPT exists with post ID: ' . $cpt_check['post_id']);
        $cpt_post_id = $cpt_check['post_id'];

        // Determine if we are in the admin area
        $is_admin = is_admin();

        // Check if an update is required
        if ($update_needed || (!$is_admin && $cpt_check['update_required'])) {
            // error_log('Updating content due to field changes or frequency check');

            // Get the modified date of the XML URL
            $xml_modified_date = uwguide_get_url_modified_date($url);
            // error_log('XML modified date: ' . $xml_modified_date);

            // Get the stored modified date from the CPT
            $stored_modified_date = get_post_meta($cpt_post_id, 'last_modified', true);
            // error_log('Stored modified date: ' . $stored_modified_date);

            // If in admin and update_needed, force update
            if ($is_admin && $update_needed) {
                $xml_modified_date = null; // Force content fetch in admin if hash changes
            }

            // Compare the dates
            if ($xml_modified_date !== $stored_modified_date || $is_admin) {
                // Fetch and clean the content from the XML
                $content = uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags, $h_select);

                // Update the existing CPT content
                wp_update_post(array(
                    'ID'           => $cpt_post_id,
                    'post_content' => $content,
                ));
                update_post_meta($cpt_post_id, 'url', $url);
                update_post_meta($cpt_post_id, 'section', $section);
                update_post_meta($cpt_post_id, 'last_modified', $xml_modified_date);
            } else {
                error_log('No update needed as the XML modified date has not changed.');
            }
        }
    } else {
        error_log('CPT does not exist, creating a new one');

        // Fetch and clean the content from the XML
        $content = uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags, $h_select);

        // Create the CPT
        $cpt_post_id = uwguide_create_cpt($url, $section, current_time('mysql'), $content, $post_id, $block_id);
        // error_log('Created CPT post ID: ' . $cpt_post_id);

        // Update the CPT with the block_id as the shortcode_id
        $xml_modified_date = uwguide_get_url_modified_date($url); // Ensure last modified date is set
        update_post_meta($cpt_post_id, 'shortcode_id', $block_id);
        update_post_meta($cpt_post_id, 'last_modified', $xml_modified_date);
        // error_log('Updated post meta with block_id: ' . $block_id . ' and last_modified: ' . $xml_modified_date);

    }

    wp_reset_postdata();

    // Render the CPT content
    $cpt_query = new WP_Query(array(
        'post_type' => 'uw-guide',
        'meta_key' => 'shortcode_id',
        'meta_value' => $block_id
    ));

    if ($cpt_query->have_posts()) {
        $cpt_query->the_post();
        $content = get_the_content();
        $content_found = true;

        // Fetch the last modified date for the comment
        $last_modified_date = get_post_meta($cpt_post_id, 'last_modified', true);
        // Show the content
        echo '<!-- START Content copied from ' . $url . '#' . $section . ' | Last updated: ' . $last_modified_date . ' -->';
        echo $content;
        echo '<!-- END Content copied from ' . $url . '#' . $section . ' | Last updated: ' . $last_modified_date . ' -->';

        wp_reset_postdata();
    } else {
        echo '<p>No content found</p>';
    }

    // Comment out the second echo statement to prevent content duplication
    // echo $content;
}


function uwguide_check_if_cpt_exists($shortcode_id)
{
    error_log('uwguide_check_if_cpt_exists called');

    // Ensure shortcode_id is not empty
    if (empty($shortcode_id)) {
        error_log('shortcode_id is required but missing');
        return array(
            'exists' => false,
            'update_required' => false
        );
    }

    // Query to check if the CPT exists based on shortcode_id
    $args = array(
        'post_type'      => 'uw-guide',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'   => 'shortcode_id',
                'value' => $shortcode_id,
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $post_id = $query->posts[0];

        if (uwguide_check_frequency($post_id)) {
            return array(
                'exists' => true,
                'update_required' => true,
                'post_id' => $post_id
            );
        } else {
            return array(
                'exists' => true,
                'update_required' => false,
                'post_id' => $post_id
            );
        }
    } else {
        error_log('No guide found with shortcode_id: ' . $shortcode_id);
        return array(
            'exists' => false,
            'update_required' => false
        );
    }
}

function uwguide_check_frequency($post_id)
{
    // Get the date from the ACF field
    error_log('uwguide_check_frequency called for post ID: ' . $post_id);
    $cpt_modified = get_post_meta($post_id, 'last_modified', true);
    error_log('Last Modified from uwguide_check_frequency: ' . $cpt_modified);

    if ($cpt_modified) {
        // Try to parse the date using various formats
        $date_formats = ['Ymd', 'Y-m-d H:i:s', 'd/m/Y'];

        $cpt_modified_date = false;
        foreach ($date_formats as $format) {
            $cpt_modified_date = DateTime::createFromFormat($format, $cpt_modified);
            if ($cpt_modified_date !== false) {
                break;
            }
        }

        if ($cpt_modified_date === false) {
            error_log('Invalid date format for post ID: ' . $post_id . '. Date string: ' . $cpt_modified);
            return false;
        }
        $cpt_modified = $cpt_modified_date->format('Y-m-d');
    } else {
        error_log('No last modified date found for post ID: ' . $post_id);
        return false; // Handle the absence of a date as needed
    }

    error_log('Formatted Last Modified: ' . $cpt_modified);

    // Get the frequency of the post
    $frequency = get_field('uw_guide_update_frequency', 'options');

    // Convert last modified date to DateTime object
    $last_modified_date = new DateTime($cpt_modified);

    // Get the current date
    $current_date = new DateTime();

    // Clone the current date object to keep the original date intact
    $earliest_allowed_date = clone $current_date;

    // Determine the date range based on frequency
    switch ($frequency) {
        case 'everytime':
            error_log('Update frequency: everytime');
            return true; // Always continue
        case 'daily':
            $interval = new DateInterval('P1D');
            error_log('Update frequency: daily');
            break;
        case 'weekly':
            $interval = new DateInterval('P1W');
            error_log('Update frequency: weekly');
            break;
        case 'monthly':
            $interval = new DateInterval('P1M');
            error_log('Update frequency: monthly');
            break;
        default:
            error_log('Update frequency: default');
            return false; // Invalid frequency
    }

    // Calculate the earliest allowed date based on frequency
    $earliest_allowed_date->sub($interval);

    // Compare the last modified date with the earliest allowed date
    if ($last_modified_date < $earliest_allowed_date) {
        return true; // Continue if the post was last modified before the earliest allowed date
    } else {
        return false; // Do not continue if the post was modified after the earliest allowed date
    }
}

function uwguide_create_cpt($url, $section, $guide_modified, $content, $current_post_id, $shortcode_id)
{
    error_log('uwguide_create_cpt called');
    error_log('Current Post ID: ' . $current_post_id);
    error_log('Shortcode ID: ' . $shortcode_id);

    // Create the CPT with the generated UUID included in meta_input
    $post_id = wp_insert_post(array(
        'post_title'    => $shortcode_id, // Concatenate URL and section for title
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'uw-guide',
        'meta_input'    => array(
            'url'           => $url,
            'section'       => $section,
            'last_modified' => $guide_modified,
            'shortcode_id'  => $shortcode_id, // Include the UUID in meta_input
            'id_of_post'    => $current_post_id, // Include the ID of the post that created this CPT
        ),
    ));

    if ($post_id) {
        error_log("New guide created with ID: " . $post_id . " and shortcode_id: " . $shortcode_id);

        return $post_id;
    } else {
        error_log("Failed to create a new guide.");
        return false;
    }
}

function uwguide_update_cpt($block_id, $guide_modified, $content, $shortcode_id)
{
    error_log('uwguide_update_cpt called with shortcode_id: ' . $shortcode_id);

    // Query to find the CPT based on shortcode_id
    $args = array(
        'post_type'      => 'uw-guide',  // Adjust if your CPT has a different slug
        'posts_per_page' => 1,
        'fields'         => 'ids',  // Retrieve only the IDs for efficiency
        's'              => $shortcode_id,  // Search by shortcode_id
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $post_id = $query->posts[0];  // Assuming there's only one post with this block ID

        // Prepare post data
        $post_data = array(
            'ID'           => $post_id,
            'post_content' => $content, // Update the content
            // Optionally, update other post fields as needed
        );

        // Update the post
        $updated_post_id = wp_update_post($post_data, true);

        if (is_wp_error($updated_post_id)) {
            error_log('Failed to update guide: ' . $updated_post_id->get_error_message());
            return false;
        }

        // Update the ACF field 'last_modified'
        update_field('last_modified', $guide_modified, $post_id);

        error_log('Guide updated with ID: ' . $post_id . ' and shortcode_id: ' . $shortcode_id);
        return $post_id;
    } else {
        error_log('No guide found with shortcode_id: ' . $shortcode_id);
        return false; // No post found
    }
}

// remove all uw guide posts if clear cache is set to yes
add_action('acf/save_post', 'uw_guide_save_options_page', 20);
function uw_guide_save_options_page($post_id)
{
    // Check if it's the options page
    if ($post_id != 'options') {
        return;
    }
    echo error_log('uw_guide_save_options_page called');

    // Check if 'clear_cache' field is set to 'yes'
    $clear_cache = get_field('uw_guide_clear_cache', 'option');
    if ($clear_cache === 'yes') {
        // Query all posts of type 'uw-guide'
        $args = array(
            'post_type'      => 'uw-guide',
            'posts_per_page' => -1,
            'fields'         => 'ids', // Only get post IDs to improve performance
        );

        $posts = get_posts($args);

        // Loop through the posts and delete them
        foreach ($posts as $post_id) {
            wp_delete_post($post_id, true); // Set to true to bypass trash
        }

        // Optionally, reset the 'clear_cache' field to 'no' or empty
        update_field('uw_guide_clear_cache', 'no', 'option');
    }
}

// Function to handle the shortcode
function uw_guide_shortcode($atts)
{
    error_log('uw_guide_shortcode called');
    // Default attributes
    $atts = shortcode_atts(
        array(
            'id' => '', // Default id is an empty string
        ),
        $atts,
        'uw-guide'
    );

    // Get the id from the shortcode attributes
    $shortcode_id = $atts['id'];

    // Check if id is provided
    if (empty($shortcode_id)) {
        return 'No id provided.';
    }

    // Args for WP_Query
    $args = array(
        'post_type'      => 'uw-guide',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'   => 'shortcode_id',
                'value' => $shortcode_id,
                'compare' => '='
            )
        )
    );

    // The Query
    $query = new WP_Query($args);

    // Initialize output variable
    $output = '';

    // Check if any post was found
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            // Customize your output as needed
            $output = get_the_content(); // For example, returning the content of the post
        }
        wp_reset_postdata(); // Reset Post Data after the loop
    } else {
        $output = 'No posts found with that id.';
    }

    return $output;
}

// Register the shortcode with WordPress
add_shortcode('uw-guide', 'uw_guide_shortcode');
