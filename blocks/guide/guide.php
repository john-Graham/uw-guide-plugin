<?php

/**
 * Guide Block Template
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 *          This is either the post ID currently being displayed inside a query loop,
 *          or the post ID of the post hosting this block.
 * @param   array $context The context provided to the block by the post or its parent block.
 */

// Support custom "anchor" values.
$anchor = '';
if (!empty($block['anchor'])) :
    $anchor = 'id="' . esc_attr($block['anchor']) . '" ';
endif;
// echo print_r($block, true);
// echo '<br><br>';
// echo print_r($context, true);

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

// block configuration settings
// $last_modified = get_field('last_modified');
// echo 'Post ID: ' . $post_id . '<br>';
// $last_modified = get_post_meta($post_id, 'last_modified', true);
$url = get_field('url');
$section = get_field('section');
$find_replace = get_field('find_replace');
$adjust_tags = get_field('adjust_tags');
$graduate_section = get_field('graduate_section');
// $cpt_modified = get('last_modified', $post_id);
// Concatenate to form the full URL with the section as an anchor
// $full_url = $url . '#' . $section;
?>


    <div <?php echo $anchor; ?>class="<?php echo esc_attr(implode(' ', $classes)); ?>" <?php if ($styles) : ?> style="<?php echo esc_attr(implode(';', $styles)); ?>" <?php endif; ?>>
        <?php if ($container_classes) : ?><div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>"><?php endif; ?>


            <!-- <div>Guide entry</div> -->
            <?php
            echo '<!-- START Content copied from ' . $url . '#' . $section . ' -->';
            uwguide_entry($url, $section, $find_replace, $adjust_tags, $graduate_section, $post_id);
            echo '<!-- END Content copied from ' . $url . '#' . $section . ' -->';
            ?>

            <?php if ($container_classes) : ?>
            </div><?php endif; ?>
    </div>
