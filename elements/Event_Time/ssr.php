<?php


//var_dump( $propertiesData );

$times_keys = [
    'start_time',
    'end_time'
];

$times = [];


/**
 * Check to see if we're using dynamic data
 * 
 * We have to do this for the builder/editor because it otherwise spits out
 * the breakdance shortcode for the data.
 * 
 */

foreach ( $times_keys as $time_key ) {

    if ( isset( $propertiesData['content']['data'][$time_key .'_dynamic_meta' ] ) && ! empty( $propertiesData['content']['data'][$time_key .'_dynamic_meta' ] ) ) {

        $field_key = $propertiesData['content']['data'][ $time_key . '_dynamic_meta']['field']['slug'];
    
    
        //Only proceed if we have the acf_field prefix
        if ( strpos( $field_key, 'acf_field' ) === 0 ) {
            $acf_field_key = str_replace( 'acf_field_', '', $field_key );
    

            if ( function_exists( 'get_field' ) ) {
        
                $times[ $time_key ] = get_field( $acf_field_key );
                   
    
            }
        }
    



        
    } else {

        $times[$time_key] =  $propertiesData['content']['data'][$time_key];

    }


}


//var_dump( $times );

if ( !empty( $times ) ) {

    $date_format = !empty( $propertiesData['content']['data']['date_format'] ) ? $propertiesData['content']['data']['date_format'] : get_option( 'date_format' );
    $time_format = !empty( $propertiesData['content']['data']['time_format'] ) ? $propertiesData['content']['data']['time_format'] : get_option( 'time_format' );
    $day_time_sep = !empty( $propertiesData['content']['data']['day_time_separator'] ) ? $propertiesData['content']['data']['day_time_separator'] : '';
    $time_sep = !empty( $propertiesData['content']['data']['time_separator'] ) ? $propertiesData['content']['data']['time_separator'] : '';


    foreach( $times as $time_key => $value ) {

        switch( $time_key ) {

            case 'start_time' :

                $start_time = new DateTime( $value, wp_timezone( ) );

                break;

            case 'end_time' : 

                $end_time = new DateTime( $value, wp_timezone( ) );

                break;
        }

    }

    if ( !empty( $day_time_sep ) ) {

        $day_time_sep_html = sprintf('<span class="day-time-separator">%s</span>', $day_time_sep );

    }

    //Same day
    if ( $start_time->format('Y-m-d') == $end_time->format('Y-m-d') ) {


       
        printf( '<div class="date-time-block"><span class="start-date"><span class="date">%s</span>%s</span><span class="time-block"><span class="time">%s</span><span class="time-separator">%s</span><span class="time">%s</span></span></div>', 
        $start_time->format( $date_format ), 
        $day_time_sep_html,
        $start_time->format(( $time_format ) ), 
        $time_sep,
        $end_time->format( $time_format ) );

    } else {

        printf( '<div class="date-time-block"><span class="start-date"><span class="date">%1$s</span>%6$s<span class="time">%2$s</span><span class="time-separator">%3$s</span></span><span class="end-date"><span class="date">%4$s</span>%6$s<span class="time">%5$s</span></span></div>', 
            $start_time->format( $date_format ), $start_time->format( $time_format),
            $time_sep,
            $end_time->format( $date_format ), $end_time->format( $time_format),
            $day_time_sep_html
         );
    }




}