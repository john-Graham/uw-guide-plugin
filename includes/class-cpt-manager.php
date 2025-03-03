<?php
/**
 * Class to manage Custom Post Type operations
 */
class UWGuide_CPT_Manager {

    /**
     * Create a new guide CPT
     *
     * @param string $url Source URL
     * @param string $section XML section name
     * @param string $modified_date Last modified date
     * @param string $content Post content
     * @param int $parent_post_id ID of the parent post
     * @param string $shortcode_id Unique identifier for the shortcode
     * @return int|false Post ID on success, false on failure
     */
    public static function create($url, $section, $modified_date, $content, $parent_post_id, $shortcode_id) {
        $post_id = wp_insert_post(array(
            'post_title'    => $shortcode_id,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'uw-guide',
            'meta_input'    => array(
                'url'           => $url,
                'section'       => $section,
                'last_modified' => UWGuide_Date_Handler::standardize($modified_date),
                'shortcode_id'  => $shortcode_id,
                'id_of_post'    => $parent_post_id,
            ),
        ));
        
        if (!$post_id) {
            error_log("Failed to create a new guide with shortcode_id: {$shortcode_id}");
            return false;
        }
        
        return $post_id;
    }
    
    /**
     * Update an existing guide CPT
     *
     * @param int $post_id Post ID to update
     * @param string $content New content
     * @param array $meta_data Additional metadata to update
     * @return bool Success status
     */
    public static function update($post_id, $content, $meta_data = []) {
        $post_data = [
            'ID' => $post_id,
            'post_content' => $content,
        ];
        
        if (!empty($meta_data)) {
            $post_data['meta_input'] = $meta_data;
        }
        
        $result = wp_update_post($post_data, true);
        
        if (is_wp_error($result)) {
            error_log('Failed to update guide: ' . $result->get_error_message());
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if guide CPT exists by shortcode ID
     *
     * @param string $shortcode_id Shortcode identifier
     * @return array Result with exists, update_required, and post_id keys
     */
    public static function exists($shortcode_id) {
        if (empty($shortcode_id)) {
            return [
                'exists' => false,
                'update_required' => false
            ];
        }
        
        $args = [
            'post_type' => 'uw-guide',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'shortcode_id',
                    'value' => $shortcode_id,
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $post_id = $query->posts[0];
            $update_required = self::check_update_frequency($post_id);
            
            return [
                'exists' => true,
                'update_required' => $update_required,
                'post_id' => $post_id
            ];
        }
        
        return [
            'exists' => false,
            'update_required' => false
        ];
    }
    
    /**
     * Check if content needs updating based on frequency settings
     *
     * @param int $post_id Post ID to check
     * @return bool Whether an update is required
     */
    private static function check_update_frequency($post_id) {
        $last_modified = get_post_meta($post_id, 'last_modified', true);
        
        if (empty($last_modified)) {
            return true;
        }
        
        $date = UWGuide_Date_Handler::parse_date($last_modified);
        
        if ($date === false) {
            error_log('Invalid date format for post ID: ' . $post_id . '. Date string: ' . $last_modified);
            return true;  // Update if date is invalid
        }
        
        $frequency = get_field('uw_guide_update_frequency', 'options');
        
        // Default to daily if no frequency set
        if (empty($frequency)) {
            $frequency = 'daily';
        }
        
        switch ($frequency) {
            case 'everytime':
                return true;
            case 'daily':
                $interval = '1 day';
                break;
            case 'weekly':
                $interval = '1 week';
                break;
            case 'monthly':
                $interval = '1 month';
                break;
            default:
                $interval = '1 day';
        }
        
        $now = new DateTime();
        $then = clone $date;
        $then->modify("+{$interval}");
        
        return $now > $then;
    }
}
