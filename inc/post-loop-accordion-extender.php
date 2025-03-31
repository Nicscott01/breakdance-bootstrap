<?php

namespace BricBreakdance\PostLoopAccordionExtender;
use function Breakdance\LoopBuilder\Accordion\isAccordionLayoutActive;

/**
 * Add action to begin our hook of the Accordion title
 * 
 */
add_action( 'breakdance_before_loop_item', function( $object, $objectType, $data ) {

    //Check that we're in a BD accordion loop item
    if (!isAccordionLayoutActive($data['propertiesData'])) {
        return;
    }

    //Do this for posts only (maybe add terms later?)
    if ($object instanceof \WP_Post ) {

        add_filter( 'the_title', __NAMESPACE__ . '\add_content_to_title', 10, 2 );
        
    } 

}, 1, 3 );


/**
 * Our the_title callback
 */
function add_content_to_title( $post_title, $post_id ) {
      
    $accordion_title = apply_filters( 'bricbd_accordion_title', $post_title, $post_id ); //get_field( 'title', $post_id );
     
    //Shut off the filter so it doesn't mess up anything else
    remove_filter( 'the_title', __NAMESPACE__ . '\add_content_to_title', 10, 2 );

    return $accordion_title;

}
