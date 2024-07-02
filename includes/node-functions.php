<?php
// Path: includes/node-functions.php

if (!defined('ABSPATH')) exit;

function uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $unwrap_tags, $h_select)
{
    error_log('REMOTE CALL: uwguide_get_xml_node called');

    // Append 'index.xml' to the URL
    $url .= 'index.xml';

    // Use wp_remote_get to fetch the XML file
    $response = wp_remote_get($url);

    // Check for a WP_Error or a non-200 status code
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        error_log('Failed to load XML from: ' . $url);
        return "Failed to load XML. Error: " . (is_wp_error($response) ? $response->get_error_message() : 'HTTP status code ' . wp_remote_retrieve_response_code($response));
    }

    // Get the body of the response
    $xml_body = wp_remote_retrieve_body($response);

    // Load XML from the string, suppress errors with libxml
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml_body);
    libxml_use_internal_errors(false);

    if ($xml === false) {
        error_log('Invalid XML format.');
        return "Invalid XML format.";
    }

    // Check if the section exists in the XML
    if (isset($xml->$section)) {
        $sectionContent = $xml->$section;

        // Clean the content
        $sectionContent = uwguide_clean_content($sectionContent, $section, $find_replace, $adjust_tags, $unwrap_tags, $h_select);
        // error_log('Content cleaned: ' . $sectionContent);

        // Using CDATA section as a string
        return (string)$sectionContent;
    } else {
        return "Section not found in the XML.";
    }
}

// Getting the modified date of a guide webpage
function uwguide_get_url_modified_date($url)
{
    error_log('REMOTE CALL - HEAD: uwguide_get_url_modified_date ');
    // Keeping the payload as small as possible
    $response = wp_remote_head($url);

    if (is_wp_error($response)) {
        // Handle error appropriately
        return null;
    }

    $headers = wp_remote_retrieve_headers($response);
    $guide_modified = $headers['Last-Modified'] ?? null;

    // formatting the date to be the same as the ACF field
    $guide_modified = date('Ymd', strtotime($guide_modified));
    // error_log('Guide modified date: ' . $guide_modified);

    return $guide_modified;
}

function uwguide_clean_content($content, $section, $find_replace, $adjust_tags, $unwrap_tags, $h_select)
{
    // error_log('Called function uwguide_clean_content ');
    // error_log('Section: ' . $section);

    // Apply global find and replace before block level modifications
    $find_replace_pairs = get_field('uw_guide_global_find_and_replace', 'option');

    if (!empty($find_replace_pairs)) {
        // Define smart quotes replacements
        $smart_quotes = [
            '“' => '"',
            '”' => '"',
            '‘' => "'",
            '’' => "'",
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
    // keeping the programatic changes in here that's done to all content, before any block level modifications
    $content = uwguide_clean_content_all($content, $adjust_tags, $unwrap_tags);



    if (!empty($unwrap_tags)) {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        unwrap_tags($xpath, $dom, $unwrap_tags);

        $unwrappedContent = '';
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $child) {
            $unwrappedContent .= $dom->saveHTML($child);
        }
        $content = $unwrappedContent;
    }

    if (!empty($h_select)) {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $select_heading = $h_select['select_heading'] ?? '';
        $select_direction = $h_select['select_direction'] ?? '';
        $select_title = $h_select['select_title'] ?? '';

        if ($select_heading && $select_direction && $select_title) {
            $extracted_content = extract_content_by_heading($dom, $xpath, $select_heading, $select_title, $select_direction);
            if ($extracted_content) {
                // error_log('Extracted content: ' . $extracted_content);
                $content = $extracted_content;
            }
        }
    }
    // error_log('Find Replace: ' . print_r($find_replace, true));
    // error_log(('String is:' . (string)$content));
    $content = ensure_utf8_encoding($content);
    // error_log('$find_replace: ' . print_r($find_replace, true));
    if (is_array($find_replace) && !empty($find_replace)) {
        // error_log('I think I have a find_replace');
        foreach ($find_replace as $pair) {
            if (isset($pair['find']) && isset($pair['replace'])) {
                // Ensure find and replace strings are in UTF-8 encoding
                $find = ensure_utf8_encoding($pair['find']);
                $replace = ensure_utf8_encoding($pair['replace']);

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
    } else {
        // error_log('Invalid find_replace parameter: ' . print_r($find_replace, true));
    }

    return $content;
}

function uwguide_clean_content_all($content, $adjust_tags, $unwrap_tags)
{
    // error_log('Called function uwguide_clean_content_all ');

    $content = str_replace('&amp;', '[AMPERSAND]', $content);

    $fullHtml = '<!DOCTYPE html><html><body>' . $content . '</body></html>';

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($fullHtml, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($dom);


    // there tend to be extra spaces at the end of text nodes, this will remove them, but only if the next sibling is not an inline element
    // REMOVED: This was taking to many spaces away because of nested spans and divs
    // $tags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th', 'span', 'div'];
    // $inlineTags = ['a', 'span', 'strong', 'em', 'b', 'i'];

    // foreach ($tags as $tag) {
    //     $nodes = $xpath->query("//{$tag}");
    //     foreach ($nodes as $node) {
    //         $childNodes = $node->childNodes;
    //         $lastIndex = $childNodes->length - 1;

    //         for ($i = 0; $i <= $lastIndex; $i++) {
    //             $child = $childNodes->item($i);
    //             if ($child->nodeType == XML_TEXT_NODE) {
    //                 $nextSibling = $child->nextSibling;
    //                 if ($nextSibling && in_array($nextSibling->nodeName, $inlineTags)) {
    //                     // Preserve a single space if the next sibling is an inline element
    //                     $child->nodeValue = rtrim($child->nodeValue) . ' ';
    //                 } else {
    //                     // Remove trailing spaces and non-breaking spaces
    //                     $child->nodeValue = rtrim($child->nodeValue, " \x{00A0}");
    //                 }
    //             }
    //         }
    //     }
    // }

    $hiddenElements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' hidden ')]");
    foreach ($hiddenElements as $element) {
        $element->parentNode->removeChild($element);
    }

    $bootstrapTablesEnabled = get_field('uw_guide_bootstrap_tables', 'options');
    $allElements = $xpath->query('//*');
    foreach ($allElements as $element) {
        if (!empty($adjust_tags) && is_array($adjust_tags)) {
            uwguide_adjust_tags($dom, $element, $adjust_tags);
        }

        if ($bootstrapTablesEnabled && $element->nodeName === 'table') {
            $existingClass = $element->getAttribute('class');
            $newClass = trim($existingClass . ' table table-striped');
            $element->setAttribute('class', $newClass);
        }
    }

    $undefinedClasses = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' undefined ')]");
    foreach ($undefinedClasses as $element) {
        $classes = explode(' ', $element->getAttribute('class'));
        $classes = array_filter($classes, function ($class) {
            return $class !== 'undefined';
        });
        $element->setAttribute('class', implode(' ', $classes));
    }

    $divs = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' onthispage ')]");
    foreach ($divs as $div) {
        $div->parentNode->removeChild($div);
        // error_log('removed div with class onthispage');
    }

    $server_domain = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST);
    $base_url = 'https://guide.wisc.edu';

    $links = $xpath->query("//a");
    foreach ($links as $link) {
        $href = $link->getAttribute('href');

        // Check if $href is valid and not empty
        if (empty($href)) {
            continue; // Skip this iteration if $href is empty
        }

        $href_domain = parse_url($href, PHP_URL_HOST);

        if ($href_domain === null && substr($href, 0, 1) !== '/') {
            continue; // Skip if $href_domain is null and it's not a relative URL
        }

        if (substr($href, 0, 1) === '/') {
            $link->setAttribute('href', $base_url . $href);
            // error_log('Updated relative href: ' . $base_url . $href);
        } elseif (strtolower($href_domain) === strtolower($server_domain)) {
            $link->removeAttribute('target');
            // error_log('Removed target from href matching domain');
        }
    }

    $spans = $xpath->query("//span[@class='code_bubble']");
    foreach ($spans as $span) {
        $dataCodeBubble = $span->getAttribute('data-code-bubble');
        $dataCodeBubble = str_replace('[AMPERSAND]', '%26', $dataCodeBubble);
        $spanContent = $span->textContent;

        $newLink = $dom->createElement('a', $spanContent);
        $newLink->setAttribute('href', $base_url . '/search/?P=' . $dataCodeBubble);

        $span->parentNode->replaceChild($newLink, $span);
        // error_log('Replaced code_bubble span with link: ' . $spanContent);
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $newContent = '';
    foreach ($body->childNodes as $child) {
        $newContent .= $dom->saveHTML($child);
    }

    $newContent = str_replace('[AMPERSAND]', '&amp;', $newContent);

    return $newContent;
}

function uwguide_adjust_tags($dom, $element, $adjust_tags)
{
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

function unwrap_tags($xpath, $dom, $tags)
{
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

function extract_content_by_heading($dom, $xpath, $tag, $title, $mode)
{
    // echo error_log('Called function extract_content_by_heading ');
    $query = "//{$tag}[contains(., '{$title}')]";
    $headers = $xpath->query($query);
    $content = '';
    // error_log('Headers: ' . $headers->length);
    // error_log('Tag: ' . $tag);
    // error_log('Title: ' . $title);
    // error_log('Mode: ' . $mode);

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

    // error_log('Extracted content: ' . $content);

    return $content;
}


function ensure_utf8_encoding($string)
{
    return mb_convert_encoding($string, 'UTF-8', 'auto');
}
