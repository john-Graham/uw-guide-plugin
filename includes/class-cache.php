<?php
/**
 * Cache handler for plugin
 */
class UWGuide_Cache {
    
    /**
     * Cache prefix for transient keys
     */
    const CACHE_PREFIX = 'uwguide_';
    
    /**
     * Default cache duration in hours
     */
    const DEFAULT_DURATION = 24;
    
    /**
     * Get content from cache or fetch it
     *
     * @param string $url URL to fetch
     * @param string $section Section to extract
     * @param array $options Additional options
     * @param bool $bypass_cache Whether to bypass the cache
     * @return string Content
     */
    public static function get_content($url, $section, $options = [], $bypass_cache = false) {
        // Generate unique cache key
        $cache_key = self::generate_cache_key($url, $section, $options);
        
        // Try to get from cache first (unless bypass is requested)
        $content = false;
        if (!$bypass_cache) {
            $content = get_transient($cache_key);
        }
        
        // If not in cache or bypassing cache, fetch fresh content
        if ($content === false) {
            $find_replace = $options['find_replace'] ?? [];
            $adjust_tags = $options['adjust_tags'] ?? [];
            $unwrap_tags = $options['unwrap_tags'] ?? [];
            $h_select = $options['h_select'] ?? [];
            
            $content = UWGuide_Content_Fetcher::get_xml_content(
                $url, 
                $section, 
                $find_replace, 
                $adjust_tags, 
                $unwrap_tags, 
                $h_select
            );
            
            // Cache the result (even when bypassing - just refreshes the cache)
            self::set_content_cache($cache_key, $content);
        }
        
        return $content;
    }
    
    /**
     * Cache content
     *
     * @param string $key Cache key
     * @param string $content Content to cache
     * @param int $duration Duration in hours
     */
    public static function set_content_cache($key, $content, $duration = null) {
        if ($duration === null) {
            $duration = self::get_cache_duration();
        }
        
        // Convert hours to seconds
        $seconds = $duration * HOUR_IN_SECONDS;
        
        // Store in transient
        set_transient($key, $content, $seconds);
    }
    
    /**
     * Generate a unique cache key
     *
     * @param string $url URL
     * @param string $section Section
     * @param array $options Additional options
     * @return string Cache key
     */
    public static function generate_cache_key($url, $section, $options = []) {
        // Create an array of all parameters that affect the content
        $key_parts = [
            'url' => $url,
            'section' => $section,
        ];
        
        // Include options that affect the content
        if (!empty($options['find_replace'])) {
            $key_parts['find_replace'] = $options['find_replace'];
        }
        
        if (!empty($options['adjust_tags'])) {
            $key_parts['adjust_tags'] = $options['adjust_tags'];
        }
        
        if (!empty($options['unwrap_tags'])) {
            $key_parts['unwrap_tags'] = $options['unwrap_tags'];
        }
        
        if (!empty($options['h_select'])) {
            $key_parts['h_select'] = $options['h_select'];
        }
        
        // Create a unique hash from all parameters
        $hash = md5(serialize($key_parts));
        
        return self::CACHE_PREFIX . $hash;
    }
    
    /**
     * Clear all plugin caches
     */
    public static function clear_all_caches() {
        global $wpdb;
        
        // Get all transients with our prefix
        $sql = $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_' . self::CACHE_PREFIX . '%'
        );
        
        $transients = $wpdb->get_col($sql);
        
        // Delete each transient
        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient);
            delete_transient($transient_name);
        }
    }
    
    /**
     * Get cache duration from settings or default
     * 
     * @return int Duration in hours
     */
    private static function get_cache_duration() {
        $frequency = get_field('uw_guide_update_frequency', 'options');
        
        switch ($frequency) {
            case 'everytime':
                return 0;
            case 'daily':
                return 24;
            case 'weekly':
                return 168; // 7 days
            case 'monthly':
                return 720; // 30 days
            default:
                return self::DEFAULT_DURATION;
        }
    }
}
