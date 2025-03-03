<?php
// Most functionality has been moved to class files

if (!defined('ABSPATH')) exit;

// Starting the process of a guide entry
function uwguide_entry($url, $section, $find_replace, $adjust_tags, $post_id, $unwrap_tags, $block_id, $h_select = [])
{
    $current_post_id = $post_id;
    $cpt_post_id = null;
    
    // Collect current field values for hash generation
    $current_fields = compact('url', 'section', 'find_replace', 'adjust_tags', 'unwrap_tags', 'h_select');
    
    // Generate current hash
    $current_hash = generate_fields_hash($current_fields);
    
    // Retrieve previous hash
    $previous_hash = get_post_meta($current_post_id, '_fields_hash_' . $block_id, true);
    
    // Update previous hash if it has changed
    $update_needed = ($current_hash !== $previous_hash);
    
    // In admin, we'll always check for updates
    $is_admin = is_admin();
    $force_update = $is_admin && $update_needed;
    
    if ($update_needed) {
        // Save the current hash with block-specific key
        update_post_meta($current_post_id, '_fields_hash_' . $block_id, $current_hash);
    }
    
    // Check if the CPT with this block ID already exists
    $cpt_check = UWGuide_CPT_Manager::exists($block_id);
    
    if ($cpt_check['exists']) {
        $cpt_post_id = $cpt_check['post_id'];
        
        // Check if an update is required
        if ($force_update || (!$is_admin && $cpt_check['update_required'])) {
            // Get the modified date of the XML URL
            $xml_modified_date = UWGuide_Content_Fetcher::get_modified_date($url);
            
            // Get the stored modified date from the CPT
            $stored_modified_date = get_post_meta($cpt_post_id, 'last_modified', true);
            
            // In admin and update needed, always update
            if ($force_update) {
                UWGuide_Logger::log(
                    "Block options changed, forcing content update for block ID: {$block_id}",
                    UWGuide_Logger::INFO
                );
                
                // Using the cache system to get content
                $options = [
                    'find_replace' => $find_replace,
                    'adjust_tags' => $adjust_tags,
                    'unwrap_tags' => $unwrap_tags,
                    'h_select' => $h_select
                ];
                
                // For forced updates, bypass cache by adding unique timestamp
                $options['_bypass_cache'] = time();
                
                $content = UWGuide_Cache::get_content($url, $section, $options, true); // Force refresh
                
                // Update the post
                UWGuide_CPT_Manager::update($cpt_post_id, $content, [
                    'url' => $url,
                    'section' => $section,
                    'last_modified' => $xml_modified_date ?: current_time('Ymd'),
                ]);
            }
            // Compare the dates for non-admin context
            else if ($xml_modified_date !== $stored_modified_date) {
                // Normal update flow for frontend viewing
                // Using the cache system to get content
                $options = [
                    'find_replace' => $find_replace,
                    'adjust_tags' => $adjust_tags,
                    'unwrap_tags' => $unwrap_tags,
                    'h_select' => $h_select
                ];
                
                $content = UWGuide_Cache::get_content($url, $section, $options);
                
                // Log this action
                UWGuide_Logger::log(
                    "Updated guide content for block ID: {$block_id}",
                    UWGuide_Logger::INFO,
                    ['url' => $url, 'section' => $section]
                );
                
                // Update the post
                UWGuide_CPT_Manager::update($cpt_post_id, $content, [
                    'url' => $url,
                    'section' => $section,
                    'last_modified' => $xml_modified_date,
                ]);
            }
        }
    } else {
        // CPT does not exist, creating a new one
        $options = [
            'find_replace' => $find_replace,
            'adjust_tags' => $adjust_tags,
            'unwrap_tags' => $unwrap_tags,
            'h_select' => $h_select
        ];
        
        // Use cache system to get content
        $content = UWGuide_Cache::get_content($url, $section, $options);
        
        // Get the modified date
        $xml_modified_date = UWGuide_Content_Fetcher::get_modified_date($url);
        
        // Create the CPT
        $cpt_post_id = UWGuide_CPT_Manager::create($url, $section, $xml_modified_date, $content, $post_id, $block_id);
        
        // Log this action
        UWGuide_Logger::log(
            "Created new guide content for block ID: {$block_id}",
            UWGuide_Logger::INFO,
            ['url' => $url, 'section' => $section, 'post_id' => $cpt_post_id]
        );
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
        
        // Fetch the last modified date for the comment
        $last_modified_date = get_post_meta($cpt_post_id, 'last_modified', true);
        
        // Show the content with wrapper comments
        echo '<!-- START Content copied from ' . $url . '#' . $section . ' | Last updated: ' . $last_modified_date . ' -->';
        echo $content;
        echo '<!-- END Content copied from ' . $url . '#' . $section . ' | Last updated: ' . $last_modified_date . ' -->';
        
        wp_reset_postdata();
    } else {
        echo '<p>No content found</p>';
    }
    
    return $cpt_post_id;
}

// remove all uw guide posts if clear cache is set to yes
add_action('acf/save_post', 'uw_guide_save_options_page', 20);
function uw_guide_save_options_page($post_id)
{
    // Check if it's the options page
    if ($post_id != 'options') {
        return;
    }

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

        // Clear cache from the cache class as well
        UWGuide_Cache::clear_all_caches();
        
        // Reset the 'clear_cache' field to 'no'
        update_field('uw_guide_clear_cache', 'no', 'option');
        
        // Log the action
        UWGuide_Logger::log("Cache cleared manually from settings", UWGuide_Logger::INFO);
    }
}

// Function to handle the shortcode
function uw_guide_shortcode($atts)
{
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
        UWGuide_Logger::log("Shortcode used without ID", UWGuide_Logger::WARNING);
        return 'No id provided.';
    }
    
    // Check if CPT exists
    $cpt_check = UWGuide_CPT_Manager::exists($shortcode_id);
    
    if ($cpt_check['exists']) {
        $post_id = $cpt_check['post_id'];
        
        // Query to get the post content
        $query = new WP_Query(array(
            'post_type' => 'uw-guide',
            'p' => $post_id
        ));
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $content = get_the_content();
                
                // Metadata for comments
                $last_modified_date = get_post_meta(get_the_ID(), 'last_modified', true);
                $url = get_post_meta(get_the_ID(), 'url', true);
                $section = get_post_meta(get_the_ID(), 'section', true);
                
                // Output with comments
                $output = '<!-- START Content copied from ' . esc_url($url) . '#' . esc_attr($section) . ' | Last updated: ' . esc_attr($last_modified_date) . ' -->';
                $output .= $content;
                $output .= '<!-- END Content copied from ' . esc_url($url) . '#' . esc_attr($section) . ' | Last updated: ' . esc_attr($last_modified_date) . ' -->';
            }
            wp_reset_postdata();
        } else {
            UWGuide_Logger::log("No posts found with shortcode ID: {$shortcode_id}", UWGuide_Logger::WARNING);
            $output = 'No posts found with that id.';
        }
    } else {
        $output = extract_and_create_content_from_block($shortcode_id);
    }
    
    return $output;
}

// Helper function to extract parameters from blocks and create content
function extract_and_create_content_from_block($shortcode_id) {
    global $post;
    $post_id = $post->ID;
    
    // Parse the blocks to find our block with the matching shortcode ID
    $blocks = parse_blocks($post->post_content);
    
    // Extract parameters
    $params = extract_block_parameters($blocks, $shortcode_id);
    
    if (!$params['found'] || empty($params['url'])) {
        UWGuide_Logger::log(
            "Failed to create content: Invalid URL for shortcode ID {$shortcode_id}",
            UWGuide_Logger::ERROR
        );
        return 'A valid URL was not provided.';
    }
    
    // Use cache system to get content
    $options = [
        'find_replace' => $params['find_replace'],
        'adjust_tags' => $params['adjust_tags'],
        'unwrap_tags' => $params['unwrap_tags'],
        'h_select' => $params['h_select']
    ];
    
    $content = UWGuide_Cache::get_content($params['url'], $params['section'], $options);
    $xml_modified_date = UWGuide_Content_Fetcher::get_modified_date($params['url']);
    
    // Create the CPT
    $cpt_post_id = UWGuide_CPT_Manager::create(
        $params['url'], 
        $params['section'], 
        $xml_modified_date, 
        $content, 
        $post_id, 
        $shortcode_id
    );
    
    if ($cpt_post_id) {
        $output = '<!-- START Content copied from ' . $params['url'] . '#' . $params['section'] . ' | Last updated: ' . $xml_modified_date . ' -->';
        $output .= $content;
        $output .= '<!-- END Content copied from ' . $params['url'] . '#' . $params['section'] . ' | Last updated: ' . $xml_modified_date . ' -->';
        
        UWGuide_Logger::log(
            "Created content via shortcode for ID: {$shortcode_id}",
            UWGuide_Logger::INFO,
            ['post_id' => $cpt_post_id]
        );
    } else {
        UWGuide_Logger::log(
            "Failed to create content via shortcode for ID: {$shortcode_id}",
            UWGuide_Logger::ERROR
        );
        $output = 'Failed to create content.';
    }
    
    return $output;
}

// Helper function to extract parameters from block data
function extract_block_parameters($blocks, $shortcode_id) {
    $params = [
        'url' => '',
        'section' => '',
        'find_replace' => [],
        'adjust_tags' => [],
        'unwrap_tags' => [],
        'h_select' => [],
        'found' => false
    ];
    
    foreach ($blocks as $block) {
        if ($block['blockName'] === 'acf/guide') {
            $block_shortcode_id = $block['attrs']['data']['shortcode_id'] ?? '';
            
            if ($block_shortcode_id === $shortcode_id) {
                $params['url'] = $block['attrs']['data']['url'] ?? '';
                $params['section'] = $block['attrs']['data']['section'] ?? '';
                
                // Extract repeater fields
                $params['find_replace'] = extract_find_replace_field($block);
                $params['unwrap_tags'] = extract_unwrap_tags_field($block);
                $params['adjust_tags'] = extract_adjust_tags_field($block);
                
                // Extract heading selection
                $params['h_select'] = [
                    'select_heading' => $block['attrs']['data']['select_heading'] ?? '',
                    'select_direction' => $block['attrs']['data']['select_direction'] ?? '',
                    'select_title' => $block['attrs']['data']['select_title'] ?? ''
                ];
                
                $params['found'] = true;
                break;
            }
        }
    }
    
    return $params;
}

// Helper function to extract find_replace field
function extract_find_replace_field($block) {
    $find_replace = [];
    $find_replace_count = intval($block['attrs']['data']['find_replace'] ?? 0);
    
    for ($i = 0; $i < $find_replace_count; $i++) {
        $find = $block['attrs']['data']["find_replace_{$i}_find"] ?? '';
        $replace = $block['attrs']['data']["find_replace_{$i}_replace"] ?? '';
        if ($find !== '') {
            $find_replace[] = [
                'find' => $find,
                'replace' => $replace
            ];
        }
    }
    
    return $find_replace;
}

// Helper function to extract unwrap_tags field
function extract_unwrap_tags_field($block) {
    $unwrap_tags = [];
    $unwrap_tags_count = intval($block['attrs']['data']['remove_tags'] ?? 0);
    
    for ($i = 0; $i < $unwrap_tags_count; $i++) {
        $tag = $block['attrs']['data']["remove_tags_{$i}_tag_type"] ?? '';
        if ($tag !== '') {
            $unwrap_tags[] = $tag;
        }
    }
    
    return $unwrap_tags;
}

// Helper function to extract adjust_tags field
function extract_adjust_tags_field($block) {
    $adjust_tags = [];
    $adjust_tags_count = intval($block['attrs']['data']['adjust_tags'] ?? 0);
    
    for ($i = 0; $i < $adjust_tags_count; $i++) {
        $first_tag = $block['attrs']['data']["adjust_tags_{$i}_first_tag"] ?? '';
        $second_tag = $block['attrs']['data']["adjust_tags_{$i}_second_tag"] ?? '';
        if ($first_tag !== '' && $second_tag !== '') {
            $adjust_tags[] = [
                'first_tag' => $first_tag,
                'second_tag' => $second_tag
            ];
        }
    }
    
    return $adjust_tags;
}

// Register the shortcode with WordPress
add_shortcode('uw-guide', 'uw_guide_shortcode');

// helper functions
function encode_html_entities($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
