<?php
/**
 * Admin UI enhancements for the plugin
 */
class UWGuide_Admin_UI {
    
    /**
     * Initialize the admin UI
     */
    public static function init() {
        // Add admin column for shortcode ID
        add_filter('manage_uw-guide_posts_columns', [self::class, 'add_columns']);
        add_action('manage_uw-guide_posts_custom_column', [self::class, 'render_columns'], 10, 2);
        
        // Add status dashboard widget
        add_action('wp_dashboard_setup', [self::class, 'add_dashboard_widget']);
        
        // Add a debug page
        add_action('admin_menu', [self::class, 'add_debug_page']);
    }
    
    /**
     * Add custom columns to the CPT listing
     */
    public static function add_columns($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['shortcode_id'] = 'Shortcode ID';
                $new_columns['source_url'] = 'Source URL';
                $new_columns['last_updated'] = 'Last Updated';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render the custom column data
     */
    public static function render_columns($column, $post_id) {
        switch ($column) {
            case 'shortcode_id':
                $id = get_post_meta($post_id, 'shortcode_id', true);
                echo '<code>[uw-guide id="' . esc_attr($id) . '"]</code>';
                echo '<button class="button button-small copy-shortcode" data-shortcode="[uw-guide id=&quot;' . esc_attr($id) . '&quot;]">Copy</button>';
                break;
                
            case 'source_url':
                $url = get_post_meta($post_id, 'url', true);
                $section = get_post_meta($post_id, 'section', true);
                if ($url) {
                    echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>';
                    if ($section) {
                        echo '<br><small>Section: ' . esc_html($section) . '</small>';
                    }
                }
                break;
                
            case 'last_updated':
                $date = get_post_meta($post_id, 'last_modified', true);
                if ($date) {
                    $date_object = UWGuide_Date_Handler::parse_date($date);
                    if ($date_object) {
                        echo $date_object->format('F j, Y');
                    } else {
                        echo esc_html($date);
                    }
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Add dashboard widget with guide statistics
     */
    public static function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'uw_guide_dashboard_widget',
            'UW Guide Content',
            [self::class, 'render_dashboard_widget']
        );
    }
    
    /**
     * Render the dashboard widget content
     */
    public static function render_dashboard_widget() {
        $guide_count = wp_count_posts('uw-guide')->publish;
        $latest_update = self::get_latest_guide_update();
        
        echo '<p><strong>Total guides:</strong> ' . esc_html($guide_count) . '</p>';
        
        if ($latest_update) {
            echo '<p><strong>Latest update:</strong> ' . esc_html($latest_update) . '</p>';
        }
        
        echo '<p><a href="' . admin_url('edit.php?post_type=uw-guide') . '" class="button">View All Guides</a> ';
        echo '<a href="' . admin_url('admin.php?page=acf-options-uw-guide-settings') . '" class="button">Guide Settings</a></p>';
    }
    
    /**
     * Get the date of the most recently updated guide
     */
    private static function get_latest_guide_update() {
        $query = new WP_Query([
            'post_type' => 'uw-guide',
            'posts_per_page' => 1,
            'orderby' => 'meta_value',
            'meta_key' => 'last_modified',
            'order' => 'DESC',
        ]);
        
        if ($query->have_posts()) {
            $query->the_post();
            $date = get_post_meta(get_the_ID(), 'last_modified', true);
            wp_reset_postdata();
            
            $date_object = UWGuide_Date_Handler::parse_date($date);
            if ($date_object) {
                return $date_object->format('F j, Y');
            }
            return $date;
        }
        
        return false;
    }
    
    /**
     * Add a debug page
     */
    public static function add_debug_page() {
        add_submenu_page(
            'edit.php?post_type=uw-guide',
            'Debug Information',
            'Debug Info',
            'manage_options',
            'uw-guide-debug',
            [self::class, 'render_debug_page']
        );
    }
    
    /**
     * Render the debug page
     */
    public static function render_debug_page() {
        echo '<div class="wrap">';
        echo '<h1>UW Guide Debug Information</h1>';
        
        // Plugin info
        echo '<h2>Plugin Information</h2>';
        echo '<table class="widefat fixed">';
        echo '<tr><th>Plugin Version</th><td>' . UW_GUIDE_CONTENT_VERSION . '</td></tr>';
        echo '<tr><th>WordPress Version</th><td>' . get_bloginfo('version') . '</td></tr>';
        echo '<tr><th>PHP Version</th><td>' . phpversion() . '</td></tr>';
        echo '</table>';
        
        // Update settings
        echo '<h2>Update Settings</h2>';
        echo '<table class="widefat fixed">';
        echo '<tr><th>Update Frequency</th><td>' . get_field('uw_guide_update_frequency', 'option') . '</td></tr>';
        echo '<tr><th>Bootstrap Tables</th><td>' . (get_field('uw_guide_bootstrap_tables', 'option') ? 'Yes' : 'No') . '</td></tr>';
        echo '</table>';
        
        // Test fetch functionality
        if (isset($_POST['test_url']) && isset($_POST['test_section'])) {
            echo '<h2>Fetch Test Results</h2>';
            $test_url = sanitize_text_field($_POST['test_url']);
            $test_section = sanitize_text_field($_POST['test_section']);
            
            echo '<h3>Testing URL: ' . esc_html($test_url) . ' / Section: ' . esc_html($test_section) . '</h3>';
            
            // Test modified date
            echo '<h4>Modified Date Test</h4>';
            $modified_date = UWGuide_Content_Fetcher::get_modified_date($test_url);
            echo '<p>Modified Date: ' . ($modified_date ? esc_html($modified_date) : 'Failed to retrieve') . '</p>';
            
            // Test content fetch
            echo '<h4>Content Fetch Test</h4>';
            $content = UWGuide_Content_Fetcher::get_xml_content($test_url, $test_section, [], [], [], []);
            echo '<div style="border:1px solid #ccc; padding:10px; max-height:300px; overflow:auto;">';
            if (strpos($content, 'Failed to load content') !== false || strpos($content, 'Section not found') !== false) {
                echo '<p style="color:red;">' . esc_html($content) . '</p>';
            } else {
                echo '<p style="color:green;">Successfully fetched content (' . strlen($content) . ' bytes)</p>';
                echo '<hr>';
                echo wp_kses_post($content);
            }
            echo '</div>';
        }
        
        // Test form
        echo '<h2>Test Content Fetch</h2>';
        echo '<form method="post">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="test_url">Test URL</label></th><td><input type="text" id="test_url" name="test_url" class="regular-text" placeholder="https://guide.wisc.edu/some-page/" required></td></tr>';
        echo '<tr><th><label for="test_section">Section</label></th><td><input type="text" id="test_section" name="test_section" placeholder="e.g., maincontent" required></td></tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" class="button button-primary" value="Run Test"></p>';
        echo '</form>';
        
        echo '</div>';
        
        // Add JavaScript for copy button
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var copyButtons = document.querySelectorAll(".copy-shortcode");
                copyButtons.forEach(function(button) {
                    button.addEventListener("click", function() {
                        var shortcode = this.getAttribute("data-shortcode");
                        navigator.clipboard.writeText(shortcode).then(function() {
                            button.textContent = "Copied!";
                            setTimeout(function() {
                                button.textContent = "Copy";
                            }, 2000);
                        });
                    });
                });
            });
        </script>';
    }
}

// Initialize the Admin UI
UWGuide_Admin_UI::init();
