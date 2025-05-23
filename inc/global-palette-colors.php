<?php

namespace BricBreakdance\ColorPalette;



/**
 * 
 * Register Breakdance Global Colors for Gutenberg.
 * TODO: Breakdance doesn't require you to put colors into the palette first. So Primary and heading, text, etc.
 * can be additional values. Right now were' only looking at the palette.
 */
add_action( 'init', function() {

    // Bail if Breakdance's GlobalStylesStore isn't available.
    if ( ! function_exists( '\Breakdance\Data\get_global_settings_array' ) ) {
        return;
    }

    $global_breakdance_settings = \Breakdance\Data\get_global_settings_array();
    
    if ( ! isset( $global_breakdance_settings['settings']['colors'] ) || 
        ! isset( $global_breakdance_settings['settings']['colors']['palette'] ) ||
        ! isset( $global_breakdance_settings['settings']['colors']['palette']['colors'] ) ||
        ! is_array( $global_breakdance_settings['settings']['colors']['palette']['colors'] ) 
    ) {
        return;
    }

    $colors = $global_breakdance_settings['settings']['colors'];

    $palette =  array();

   
    foreach ( $colors['palette']['colors'] as $color ) {
        $palette[] = array(
            'name'  => $color['label'],
            'slug'  => 'bd-palette-' . sanitize_title( $color['label'] ),
            'color' => $color['value'],
        );
    }
    
    \add_theme_support( 'editor-color-palette', $palette );

}, 0 );




add_filter( 'fluent_crm/theme_pref', function( $array ) {

    
    return $array;

}, 10, 1 );