<?php

namespace BricBreakdanceHotFixes;

/**
 * Hot fix for ACF relationship field ordering when using 'acf_field' orderby in Breakdance Query Control
 * 
 * It appears that the Breakdance Query Control 'acf_field' orderby option 
 * does not respect the order set in the ACF relationship field. As a workaround,
 * we modify the query to use 'post__in' ordering, which respects the order of
 * IDs provided in the 'post__in' parameter. If the user has selected DESC order,
 * we reverse the array of IDs to maintain the intended order.
 * 
 */

add_filter( 'breakdance_query_control_query', function( $query ) {
    // A Text query arrives as a query string, not an array; leave it untouched.
    if ( ! is_array( $query ) || ( $query['orderby'] ?? '' ) !== 'acf_field' ) {
        return $query;
    }

    $query['orderby'] = 'post__in';

    if ( ( $query['order'] ?? '' ) === 'DESC' && is_array( $query['post__in'] ?? null ) ) {
        $query['post__in'] = array_reverse( $query['post__in'] );
    }

    return $query;
});