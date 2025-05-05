<?php
/**
 * SSR file for the Table of Contents for SEO element.
 */

 namespace Bric\Breakdance\Elements\Table_of_Contents_for_SEO;
 use function BricBreakdance\Table_of_Contents_for_SEO\get_cached_toc;

 global $post;


$exclude_headings = $propertiesData['content']['data']['exclude_headings'] ?? [];


[ $toc_items, $updated_content ] = get_cached_toc( $post, $exclude_headings );

if ( empty( $toc_items ) ) {
    echo '<p>There was no content found to generate a table of contents.</p>';
}

if ( $updated_content !== $post->post_content ) {
        
    add_filter( 'the_content', function ( $content ) use ( $updated_content ) {
        return $updated_content;
    }, 5 );

}

echo '<nav class="bric-toc"><ul>';
$prev_level = $toc_items[0]['level'];

foreach ( $toc_items as $index => $item ) {
    $curr_level = $item['level'];
    $next_level = isset( $toc_items[ $index + 1 ] ) ? $toc_items[ $index + 1 ]['level'] : $curr_level;

    // Open list item
    printf(
        '<li class="toc-level-%d"><a href="%s">%s</a>',
        $curr_level,
        esc_attr( $item['anchor'] ),
        esc_html( $item['text'] )
    );

    // Going deeper
    if ( $next_level > $curr_level ) {
        echo '<ul>';
    }

    // Going shallower
    if ( $next_level < $curr_level ) {
        echo str_repeat( '</li></ul>', $curr_level - $next_level );
        echo '</li>'; // close current item
    }

    // Same level
    if ( $next_level === $curr_level ) {
        echo '</li>';
    }

    $prev_level = $curr_level;
}
echo '</ul></nav>';