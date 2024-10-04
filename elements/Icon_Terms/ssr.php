<?php
global $post;

$taxo = $propertiesData['content']['controls']['taxonomy'] ?? '';

if ( empty( $taxo ) ) {
    echo '<pre>Please set a taxonomy</pre>';
    return;
}


$terms = $propertiesData['content']['controls']['terms'] ?? [];

if ( empty( $terms ) ) {
    echo '<pre>Please set some terms and icons.</pre>';
    return;
}


//See if the post has terms
$post_terms = wp_get_post_terms( $post->ID, $taxo, ['fields'=>'ids'] );

//var_dump( $post_terms );
//var_dump( $terms );



echo '<ul class="icon-terms-list">';

foreach ( $terms as $term ) {

    if ( in_array( $term['term'], $post_terms ) ) {
        printf( '<li><a href="%s"><div class="icon breakdance-icon-atom">%s</div></a></li>', get_term_link( (int) $term['term'], $taxo ), $term['icon']['svgCode'] );
    }
}

echo '</ul>';