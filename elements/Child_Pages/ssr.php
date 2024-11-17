<?php
global $post;



//Get child pages and display them

$order = $propertiesData['content']['data']['order'] ?? 'date';
$orderby = $propertiesData['content']['data']['order_by'] ?? 'DESC';

if ( $propertiesData['content']['data']['source'] == 'custom_query' ) {

    $args = \Breakdance\WpQueryControl\getWpQueryArgumentsFromWpQueryControlProperties($propertiesData['content']['data']['query']);

} else {

    $args = [
        'posts_per_page' => -1,
        'post_parent' => $post->ID,
        'post_type' => $post->post_type,
        'order' => $order,
        'orderby' => $orderby
    ];
}

$children = get_posts( $args );


if ( !empty( $children ) ) {

    $show_featured_image = $propertiesData['content']['background']['featured_image'];
    $image_size = $propertiesData['content']['background']['featured_image_size'];
    //var_dump( $propertiesData ); 


    echo '<ul class="bric-child-pages--children">';

    foreach( $children as $child ) {

        $featured_image_inline_css = $show_featured_image ? "style=\"background-image:url( " .get_the_post_thumbnail_url( $child, $image_size ) . ")\"" : "";

        $title = get_the_title( $child );

        printf( '<li class="bric-child-pages--child" %s><div class="section-background-overlay"></div><a href="%s"><h2>%s</h2></a></li>', $featured_image_inline_css, get_permalink( $child ),  $title );


    }

    echo '</ul>';

}