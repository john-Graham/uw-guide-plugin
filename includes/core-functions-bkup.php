<?php //guide-content - Settings callbacks

if (!defined('ABSPATH')) exit;






// Starting the process of a guide entry

function uwguide_entry($url, $section, $find_replace, $adjust_tags, $graduate_section, $post_id, $unwrap_tags, $h_select = [], $block_id)
{


    // global $post;
    $current_post_id = $post_id;
    error_log('uwguide_entry called');
    // error_log('Post ID: ' . $current_post_id);
    // error_log('URL: ' . $url);
    // error_log('Section: ' . $section);
    // error_log('Shortcode ID: ' . $shortcode_id);
    // error_log('current_post_id' . print_r($current_post_id, true));
    // print_r($find_replace);
    // Extract the values from the $h_select array
    $select_heading = $h_select['select_heading'] ?? '';
    $select_direction = $h_select['select_direction'] ?? '';
    $select_title = $h_select['select_title'] ?? '';
    //    echo 'Select Heading: ' . $select_heading . '<br>';
    //    echo 'Select Direction: ' . $select_direction . '<br>';
    //    echo 'Select Title: ' . $select_title . '<br>';
    $content = '';
    $cpt_post_id = null;


    // Removing everything after the last '/' including any anchor or query parameters to make sure we're getting the right URL
    $url = preg_replace('/\/[^\/]*$/', '/', $url);






    //////////////////////// new code ////////////////////////
    error_log('Initial block_id: ' . $block_id);

    if ($url && $section) {
        // Generate or get the block ID
        if (!$block_id) {
            $block_id = wp_generate_uuid4();
            error_log('Generated block_id: ' . $block_id);
    
            // Save the generated block_id back to the ACF field
            $updated = update_field('block_id', $block_id, get_the_ID());
            error_log('Update field result: ' . ($updated ? 'success' : 'failure'));
        }
    
        error_log('Final block_id: ' . $block_id);
        error_log('url, block_id, and section are all set');
    
        // Check if the CPT with this block ID already exists
        $existing_query = new WP_Query(array(
            'post_type' => 'uw-guide',
            'meta_key' => 'shortcode_id',
            'meta_value' => $block_id
        ));
        error_log('existing_query: ' . print_r($existing_query->posts, true));
    
        if ($existing_query->have_posts()) {
            error_log('existing_query has posts');
            $existing_query->the_post();
            $cpt_post_id = get_the_ID();
            error_log('Found CPT post ID: ' . $cpt_post_id);
        } else {
            error_log('existing_query has no posts, lets create a new one');
    
            // Fetch and clean the content from the XML
            $content = uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags);
            // error_log('Fetched content: ' . $content);
    
            // Create the CPT
            $cpt_post_id = uwguide_create_cpt($url, $section, current_time('mysql'), $content, $post_id, $block_id);
            error_log('Created CPT post ID: ' . $cpt_post_id);
    
            // Update the CPT with the block_id as the shortcode_id
            update_post_meta($cpt_post_id, 'shortcode_id', $block_id);
            error_log('Updated post meta with block_id: ' . $block_id);
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
            // error_log('Rendering content: ' . $content);
    
            // Show the content
            echo $content;
    
            wp_reset_postdata();
        } else {
            echo '<p>no content found</p>';
        }
    } else {
        echo '<p>url or section is not set</p>';
        error_log('url or section is not set');
    }

    

    
    // // Check if the CPT exists
    // $result = uwguide_check_if_cpt_exists($shortcode_id);
    // // Handle the result as needed
    // if ($result['exists']) {
    //     if ($result['update_required']) {
    //         error_log('Updating guide.');

    //         // Were checking the header for the last modified date
    //         // These shouldnt change often so we're not getting the page content unless it's changed
    //         $guide_modified = uwguide_get_url_modified_date($url);
    //         if (!$guide_modified) {
    //             error_log('No last modified date found from header of webpage.');
    //             return;
    //         }

    //         // Fetch content and clean it
    //         $content = uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags);

    //         // uwguide_update_cpt($result['post_id'], $url, $section, $guide_modified, $content, $current_post_id);
    //         $cpt_post_id = uwguide_update_cpt($result['post_id'],  $guide_modified, $content, $shortcode_id);
    //     } else {
    //         error_log('CPT exists but does not require an update.');
    //         $content = get_post_field('post_content', $result['post_id']);
    //     }
    // } else {
    //     error_log('Creating new guide.');

    //     // Fetch the last modified date from the URL for new CPT
    //     $guide_modified = uwguide_get_url_modified_date($url);
    //     if (!$guide_modified) {
    //         error_log('No last modified date found from header of webpage.');
    //         return;
    //     }

    //     // Fetch content and clean it
    //     $content = uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags);
    //     error_log(('calling uwguide_create_cpt current post id is: ' . $current_post_id . ''));
    //     if (empty($shortcode_id)) {
    //         error_log('shortcode_id is empty, generating new one');
    //         $shortcode_id = wp_generate_uuid4();
    //         // $shortcode_id = 'sddsds';
    //     }
    //     $cpt_post_id = uwguide_create_cpt($url, $section, $guide_modified, $content, $post_id, $shortcode_id);
    // }












    // Echo the content
    // echo '<!-- START Content copied on ' .  $guide_modified . ' from ' . $url . '#' . $section . ' -->';
    echo $content;
    echo '<!-- END Content copied from ' . $url . '#' . $section . ' -->';
    // return $cpt_post_id; // Return the CPT ID for use elsewhere
    // return $block['id']; // Return the CPT ID for use elsewhere

}


function uwguide_check_frequency($post_id)
{
    // Get the date from the ACF field
    error_log('uwguide_check_frequency called for post ID: ' . $post_id);
    $cpt_modified = get_post_meta($post_id, 'last_modified', true);
    error_log('Last Modified from uwguide_check_frequency: ' . $cpt_modified);


    if ($cpt_modified) {
        // Specify the format of the date we're expecting
        $cpt_modified_date = DateTime::createFromFormat('Ymd', $cpt_modified); // this is the format of the ACF field
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
    if ($last_modified_date > $earliest_allowed_date) {
        return true; // Continue if the post was last modified before the earliest allowed date
    } else {
        return false; // Do not continue if the post was modified after the earliest allowed date
    }
}




// // getting only the specific XML node from a guide webpage
// // and cleaning the content
// function uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags)
// {
//     error_log('uwguide_get_xml_node called');

//     // Remove everything after the last '/' including any anchor or query parameters
//     // MOVED this to the entry function... probably can remove these lines
//     // $url = preg_replace('/\/[^\/]*$/', '/', $url);

//     // Append 'index.xml' to the URL
//     $url .= 'index.xml';

//     try {
//         $xml = simplexml_load_file($url);
//         if ($xml === false) {
//             throw new Exception("Cannot load the XML file.");
//         }
//     } catch (Exception $e) {
//         echo "Error: " . $e->getMessage();
//         exit;
//     }

//     // Check if the section exists in the XML
//     if (isset($xml->$section)) {
//         $sectionContent = $xml->$section;

//         // Clean the content
//         $sectionContent = uwguide_clean_content($sectionContent, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags);

//         // Using CDATA section as a string
//         return (string)$sectionContent;
//     } else {
//         echo "Section not found in the XML.";
//     }
// }

function uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags)
{
    error_log('uwguide_get_xml_node called');

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
        $sectionContent = uwguide_clean_content($sectionContent, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags);

        // Using CDATA section as a string
        return (string)$sectionContent;
    } else {
        return "Section not found in the XML.";
    }
}



// Getting the modified date of a guide webpage
function uwguide_get_url_modified_date($url)
{
    error_log('uwguide_get_url_modified_date called');
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

    return $guide_modified;
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

        // Update the block with the new shortcode_id
        // $update_result = update_field('shortcode_id', $shortcode_id, $current_post_id);
        // if (!$update_result) {
        //     error_log('Failed to update shortcode_id field in block');
        // } else {
        //     error_log('Successfully updated shortcode_id field in block');
        // }

        return $post_id;
    } else {
        error_log("Failed to create a new guide.");
        return false;
    }
}

// function uwguide_create_cpt($url, $section, $guide_modified, $content, $current_post_id, $shortcode_id) {
//     error_log('uwguide_create_cpt called');
//     error_log('Current Post ID: ' . $current_post_id);
//     error_log('Shortcode ID: ' . $shortcode_id);

//     // Create the CPT with the generated UUID included in meta_input
//     $post_id = wp_insert_post(array(
//         'post_title'    => $shortcode_id, // Concatenate URL and section for title
//         'post_content'  => $content,
//         'post_status'   => 'publish',
//         'post_type'     => 'uw-guide',
//         'meta_input'    => array(
//             'url'           => $url,
//             'section'       => $section,
//             'last_modified' => $guide_modified,
//             'shortcode_id'  => $shortcode_id, // Include the UUID in meta_input
//             'id_of_post'    => $current_post_id, // Include the ID of the post that created this CPT
//         ),
//     ));

//     if ($post_id) {
//         error_log("New guide created with ID: " . $post_id . " and shortcode_id: " . $shortcode_id);

//         // Update the block with the new shortcode_id
//         $update_result = update_field('shortcode_id', $shortcode_id, $current_post_id);
//         if (!$update_result) {
//             error_log('Failed to update shortcode_id field in block');
//         } else {
//             error_log('Successfully updated shortcode_id field in block');
//         }

//         return $post_id;
//     } else {
//         error_log("Failed to create a new guide.");
//         return false;
//     }
// }






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



function uwguide_clean_content($content, $section, $find_replace, $adjust_tags, $graduate_section, $unwrap_tags)
{
    error_log('uwguide_clean_content called');
    error_log('Section: ' . $section);
    // error_log('Adjust tags: ' . print_r($adjust_tags));
    // error_log('Content: ' . print_r($find_replace, true));

    // apply global find and replace before block level modifications
    // Retrieve the repeater field from an ACF options page
    $find_replace_pairs = get_field('uw_guide_global_find_and_replace', 'option');

    // Check if the repeater field exists and is not empty
    if (!empty($find_replace_pairs)) {
        // Loop through each row of the repeater field
        foreach ($find_replace_pairs as $pair) {
            // Check if both 'find' and 'replace' subfields are set
            if (isset($pair['uw_guide_global_find']) && isset($pair['uw_guide_global_replace'])) {
                // Apply the find and replace
                $content = str_replace($pair['uw_guide_global_find'], $pair['uw_guide_global_replace'], $content);
            }
        }
    }



    // function applying cleanup to all sections
    $content = uwguide_clean_content_all($content, $adjust_tags, $unwrap_tags);


    // Loop through each find-replace pair and apply the replacements
    if (!empty($find_replace)) {
        foreach ($find_replace as $pair) {
            if (isset($pair['find']) && isset($pair['replace'])) {
                $content = str_replace($pair['find'], $pair['replace'], $content);
            }
        }
    }


    if ($section === 'overviewtext') {
        // this is called text in xml node
    }

    if ($section === 'howtogetintext') {
        // Remove class="toggle" from $content
        // can add ways to leave this in there if needed
        $content = str_replace('class="toggle"', '', $content);
    }



    if ($section === 'requirementstext') {
        error_log('Requirementstext section found');


        // graduate section isn't seperate tabs, it's all in one tab so we need to remove the other sections
        // the three possible sections are mode, curr, courses
        // this code is only for requirements tab if it's a graduate program
        if (!empty($graduate_section)) {

            // Load the HTML content into DOMDocument
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new DOMXPath($dom);

            // Find all <h3> elements
            $h3Elements = $xpath->query("//h3");

            // Initialize an array to store the sections
            $sections = array();

            // Loop through the <h3> elements and separate the content
            $currentSection = null;
            foreach ($h3Elements as $h3Element) {
                $textContent = trim($h3Element->textContent);
                // Convert to lowercase for comparison, some titles may have different cases
                $textContent = strtolower($textContent);
                if ($textContent === 'mode of instruction' || $textContent === 'curricular requirements' || $textContent === 'required courses') {
                    // error_log('Found section: ' . $textContent);

                    // Extract content until the next <h3> element
                    $currentSection = array('title' => $textContent, 'content' => '');

                    // Start from the next sibling of <h3>
                    $nextNode = $h3Element->nextSibling;

                    while ($nextNode !== null && $nextNode->nodeName !== 'h3') {
                        $currentSection['content'] .= $dom->saveHTML($nextNode);
                        $nextNode = $nextNode->nextSibling;
                    }

                    $sections[] = $currentSection;
                    // error_log('Current Section: ' . print_r($currentSection, true));
                }
            }

            // Extract the content based on $graduate_section
            $extractedContent = '';

            if ($graduate_section === 'mode') {
                $extractedContent = $sections[0]['content'];
            } elseif ($graduate_section === 'curr') {
                $extractedContent = $sections[1]['content'];
            } elseif ($graduate_section === 'courses') {
                $extractedContent = $sections[2]['content'];
            }

            // error_log('Extracted Content for ' . $graduate_section . ': ' . $extractedContent);

            // Return the extracted content
            return $extractedContent;
        }
    }





    if ($section === 'learningoutcomestext') {
    }
    if ($section === 'fouryearplantext') {
    }
    if ($section === 'advisingandcareerstext') {
    }
    if ($section === 'peopletext') {
    }
    if ($section === 'accrediationtext') {
    }
    if ($section === 'faculty') {
    }
    if ($section === 'admissionstext') {
    }
    if ($section === 'fundingtext') {
    }
    if ($section === 'policiestext') {
    }
    if ($section === 'professionaldevelopmenttext') {
    }
    if ($section === 'learningoutcomestext') {
    }

    return $content;
}

function uwguide_clean_content_all($content, $adjust_tags, $unwrap_tags)
{
    error_log('uwguide_clean_content_all called');

    // Course names with & in the title are problematic,we're replacing &amp; within data-code-bubble attributes while it's a string
    // and then replacing it back after the DOMDocument has been manipulated into the string
    $content = str_replace('&amp;', '[AMPERSAND]', $content);

    // Extract the values from the $h_select array
    $select_heading = $h_select['select_heading'] ?? '';
    $select_direction = $h_select['select_direction'] ?? '';
    $select_title = $h_select['select_title'] ?? '';
    // echo 'Select Heading: ' . $select_heading . '<br>';
    // echo 'Select Direction: ' . $select_direction . '<br>';
    // echo 'Select Title: ' . $select_title . '<br>';

    // Wrap content in full HTML structure
    // This is needed to use DOMDocument more reliably
    $fullHtml = '<!DOCTYPE html><html><body>' . $content . '</body></html>';

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($fullHtml, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($dom);


    // Unwrap specified tags
    // Unwrap specified tags if any are provided
    if (!empty($unwrap_tags)) {
        unwrap_tags($xpath, $dom, $unwrap_tags);
    }

    // Specific tags to check
    $tags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th', 'span', 'div'];
    foreach ($tags as $tag) {
        $nodes = $xpath->query("//{$tag}");
        foreach ($nodes as $node) {
            // Check the last text node for trailing non-breaking spaces and remove them
            foreach ($node->childNodes as $child) {
                if ($child->nodeType == XML_TEXT_NODE) {
                    // Regex to remove only trailing non-breaking spaces
                    $child->nodeValue = preg_replace('/\x{00A0}+$/u', '', $child->nodeValue);
                }
            }
        }
    }

    // Remove elements with class 'hidden'
    // not sure why they are there in first place, may consider switching to display:none in CSS if they are there for ADA reasons
    $hiddenElements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' hidden ')]");
    foreach ($hiddenElements as $element) {
        // Remove the element from its parent
        $element->parentNode->removeChild($element);
    }

    // a loop for all elements, used for adjusting tags and adding classes to tables
    $bootstrapTablesEnabled = get_field('uw_guide_bootstrap_tables', 'options');
    $allElements = $xpath->query('//*');
    foreach ($allElements as $element) {
        // Call the adjust tag for each element
        if (!empty($adjust_tags) && is_array($adjust_tags)) {
            uwguide_adjust_tags($dom, $element, $adjust_tags);
        }

        // Add classes to table tags if bootstrap tables are enabled
        if ($bootstrapTablesEnabled && $element->nodeName === 'table') {
            $existingClass = $element->getAttribute('class');
            $newClass = trim($existingClass . ' table table-striped');
            $element->setAttribute('class', $newClass);
        }
    }


    // Remove 'class="undefined"' from all elements, these show up in guide itself.
    // I have error reporting that looks for undefined in code so I'm keeping it out of the imported guide html
    $undefinedClasses = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' undefined ')]");
    foreach ($undefinedClasses as $element) {
        $classes = explode(' ', $element->getAttribute('class'));
        // Remove 'undefined' from the class list
        $classes = array_filter($classes, function ($class) {
            return $class !== 'undefined';
        });
        // Update the class attribute
        $element->setAttribute('class', implode(' ', $classes));
    }

    // Remove the 'on this page' div
    $divs = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' onthispage ')]");
    foreach ($divs as $div) {
        $div->parentNode->removeChild($div);
        error_log('removed div with class onthispage');
    }

    // extract_content_by_heading($dom, $xpath, $tag, $title, $mode);


    // Clean up 'a' hrefs to remove 'target' attribute if it matches the server domain & prepend the base URL to relative URLs
    $server_domain = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST);
    // $server_domain = 'engineering.wisc.edu'; // Hardcoded for debugging

    // Define the base URL
    $base_url = 'https://guide.wisc.edu';

    $links = $xpath->query("//a");
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        $href_domain = parse_url($href, PHP_URL_HOST);

        // Check if the href is a relative URL
        if (substr($href, 0, 1) === '/') {
            // Prepend the base URL to the href attribute
            $link->setAttribute('href', $base_url . $href);
            error_log('Updated relative href: ' . $base_url . $href);
        } elseif (strtolower($href_domain) === strtolower($server_domain)) {
            // If it's an absolute URL with the matching domain, remove the target attribute
            $link->removeAttribute('target');
            error_log('Removed target from href matching domain');
        }
    }

    // Modifying the functionality of code bubbles to be a link to the course catalog
    // NOTE: may add option to turn this off in the future and be a bubble again
    $spans = $xpath->query("//span[@class='code_bubble']");
    foreach ($spans as $span) {
        $dataCodeBubble = $span->getAttribute('data-code-bubble');
        // URL encoding [AMPERSAND] to %26 for the search query
        $dataCodeBubble = str_replace('[AMPERSAND]', '%26', $dataCodeBubble);
        $spanContent = $span->textContent;

        // Create a new 'a' element
        $newLink = $dom->createElement('a', $spanContent);
        $newLink->setAttribute('href', $base_url . '/search/?P=' . $dataCodeBubble);

        // Replace the 'span' element with the new 'a' element
        $span->parentNode->replaceChild($newLink, $span);
        error_log('Replaced code_bubble span with link: ' . $spanContent);
    }

    // Extract only the body content
    $body = $dom->getElementsByTagName('body')->item(0);
    $newContent = '';
    foreach ($body->childNodes as $child) {
        $newContent .= $dom->saveHTML($child);
    }

    // readding the &amp; back into the data-code-bubble attribute.   See above for more info
    $newContent = str_replace('[AMPERSAND]', '&amp;', $newContent);

    return $newContent;
}

function uwguide_adjust_tags($dom, $element, $adjust_tags)
{
    // error_log('uwguide_adjust_tags called');
    foreach ($adjust_tags as $tag_pair) {
        if ($element->tagName === $tag_pair['first_tag']) {
            // Create a new element with the second tag
            $newElement = $dom->createElement($tag_pair['second_tag']);

            // Clone all child nodes and attributes to the new element
            foreach ($element->childNodes as $child) {
                $newElement->appendChild($child->cloneNode(true));
            }
            foreach ($element->attributes as $attribute) {
                $newElement->setAttribute($attribute->name, $attribute->value);
            }

            // Replace the old element with the new element
            $element->parentNode->replaceChild($newElement, $element);
            break; // Break the loop after the replacement
        }
    }
}


// It'd be nice to clean up the CPTs when the block is removed or modified, but it's difficult to do it with percision.   
// it's pretty easy to remove all CPTs with just this post id, not sure if that's a good idea or not
// // Managing removing the CPTs when the block is removed
// function uwguide_remove_cpt_entry($post_id) {
//     // Check if the current save is for the specific post type you're interested in
//     if (get_post_type($post_id) !== 'uw-guide') {
//         return;
//     }

//     // Check if the ACF block is still present
//     $post_content = get_post_field('post_content', $post_id);
//     if (strpos($post_content, 'uw-guide') === false) {
//         // The ACF block has been removed, delete the associated CPT entry
//         $cpt_post_id = get_post_meta($post_id, 'guide_section_cpt_id', true);
//         if (!empty($cpt_post_id)) {
//             wp_delete_post($cpt_post_id, true);
//         }
//     }
// }
// add_action('save_post', 'uwguide_remove_cpt_entry');




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


function unwrap_tags($xpath, $dom, $tags)
{
    // echo print_r($tags);
    foreach ($tags as $tagName) {
        $elements = $xpath->query("//{$tagName}");
        foreach ($elements as $element) {
            // Create a document fragment to hold the inner HTML
            $fragment = $dom->createDocumentFragment();
            while ($element->childNodes->length > 0) {
                $fragment->appendChild($element->childNodes->item(0));
            }
            // Replace the element with its content
            $element->parentNode->replaceChild($fragment, $element);
        }
    }
}

/**
 * Extracts content from a DOM based on the position relative to specified <h> tags.
 * 
 * This function allows the extraction of content that is either before, after, or between specified <h> tags within the document.
 * It uses XPath to locate the <h> tags by their text content and then fetches the nodes according to the specified mode.
 *
 * @param DOMDocument $dom The DOMDocument object containing the HTML structure.
 * @param DOMXPath $xpath The DOMXPath object used for querying the DOM.
 * @param string $tag The type of heading tag to look for (e.g., 'h2', 'h3').
 * @param string $title The text content of the heading tag to serve as the anchor for locating content.
 * @param string $mode Determines the content extraction mode: 'before', 'after', or 'between'.
 * @return string Returns the extracted HTML content as a string, or an error message if conditions are not met.
 */

function extract_content_by_heading($dom, $xpath, $tag, $title, $mode)
{
    $query = "//{$tag}[contains(., '{$title}')]";
    $headers = $xpath->query($query);
    $content = '';

    if ($headers->length > 0) {
        switch ($mode) {
            case 'before':
                $query = ".//node()[preceding-sibling::{$tag}[contains(., '{$title}')]]";
                break;
            case 'after':
                $query = ".//node()[following-sibling::{$tag}[contains(., '{$title}')]]";
                break;
            case 'between':
                if ($headers->length > 1) {
                    $first = $headers->item(0);
                    $second = $headers->item(1);
                    $query = ".//node()[following-sibling::{$tag}[contains(., '{$title}')][1] and preceding-sibling::{$tag}[2]]";
                } else {
                    return 'Not enough headers to define a range.';
                }
                break;
            default:
                return 'Invalid mode specified.';
        }

        $nodes = $xpath->query($query, $dom->documentElement);
        foreach ($nodes as $node) {
            $content .= $dom->saveHTML($node);
        }
    } else {
        return 'Header not found.';
    }

    return $content;
}

// Function to handle the shortcode
function uw_guide_shortcode($atts)
{
    echo error_log('uw_guide_shortcode called');
    // Default attributes
    $atts = shortcode_atts(
        array(
            'shortcode_id' => '', // Default shortcode_id is an empty string
        ),
        $atts,
        'uw-guide'
    );

    // Get the block_id from the shortcode attributes
    $shortcode_id = $atts['shortcode_id'];

    // Check if block_id is provided
    if (empty($shortcode_id)) {
        return 'No shortcode_id provided.';
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
    // echo error_log('Query: ' . print_r($query, true));

    // Check if any post was found
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            // Customize your output as needed

            $output = get_the_content(); // For example, returning the title of the post
        }
        wp_reset_postdata(); // Reset Post Data after the loop
    } else {
        $output = 'No posts found with that shortcode_id.';
    }

    return $output;
}

// Register the shortcode with WordPress
add_shortcode('uw-guide', 'uw_guide_shortcode');
