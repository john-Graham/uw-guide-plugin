<?php //guide-content - Settings callbacks

if (!defined('ABSPATH')) exit;


//When the shortcode "[guide_content]" is added to a Wordpress page, it will call the single function below. The shortcode must also include the "url" attribute value.
add_shortcode('guide_content', 'guide_content');

//Single function that is called, and evaluated separately, for each [guide_content] shortcode found on a Wordpress page
function guide_content($atts, $post)
{
    //Extract all of the shortcode attribute values
    extract(shortcode_atts(array('url' => '', 'after' => '', 'before' => '', 'exact' => '', 'udr' => '', 'grad' => '', 'adjust' => '', 'geneds' => ''), $atts));
    // $url = str_replace('index.html', '', trim($atts['url'])); //url attribute value (required)

    $after = !empty($after) ? trim($atts['after']) : '';
    $before = !empty($before) ? trim($atts['before']) : '';
    $exact = !empty($exact) ? trim($atts['exact']) : '';
    $udr = !empty($udr) ? trim($atts['udr']) : '';
    $grad = !empty($grad) ? trim($atts['grad']) : '';
    // $adjust = !empty($adjust) ? trim($atts['adjust']) : '';
    $geneds = !empty($geneds) ? trim($atts['geneds']) : '';

    //Concatenate options to identfy unique ID for building/checking the cache
    $cache = $url . $after . $before . $exact . $udr . $grad . $adjust . $geneds;
    // $previous_cache_contents = get_transient($cache);

    //geneds was deprecated in 1.7, this line serves to approximate the behavior of what geneds="y" used to do
    if ($geneds == 'y') {
        $udr = 'y';
    }


    //Grabs the XML from the page specified in the "url" shortcode attribute loads it into a DOMDocument to be parsed
    $xmlDoc = new DOMDocument();
    if (@$xmlDoc->load($selected_plan) !== false) {
        $xmlDoc->load($selected_plan);
        $x = $xmlDoc->documentElement;

        //Define the array that will hold the HTML returned from the XML
        $content_array = array();


        //Loop through all XML elements on selected Guide page, push HTML into content_array 
        foreach ($x->childNodes as $item) {
            if ($item->nodeName == $selected_tab && preg_match('/[a-zA-Z]/', $item->nodeValue) !== false) {
                $tab = $item->nodeName;
                $content = '<div id="' . $item->nodeName . 'container" class="tab_content" role="tabpanel">' . str_replace('target="_blank"', '', $item->nodeValue) . '</div>';
                // $content  = str_replace('href="/', 'href="' . $institution, $content);
                $content  = str_replace('<img ', '<img style="display: none;"', $content); //Hides all images
                $temp_array = array("tab" => $tab, "content" => $content);
                array_push($content_array, $temp_array);
            }
        }


        //BEFORE ATTRIBUTE: Get all of the content before, but not including, the specific H2 header
        if (!empty($before) && empty($exact)) {
            $before = str_replace(')', '\\)', str_replace('(', '\\(', $before));
            $courseleaf_after = preg_replace('#(.*)(' . $before . '<\/h2>)(.*?)#is', '$2', $courseleaf_parsed);
            $courseleaf_parsed = '' . str_replace($courseleaf_after, '', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('\\(', '(', str_replace('\\)', ')', $courseleaf_parsed));
        }

        //AFTER ATTRIBUTE: Get all of the content after, and including, the specific H2 header
        if (!empty($after) && empty($exact)) {
            $after = str_replace(')', '\\)', str_replace('(', '\\(', $after));
            $courseleaf_after = preg_replace('#(.*)(' . $after . '<\/h2>)#is', '$3', $courseleaf_parsed, -1, $count);
            $courseleaf_after = preg_replace('#(<\/h2>)(.*?)#is', '</h2>', $courseleaf_after);
            $courseleaf_parsed = str_replace('\\(', '(', str_replace('\\)', ')', $courseleaf_after));
        }

        //EXACT ATTRIBUTE: Get only the exact content between the specific H2 header and the H2 header that follows
        if (!empty($exact) && empty($before) && empty($after)) {
            $exact = str_replace(')', '\\)', str_replace('(', '\\(', $exact));
            $courseleaf_parsed = preg_replace('#(.*)(' . $exact . '<\/h2>)#is', ' $3', $courseleaf_parsed, -1, $count);
            $courseleaf_parsed = preg_replace('#(headerid="(.*)<\/h2>)(.*?)#imU', ' ></h2>', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('\\(', '(', str_replace('\\)', ')', $courseleaf_parsed));
        }

        //Insert the Mortarboard Symbol (to indicate courses meeting Gen Ed req) where appropriate, but only if the "University General Education Requirements" is on the page
        if ($selected_tab == 'requirementstext' && strpos($courseleaf_parsed, '* The mortarboard symbol') !== false) {
            $courseleaf_parsed = str_replace('* The mortarboard symbol appears', '* The mortarboard symbol (<img src="' . $plugin_url . 'img/mortarboard.png" height="20" width="20" alt="Mortarboard Symbol">) appears', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('<i class="fa fa-graduation-cap" aria-hidden="true"></i>', '<img src="' . $plugin_url . 'img/mortarboard.png"  height="20" width="20" alt="Mortarboard Symbol">', $courseleaf_parsed);
        }

        //UDR ATTRIBUTE: Automatically hide the University Degree Requirements Section unless udr = 'y'
        if ($selected_tab == 'requirementstext' && strpos($selected_plan, 'undergraduate') !== false && strpos($selected_plan, 'certificate') !== true && $udr != 'y') {
            $courseleaf_parsed = str_replace('name="requirementstext">University Degree Requirements', 'style="display: none;">', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('<tr class="even firstrow"><td class="column0">Total Degree</td>', '<tr style="display: none;">', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('<tr class="odd"><td class="column0">Residency</td>', '<tr style="display: none;">', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('<tr class="even last lastrow"><td class="column0">Quality of Work</td>', '<tr style="display: none;">', $courseleaf_parsed);
        }

        //If the chosen "url" attribute is for a Requirements tab, and if that plan has a named options grid, then reduce the size of the H3 named options to P
        //Default Guide styling adjusts the H3 'View as List' size for the named options, this change is to help it match Guide styling better, without using CSS
        if (strpos($courseleaf_parsed, '<div class="visual-sitemap grid">') !== false) {
            $courseleaf_parsed = str_replace('"><h3>', '"><p>', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('"></a><h3>', '"><p>', $courseleaf_parsed);
            $courseleaf_parsed = str_replace('</h3></a></li>', '</p></a></li>', $courseleaf_parsed);
        }


        //Default Guide styling makes the 'areaheader' and 'areasubheader' in Course Lists bold--need to re-add the bold since no styling is inherited
        $courseleaf_parsed = str_replace('class="courselistcomment area', 'style="font-weight:bold" class="courselistcomment area', $courseleaf_parsed);
        $courseleaf_parsed = str_replace('class="listsum"><td ', 'style="font-weight:bold" class="listsum"><td style="font-weight:bold" ', $courseleaf_parsed);

        //Default Guide styling makes the 'areasubheader' in Course Lists italicized--need to re-add the italics since no styling is inherited
        $courseleaf_parsed = str_replace('class="odd areasubheader', 'style="font-style:italic" class="odd areasubheader', $courseleaf_parsed);
        $courseleaf_parsed = str_replace('class="even areasubheader', 'style="font-style:italic" class="even areasubheader', $courseleaf_parsed);

        //Due to the variety of ways in which the "before" "after" and "exact" shortcodes may be placed within a single or multiple Wordpress Page Elements, there was a potential for a DIV to be left open or closed erroneously. 
        //This section checks each instance of the [guide_content] shortcode and makes sure that it is appropriately contained within a DIV so that it does not affect any other pgae content
        if (substr_count($courseleaf_parsed, '<div') > substr_count($courseleaf_parsed, '</div>')) {
            $courseleaf_parsed .= '</div>';
        } else if (substr_count($courseleaf_parsed, '<div') < substr_count($courseleaf_parsed, '</div>')) {
            $courseleaf_parsed = '<div>' . $courseleaf_parsed;
        }
    } //End if no DOM document found

}


// Starting the process of a guide entry

function uwguide_entry($url, $section, $find_replace, $adjust_tags, $graduate_section, $post_id)
{


    // global $post;
    $current_post_id = $post_id;
    error_log('uwguide_entry called');
    error_log('Post ID: ' . $current_post_id);
    error_log('URL: ' . $url);
    error_log('Section: ' . $section);
    // error_log('current_post_id' . print_r($current_post_id, true));
    // print_r($find_replace);

    // Removing everything after the last '/' including any anchor or query parameters to make sure we're getting the right URL
    $url = preg_replace('/\/[^\/]*$/', '/', $url);

    // Check if the CPT exists
    $result = uwguide_check_if_cpt_exists($url, $section, $current_post_id);

    $content = '';

    if ($result['exists']) {
        if ($result['update_required']) {
            error_log('Updating guide.');

            // Fetch the last modified date from the URL only if needed
            $guide_modified = uwguide_get_url_modified_date($url);
            if (!$guide_modified) {
                error_log('No last modified date found from header of webpage.');
                return;
            }

            // Fetch content and update the CPT
            $content = uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section);
            uwguide_update_cpt($result['post_id'], $url, $section, $guide_modified, $content, $current_post_id);
        } else {
            error_log('CPT exists but does not require an update.');
            $content = get_post_field('post_content', $result['post_id']);
        }
    } else {
        error_log('Creating new guide.');

        // Fetch the last modified date from the URL for new CPT
        $guide_modified = uwguide_get_url_modified_date($url);
        if (!$guide_modified) {
            error_log('No last modified date found from header of webpage.');
            return;
        }

        // Fetch content and create the CPT
        $content = uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section);
        error_log(('calling uwguide_create_cpt current post id is: ' . $current_post_id . ''));
        uwguide_create_cpt($url, $section, $guide_modified, $content, $current_post_id);
    }
    // Echo the content
    echo $content;
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
    $frequency = get_field('update_frequency', 'options');

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


// function uwguide_check_external_guide($url)
// {
//     error_log('uwguide_check_external_guide called');
//     // error_log('Checking URL: ' . $url); // Log for debugging


//     $exists = uwguide_check_guides_cpt($url, $last_modified);

//     if ($exists) {
//         error_log('Guide already exists.');
//     } else {
//         error_log('Creating new guide.');
//         error_log('Last Modified from URL uwguide_check_external_guide: ' . $last_modified);

//         uwguide_get_xml_node($url, 'learningoutcomestext');

//         // Create a new CPT entry
//         $post_id = wp_insert_post(array(
//             'post_title'    => wp_strip_all_tags($url),
//             'post_content'  => '', // add content here
//             'post_status'   => 'publish',
//             'post_type'     => 'uw-guide',
//             'meta_input'    => array(
//                 'url'           => $url,
//                 'last_modified'  => $last_modified,
//             ),
//         ));

//         if ($post_id) {
//             // Update ACF field
//             update_field('last_modified', $last_modified, $post_id);

//             echo "New guide created with ID: " . $post_id;
//         } else {
//             echo "Failed to create a new guide.";
//         }
//     }
// }




// getting only the specific XML node from a guide webpage
// and cleaning the content
function uwguide_get_xml_node($url, $section, $find_replace, $adjust_tags, $graduate_section)
{
    error_log('uwguide_get_xml_node called');

    // Remove everything after the last '/' including any anchor or query parameters
    // MOVED this to the entry function... probably can remove these lines
    // $url = preg_replace('/\/[^\/]*$/', '/', $url);

    // Append 'index.xml' to the URL
    $url .= 'index.xml';

    try {
        $xml = simplexml_load_file($url);
        if ($xml === false) {
            throw new Exception("Cannot load the XML file.");
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }

    // Check if the section exists in the XML
    if (isset($xml->$section)) {
        $sectionContent = $xml->$section;

        // Clean the content
        $sectionContent = uwguide_clean_content($sectionContent, $section, $find_replace, $adjust_tags, $graduate_section);

        // Using CDATA section as a string
        return (string)$sectionContent;
    } else {
        echo "Section not found in the XML.";
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


function uwguide_check_if_cpt_exists($url, $section, $current_post_id)
{
    // may want to change the title / key to include the post ID
    error_log('uwguide_check_if_cpt_exists called');
    // error_log('URL: ' . $url);
    // error_log('Section: ' . $section);
    // error_log('Last Modified: ' . $cpt_modified);

    // Ensure all variables are not empty
    if (empty($url) || empty($section) || empty($current_post_id)) {
        error_log('One or more variables are empty');
        return array(
            'exists' => false
        );
    }

    // Create the title to search for
    $search_title = wp_strip_all_tags($url) . '-' . $section;

    $args = array(
        'post_type'      => 'uw-guide',
        'posts_per_page' => 1,
        'title'          => $search_title,
        'meta_query'     => array(
            array(
                'key'   => 'id_of_post', // Replace with your actual meta key
                'value' => $current_post_id,
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Now check the frequency
            if (uwguide_check_frequency($post_id)) {
                wp_reset_postdata();
                return array(
                    'exists' => true,
                    'update_required' => true,
                    'post_id' => $post_id
                );
            } else {
                wp_reset_postdata();
                return array(
                    'exists' => true,
                    'update_required' => false,
                    'post_id' => $post_id
                );
            }
        }
    } else {
        wp_reset_postdata();
        return array(
            'exists' => false,
            'update_required' => false
        );
    }
}

function uwguide_create_cpt($url, $section, $guide_modified, $content, $current_post_id)
{
    error_log('uwguide_create_cpt called');
    // error_log('URL: ' . $url);
    // error_log('Section: ' . $section);
    // error_log('Guide Modified: ' . $guide_modified);
    // error_log('Content: ' . $content);
    $current_post_id = $current_post_id;
    error_log('Current Post ID: ' . $current_post_id);



    $post_id = wp_insert_post(array(
        'post_title'    => wp_strip_all_tags($url) . '-' . $section, // Concatenate section here
        'post_content'  => $content, // add content here
        'post_status'   => 'publish',
        'post_type'     => 'uw-guide',
        'meta_input'    => array(
            'url'           => $url,
            'section'       => $section,
            'last_modified'  => $guide_modified,
        ),
    ));

    if ($post_id) {
        // Update ACF field
        // update_field('last_modified', $guide_modified, $post_id);
        echo "New guide created with ID: " . $post_id;
        // Update the CPT ID in the post meta so we can remove it later if block is removed see uwguide_remove_cpt_entry
        // not working yet.  need to figure out how to get the post ID of the CPT or store it in meta
        update_post_meta($post_id, 'id_of_post', $current_post_id);
    } else {
        echo "Failed to create a new guide.";
    }
}

function uwguide_update_cpt($post_id, $url, $section, $guide_modified, $content)
{
    error_log('uwguide_update_cpt called for post ID: ' . $post_id);


    // Prepare post data
    $post_data = array(
        'ID'           => $post_id,
        'post_content' => $content, // Update content
        // 'post_title' and other fields can be updated as needed
    );

    // Update the post
    $updated_post_id = wp_update_post($post_data, true);

    if (is_wp_error($updated_post_id)) {
        error_log('Failed to update guide: ' . $updated_post_id->get_error_message());
        return false;
    }

    // Update ACF fields if necessary
    update_field('last_modified', $guide_modified, $post_id);
    // Update other ACF fields as needed

    error_log('Guide updated with ID: ' . $updated_post_id);
    return true;
}


function uwguide_clean_content($content, $section, $find_replace, $adjust_tags, $graduate_section)
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
    $content = uwguide_clean_content_all($content, $adjust_tags);


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

                if ($textContent === 'Mode of Instruction' || $textContent === 'CURRICULAR REQUIREMENTS' || $textContent === 'Required Courses') {
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
    if ($section === 'requirementstext') {
    }
    if ($section === 'policiestext') {
    }
    if ($section === 'professionaldevelopmenttext') {
    }
    if ($section === 'learningoutcomestext') {
    }

    return $content;
}

function uwguide_clean_content_all($content, $adjust_tags)
{
    error_log('uwguide_clean_content_all called');

    // Course names with & in the title are problematic,we're replacing &amp; within data-code-bubble attributes while it's a string
    // and then replacing it back after the DOMDocument has been manipulated into the string
    $content = str_replace('&amp;', '[AMPERSAND]', $content);



    // Wrap content in full HTML structure
    // This is needed to use DOMDocument more reliably
    $fullHtml = '<!DOCTYPE html><html><body>' . $content . '</body></html>';

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($fullHtml, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($dom);

    // Remove elements with class 'hidden'
    // not sure why they are there in first place, may consider switching to display:none in CSS if they are there for ADA reasons
    $hiddenElements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' hidden ')]");
    foreach ($hiddenElements as $element) {
        // Remove the element from its parent
        $element->parentNode->removeChild($element);
    }

    // a loop for all elements, used for adjusting tags and adding classes to tables
    $bootstrapTablesEnabled = get_field('bootstrap_tables', 'options');
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

    // Clean up 'a' hrefs to remove 'target' attribute if it matches the server domain & prepend the base URL to relative URLs
    // $server_domain = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST);
    $server_domain = 'engineering.wisc.edu'; // Hardcoded for debugging

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

    // Check if 'clear_cache' field is set to 'yes'
    $clear_cache = get_field('clear_cache', 'option');
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
        update_field('clear_cache', 'no', 'option');
    }
}
