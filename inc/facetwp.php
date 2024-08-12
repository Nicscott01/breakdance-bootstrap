<?php

namespace BricBreakdance;

class FacetWP {

    /**
     * Singleton Instance
     */
    public static $instance;



    public function __construct() {


        //Add button classes for breakdance support
        add_filter( 'facetwp_facet_html', [ $this, 'button_classes' ], 10, 2 );

    }



    /**
     *  Add Breakdance Classes to FacetWP buttons
     * 
     * 
     * 
     */
	public function button_classes( $output, $params ) {

        if ( 'reset' == $params['facet']['type'] ) {
          $output = str_replace( 'facetwp-reset', 'facetwp-reset button-atom--secondary', $output );
        }

        return $output;

    }








    /**
     * Singleton
     */

    public static function get_instance() {

        if ( self::$instance == null ) {

            self::$instance = new self;
        }

        return self::$instance;
    }

}


if ( true ) { //function_exists( 'facetwp_display' ) ) {

    FacetWP::get_instance();
    
}