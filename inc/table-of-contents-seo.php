<?php

namespace BricBreakdance\Table_of_Contents_for_SEO;


/**
 * Generates a table of contents from the given content.
 *
 * @param string $content The content to generate the t able of contents from.
 * @param array  $exclude_tags An array of heading tags to exclude from the table of contents.
 *
 * @return array An array containing the headings and the updated content with IDs.
 */
function generate_toc_from_content_with_ids( $content, $exclude_tags = [] ) {
    $matches = [];
    preg_match_all( '/<h([1-6])([^>]*)>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER );

    $headings = [];
    $used_ids = [];

    foreach ( $matches as $match ) {
        $level = (int) $match[1];
        $tag = 'h' . $level;

        if ( in_array( $tag, $exclude_tags ) ) {
            continue;
        }

        $attrs = $match[2];
        $text  = strip_tags( $match[3] );

        // Check if an ID is already present.
        if ( preg_match( '/\bid\s*=\s*["\']([^"\']+)["\']/', $attrs, $id_match ) ) {
            $id = $id_match[1];
        } else {
            $id = sanitize_title( $text );
        }

        // Ensure the ID is unique.
        $original_id = $id;
        $i           = 2;
        while ( in_array( $id, $used_ids ) ) {
            $id = $original_id . '-' . $i++;
        }
        $used_ids[] = $id;

        // Remove any existing ID attribute to avoid duplicates in the new heading.
        $attrs_without_id = preg_replace( '/\bid\s*=\s*["\'][^"\']*["\']/', '', $attrs );

        $old_heading = $match[0];
        $new_heading = sprintf(
            '<h%d%s id="%s">%s</h%d>',
            $level,
            $attrs_without_id ? ' ' . trim( $attrs_without_id ) : '',
            esc_attr( $id ),
            $match[3],
            $level
        );
        $content = str_replace( $old_heading, $new_heading, $content );

        $headings[] = [
            'level'  => $level,
            'text'   => $text,
            'anchor' => '#' . $id,
        ];
    }

    return [ $headings, $content ];
}


/**
 * Get the cached table of contents for a post.
 *
 * @param WP_Post $post The post object.
 *
 * @return array The cached table of contents and updated content.
 */

function get_cached_toc( $post, $exclude_headings = [] ) {

    $cache_key     = '_bric_toc_cache';
    $exclude_hash  = md5( serialize( $exclude_headings ) );
    $current_hash  = md5( $post->post_content . '_' . $exclude_hash );

    $cache = get_post_meta( $post->ID, $cache_key, true );

    if ( is_array( $cache ) && isset( $cache['hash'], $cache['toc'] ) && $cache['hash'] === $current_hash ) {
        return [ $cache['toc'], $post->post_content ];
    }

    // Recalculate
    [ $toc, $updated_content ] = generate_toc_from_content_with_ids( $post->post_content, $exclude_headings );
    
    // Update the post content with the new IDs    
    wp_update_post( [
        'ID'           => $post->ID,
        'post_content' => $updated_content,
    ] );

    update_post_meta( $post->ID, $cache_key, [
        'toc'  => $toc,
        'hash' => $current_hash,
    ] );

    return [ $toc, $updated_content ];
}








/**
 * Add the table of contents to the JSON-LD schema.
 *
 * TODO: The option of excluding headings should be added here as well. Not the worst thing that all headings are included in the schema.
 * 
 * @param array $data The existing JSON-LD data.
 * @param array $jsonld The JSON-LD data being generated.
 *
 * @return array The modified JSON-LD data.
 */
add_filter( 'rank_math/json_ld', function( $data, $jsonld ) {
    if ( is_singular( 'post' ) ) {
        global $post;
       
        // Generate TOC with IDs
        [ $toc_items, $content_with_ids ] = get_cached_toc( $post );

        if ( empty( $toc_items ) ) {
            return $data;
        }

        // Create an ItemList schema object
        $item_list = [
            '@type'            => 'ItemList',
            'name'             => 'Table of Contents',
            'itemListOrder'    => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems'    => count( $toc_items ),
            'itemListElement'  => [],
        ];

        foreach ( $toc_items as $index => $item ) {
            $item_list['itemListElement'][] = [
                '@type'    => 'SiteNavigationElement',
                'position' => (int) ( $index + 1 ),
                'name'     => $item['text'],
                'url'      => get_permalink( $post ) . $item['anchor'],
            ];
        }

        // Inject into schema under a new key (e.g., "toc")
        $data['toc'] = $item_list;
    }

    return $data;
}, 20, 2 );