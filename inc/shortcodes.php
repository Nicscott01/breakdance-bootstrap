<?php



add_shortcode( 'time_diff', 'time_diff_sc' );

function time_diff_sc( $atts ) {
    
    $atts = shortcode_atts( [
        'date' => null,
        'compare_to' => 'now',
        'timezone' => wp_timezone_string(),
        'return_format' => 'y',
    ], $atts );
    
    
    if ( !empty( $atts['date'] ) ) {
        
        $start_date = new DateTime( $atts['date'], new DateTimeZone( $atts['timezone'] ) );

        $now = new DateTime( $atts['compare_to'], new DateTimeZone( $atts['timezone']  ) );

        $diff = $now->diff( $start_date );

        if ( $diff->$atts['return_format'] > 0 ) {
            
            return strval( $diff->$atts['return_format'] );
        
        } else {
            
            return 'Less than 1';
            
        }
         
        

        
        
    }
    
    return '';
    
    
}