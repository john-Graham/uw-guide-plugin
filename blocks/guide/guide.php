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
if (!empty($block['anchor'])) {
    $anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}

// Only allow and force align to be full
$block['align'] == 'full';

// WP block settings
$classes = array('acf-guide', $block['id'], 'position-relative');
if (!empty($block['className'])) {
    $classes = array_merge($classes, explode(' ', $block['className']));
}
if (!empty($block['align'])) {
    $classes[] = 'align' . $block['align'];
}
if (empty($block['className']) || (isset($block['className']) && !preg_match('(m[tebsxy]{0,1}?-n?\d)', $block['className']))) {
    $classes[] = 'my-5';
}
if ((empty($block['className']) || (isset($block['className']) && !preg_match('(p[tebsxy]{0,1}?-n?\d)', $block['className']))) && (isset($block['align']) && $block['align'] == 'full')) {
    $classes[] = 'py-5';
}

$container_classes = get_field('block_wrapper_class') ? explode(' ', get_field('block_wrapper_class')) : array();
if ($block['align'] == 'full') {
    $container_classes[] = get_theme_mod('theme_container_type');
}

$styles = array();
if (!empty($block['gradient'])) {
    $styles[] = 'background: var(--wp--preset--gradient--' . $block['gradient'] . ')';
}
if (!empty($block['style'])) {
    if (!empty($block['style']['color']['gradient'])) {
        $styles[] = 'background: ' . $block['style']['color']['gradient'];
    }
}

// Block configuration settings
$last_modified = get_field('last_modified');
$url = get_field('url');
$section = get_field('section');
$find_replace = get_field('find_replace');
$adjust_tags = get_field('adjust_tags');
$graduate_section = get_field('graduate_section');
$block_id = get_field('shortcode_id'); //calling it block_id when getting from the block, shortcode_id when getting from the CPT

$original_unwrap_tags = get_field('remove_tags');
$unwrap_tags = is_array($original_unwrap_tags) ? array_map(function ($item) {
    return $item['tag_type'];  // Extract the tag_type from each sub-array
}, $original_unwrap_tags) : [];

$h_select = array(
    'select_heading' => get_field('select_heading'),  // Example values
    'select_direction' => get_field('select_direction'),
    'select_title' => get_field('select_title')
);


?>

<?php

// Load the value of 'rendor_in_admin_only' field.
$rendor_in_admin_only = get_field('rendor_in_admin_only');

// Check if we are in the admin preview or if the content should always be displayed
if (!$rendor_in_admin_only || $is_preview) {


    // Display admin-specific message at the top if in admin preview and content is admin-only
    if ($is_preview && $rendor_in_admin_only) {
        echo '<div style="background-color:#ccc; padding:1rem;">  <p><strong>Admin Only:</strong> This block is set to display only when editing. It creates a shortcode for use on this page. You can place this block anywhere, but its suggested to place it at the bottom out of the way. To display it on the page, use the shortcode: [uw-guide id="' . $block_id . '"]</p>';
    }

?>
    <div <?php echo $anchor; ?> class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo $styles ? ' ' . esc_attr(implode(';', $styles)) : ''; ?>">
        <?php if ($container_classes) : ?><div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>"><?php endif; ?>

            <?php
            // Now run uwguide_entry using the shortcode_id
            $cpt_post_id = uwguide_entry($url, $section, $find_replace, $adjust_tags, $post_id, $unwrap_tags, $block_id, $h_select);

            if ($is_preview && empty($cpt_post_id)) {
                echo '<p>There is no content for this block.  Please check the settings and try again.</p>';
            }

            ?>

            <?php if ($container_classes) : ?></div><?php endif; ?>
    </div>
<?php
    if ($is_preview && $rendor_in_admin_only) {
        echo '</div>';
    }
} // This closes the initial condition check

?>


