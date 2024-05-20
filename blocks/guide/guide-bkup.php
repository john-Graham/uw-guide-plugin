<?php

/**
 * Guide Block Template
 *
 * @param array $block The block settings and attributes.
 * @param string $content The block inner HTML (empty).
 * @param bool $is_preview True during backend preview render.
 * @param int $post_id The post ID the block is rendering content against.
 *          This is either the post ID currently being displayed inside a query loop,
 *          or the post ID of the post hosting this block.
 * @param array $context The context provided to the block by the post or its parent block.
 */

// Support custom "anchor" values.
$anchor = '';
if (!empty($block['anchor'])) :
    $anchor = 'id="' . esc_attr($block['anchor']) . '" ';
endif;

// Only allow and force align to be full
$block['align'] == 'full';

// WP block settings
$classes = array('acf-guide', $block['id'], 'position-relative');
if (!empty($block['className'])) :
    $classes = array_merge($classes, explode(' ', $block['className']));
endif;
if (!empty($block['align'])) :
    $classes[] = 'align' . $block['align'];
endif;
if (empty($block['className']) || (isset($block['className']) && !preg_match('(m[tebsxy]{0,1}?-n?\d)', $block['className']))) :
    $classes[] = 'my-5';
endif;
if ((empty($block['className']) || (isset($block['className']) && !preg_match('(p[tebsxy]{0,1}?-n?\d)', $block['className']))) && (isset($block['align']) && $block['align'] == 'full')) :
    $classes[] = 'py-5';
endif;

$container_classes = get_field('block_wrapper_class') ? explode(' ', get_field('block_wrapper_class')) : array();
if ($block['align'] == 'full') :
    $container_classes[] = get_theme_mod('theme_container_type');
endif;

$styles = array();
if (!empty($block['gradient'])) :
    $styles[] = 'background: var(--wp--preset--gradient--' . $block['gradient'] . ')';
endif;
if (!empty($block['style'])) :
    if (!empty($block['style']['color']['gradient'])) :
        $styles[] = 'background: ' . $block['style']['color']['gradient'];
    endif;
endif;

// Block configuration settings
$last_modified = get_field('last_modified');
$url = get_field('url');
$section = get_field('section');
$find_replace = get_field('find_replace');
$adjust_tags = get_field('adjust_tags');
$graduate_section = get_field('graduate_section');

$original_unwrap_tags = get_field('remove_tags');
$unwrap_tags = is_array($original_unwrap_tags) ? array_map(function ($item) {
    return $item['tag_type'];  // Extract the tag_type from each sub-array
}, $original_unwrap_tags) : [];

$h_select = array(
    'select_heading' => get_field('select_heading'),  // Example values
    'select_direction' => get_field('select_direction'),
    'select_title' => get_field('select_title')
);

// // Check if the shortcode_id is present; if not, create a new CPT
// $shortcode_id = get_field('shortcode_id');
// if (empty($shortcode_id)) {
//     echo error_log('shortcode_id is empty, creating new CPT');
//     // If no shortcode_id, generate one and create a new CPT
//     $shortcode_id = wp_generate_uuid4();

//     // Create the new CPT
//     $content = ''; // If you need to fetch content for the new CPT, do it here
//     $cpt_post_id = uwguide_create_cpt($url, $section, $last_modified, $content, $post_id, $shortcode_id); // Function to create CPT and return its post ID

//     echo error_log('post_id of initial create is ' . $cpt_post_id);

//     // Save the generated shortcode_id back to the block's field using the field key
//     update_field('shortcode_id', $shortcode_id, $post_id);

//     // Save the shortcode_id to the newly created CPT
//     update_post_meta($cpt_post_id, 'shortcode_id', $shortcode_id);
// } else {
//     echo error_log('shortcode_id found: ' . $shortcode_id);
// }
?>

<?php

// Load the value of 'rendor_in_admin_only' field.
$rendor_in_admin_only = get_field('rendor_in_admin_only');

// Check if we are in the admin preview or if the content should always be displayed
if (!$rendor_in_admin_only || $is_preview) {

    // Additional style for admin preview
    $admin_style = $is_preview ? 'background-color: #f7f7f7;' : '';

?>
    <div <?php echo $anchor; ?> class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo $admin_style;
                                                                                                echo $styles ? ' ' . esc_attr(implode(';', $styles)) : ''; ?>">
        <?php if ($container_classes) : ?><div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>"><?php endif; ?>

            <?php
            // Now run uwguide_entry using the shortcode_id
            // $cpt_post_id = uwguide_entry($url, $section, $find_replace, $adjust_tags, $graduate_section, $post_id, $unwrap_tags, $h_select, $shortcode_id);

            // Display admin-specific message at the top if in admin preview and content is admin-only
            if ($is_preview && $rendor_in_admin_only) {
                echo '<p><strong>Admin Only:</strong> To use this guide with a shortcode, use: [uw-guide id="' . $shortcode_id . '"]</p>';
            }

            ?>

            <?php if ($container_classes) : ?></div><?php endif; ?>
    </div>
<?php
} // This closes the initial condition check

// echo do_shortcode('[uw-guide id=”block_915f33af0798d5ce196d516e45c520fb”]');
?>
