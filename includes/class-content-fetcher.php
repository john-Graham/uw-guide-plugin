<?php
/**
 * Class for fetching and processing remote content
 */
class UWGuide_Content_Fetcher {

    /**
     * Fetch and process XML content from a remote URL
     *
     * @param string $url Base URL to fetch from
     * @param string $section Section name to extract
     * @param array $find_replace Find and replace patterns
     * @param array $adjust_tags Tag modifications
     * @param array $unwrap_tags Tags to unwrap
     * @param array $h_select Heading selection options
     * @return string Processed content
     */
    public static function get_xml_content($url, $section, $find_replace = [], $adjust_tags = [], $unwrap_tags = [], $h_select = []) {
        $xml_url = rtrim($url, '/') . '/index.xml';
        
        error_log('Fetching XML content from: ' . $xml_url);
        
        $response = wp_remote_get($xml_url);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            $error = is_wp_error($response) ? $response->get_error_message() : 'HTTP status code ' . wp_remote_retrieve_response_code($response);
            error_log('Failed to fetch XML: ' . $error);
            return "Failed to load content. Error: {$error}";
        }
        
        $xml_body = wp_remote_retrieve_body($response);
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_body);
        libxml_use_internal_errors(false);
        
        if ($xml === false) {
            error_log('Invalid XML format from: ' . $xml_url);
            return "Invalid XML format.";
        }
        
        if (!isset($xml->$section)) {
            error_log("Section '{$section}' not found in XML from: " . $xml_url);
            return "Section '{$section}' not found in the content.";
        }
        
        $content = self::process_content(
            (string)$xml->$section,
            $section,
            $find_replace,
            $adjust_tags,
            $unwrap_tags,
            $h_select
        );
        
        return $content;
    }
    
    /**
     * Get the last modified date of a remote URL
     *
     * @param string $url URL to check
     * @return string Standardized date string
     */
    public static function get_modified_date($url) {
        $response = wp_remote_head($url);
        
        if (is_wp_error($response)) {
            error_log('Failed to get headers: ' . $response->get_error_message());
            return '';
        }
        
        $headers = wp_remote_retrieve_headers($response);
        $modified = isset($headers['last-modified']) ? $headers['last-modified'] : '';
        
        return UWGuide_Date_Handler::standardize($modified);
    }
    
    /**
     * Process and clean content from XML
     *
     * @param string $content Raw content
     * @param string $section Section name
     * @param array $find_replace Find and replace patterns
     * @param array $adjust_tags Tag modifications
     * @param array $unwrap_tags Tags to unwrap
     * @param array $h_select Heading selection options
     * @return string Processed content
     */
    private static function process_content($content, $section, $find_replace, $adjust_tags, $unwrap_tags, $h_select) {
        // Apply global find and replace before block level modifications
        $find_replace_pairs = get_field('uw_guide_global_find_and_replace', 'option');

        if (!empty($find_replace_pairs)) {
            // Define smart quotes replacements
            $smart_quotes = [
                '"' => '"',
                '"' => '"',
                "'" => "'",
                "'" => "'",
                '&ldquo;' => '"',
                '&rdquo;' => '"',
                '&lsquo;' => "'",
                '&rsquo;' => "'",
            ];

            // Replace smart quotes in the content
            $content = str_replace(array_keys($smart_quotes), array_values($smart_quotes), $content);

            foreach ($find_replace_pairs as $pair) {
                if (isset($pair['uw_guide_global_find']) && isset($pair['uw_guide_global_replace'])) {
                    // Decode any HTML entities in the find and replace strings
                    $find = htmlspecialchars_decode($pair['uw_guide_global_find']);
                    $replace = htmlspecialchars_decode($pair['uw_guide_global_replace']);

                    // Replace the strings in the content
                    $content = str_replace($find, $replace, $content);
                }
            }
        }
        
        // Process content with DOM methods (similar to uwguide_clean_content_all)
        $content = self::process_dom_content($content, $adjust_tags, $unwrap_tags);

        // Handle heading selection if specified
        if (!empty($h_select)) {
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);

            $select_heading = $h_select['select_heading'] ?? '';
            $select_direction = $h_select['select_direction'] ?? '';
            $select_title = $h_select['select_title'] ?? '';

            if ($select_heading && $select_direction && $select_title) {
                $extracted_content = self::extract_content_by_heading(
                    $dom, 
                    $xpath, 
                    $select_heading, 
                    $select_title, 
                    $select_direction
                );
                
                if ($extracted_content) {
                    $content = $extracted_content;
                }
            }
        }
        
        // Ensure content is UTF-8 encoded
        $content = self::ensure_utf8_encoding($content);
        
        // Apply find/replace patterns
        if (is_array($find_replace) && !empty($find_replace)) {
            foreach ($find_replace as $pair) {
                if (isset($pair['find']) && isset($pair['replace'])) {
                    // Ensure find and replace strings are in UTF-8 encoding
                    $find = self::ensure_utf8_encoding($pair['find']);
                    $replace = self::ensure_utf8_encoding($pair['replace']);

                    // Determine if the find string contains HTML
                    if (preg_match('/<.*?>/', $find)) {
                        // Handle HTML replacements using regex
                        $content = preg_replace('/' . preg_quote($find, '/') . '/', $replace, $content);
                    } else {
                        // Handle plain text replacements
                        $content = str_replace($find, $replace, $content);
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process HTML content using DOM methods
     * 
     * @param string $content HTML content to process
     * @param array $adjust_tags Tags to adjust
     * @param array $unwrap_tags Tags to unwrap
     * @return string Processed HTML content
     */
    private static function process_dom_content($content, $adjust_tags, $unwrap_tags) {
        $content = str_replace('&amp;', '[AMPERSAND]', $content);
        $fullHtml = '<!DOCTYPE html><html><body>' . $content . '</body></html>';

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($fullHtml, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        // Remove hidden elements
        $hiddenElements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' hidden ')]");
        foreach ($hiddenElements as $element) {
            $element->parentNode->removeChild($element);
        }

        // Apply Bootstrap to tables if enabled
        $bootstrapTablesEnabled = get_field('uw_guide_bootstrap_tables', 'options');
        $allElements = $xpath->query('//*');
        foreach ($allElements as $element) {
            if (!empty($adjust_tags) && is_array($adjust_tags)) {
                self::adjust_tags($dom, $element, $adjust_tags);
            }

            if ($bootstrapTablesEnabled && $element->nodeName === 'table') {
                $existingClass = $element->getAttribute('class');
                $newClass = trim($existingClass . ' table table-striped');
                $element->setAttribute('class', $newClass);
            }
        }

        // Remove undefined classes
        $undefinedClasses = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' undefined ')]");
        foreach ($undefinedClasses as $element) {
            $classes = explode(' ', $element->getAttribute('class'));
            $classes = array_filter($classes, function ($class) {
                return $class !== 'undefined';
            });
            $element->setAttribute('class', implode(' ', $classes));
        }

        // Remove "onthispage" divs
        $divs = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' onthispage ')]");
        foreach ($divs as $div) {
            $div->parentNode->removeChild($div);
        }

        // Fix links
        $server_domain = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST);
        $base_url = 'https://guide.wisc.edu';

        $links = $xpath->query("//a");
        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if (empty($href)) continue;

            $href_domain = parse_url($href, PHP_URL_HOST);

            if ($href_domain === null && substr($href, 0, 1) !== '/') {
                continue;
            }

            if (substr($href, 0, 1) === '/') {
                $link->setAttribute('href', $base_url . $href);
            } elseif (strtolower($href_domain) === strtolower($server_domain)) {
                $link->removeAttribute('target');
            }
        }

        // Convert code bubbles to links
        $spans = $xpath->query("//span[@class='code_bubble']");
        foreach ($spans as $span) {
            $dataCodeBubble = $span->getAttribute('data-code-bubble');
            $dataCodeBubble = str_replace('[AMPERSAND]', '%26', $dataCodeBubble);
            $spanContent = $span->textContent;

            $newLink = $dom->createElement('a', $spanContent);
            $newLink->setAttribute('href', $base_url . '/search/?P=' . $dataCodeBubble);

            $span->parentNode->replaceChild($newLink, $span);
        }

        // Process unwrap tags if specified
        if (!empty($unwrap_tags)) {
            self::unwrap_tags($xpath, $dom, $unwrap_tags);
        }

        // Extract the processed HTML
        $body = $dom->getElementsByTagName('body')->item(0);
        $newContent = '';
        foreach ($body->childNodes as $child) {
            $newContent .= $dom->saveHTML($child);
        }

        $newContent = str_replace('[AMPERSAND]', '&amp;', $newContent);

        return $newContent;
    }

    /**
     * Helper function to adjust HTML tags
     */
    private static function adjust_tags($dom, $element, $adjust_tags) {
        foreach ($adjust_tags as $tag_pair) {
            if ($element->tagName === $tag_pair['first_tag']) {
                $newElement = $dom->createElement($tag_pair['second_tag']);
                foreach ($element->childNodes as $child) {
                    $newElement->appendChild($child->cloneNode(true));
                }
                foreach ($element->attributes as $attribute) {
                    $newElement->setAttribute($attribute->name, $attribute->value);
                }
                $element->parentNode->replaceChild($newElement, $element);
                break;
            }
        }
    }

    /**
     * Helper function to unwrap HTML tags
     */
    private static function unwrap_tags($xpath, $dom, $tags) {
        foreach ($tags as $tagName) {
            $elements = $xpath->query("//{$tagName}");
            foreach ($elements as $element) {
                $fragment = $dom->createDocumentFragment();
                while ($element->childNodes->length > 0) {
                    $fragment->appendChild($element->childNodes->item(0));
                }
                $element->parentNode->replaceChild($fragment, $element);
            }
        }
    }

    /**
     * Helper function to extract content by heading
     */
    private static function extract_content_by_heading($dom, $xpath, $tag, $title, $mode) {
        $query = "//{$tag}[contains(., '{$title}')]";
        $headers = $xpath->query($query);
        $content = '';

        if ($headers->length > 0) {
            $header = $headers->item(0); // Get the specific header node
            
            switch ($mode) {
                case 'before':
                    $query = "preceding-sibling::node()[following-sibling::{$tag}[1][contains(., '{$title}')]]";
                    break;
                case 'after':
                    $content .= $dom->saveHTML($header); // Include the searched heading tag in the result
                    $query = "following-sibling::node()[preceding-sibling::{$tag}[1][contains(., '{$title}')]]";
                    break;
                default:
                    return 'Invalid mode specified.';
            }

            // Execute the new XPath query based on the mode
            $nodes = $xpath->query($query, $header); // Change context to header
            
            foreach ($nodes as $node) {
                if ($node->nodeName !== $tag) { // Exclude the next heading tag
                    $content .= $dom->saveHTML($node);
                } else {
                    break; // Stop at the next heading tag
                }
            }
        } else {
            return 'Header not found.';
        }

        return $content;
    }

    /**
     * Ensure string is UTF-8 encoded
     */
    private static function ensure_utf8_encoding($string) {
        return mb_convert_encoding($string, 'UTF-8', 'auto');
    }
}
