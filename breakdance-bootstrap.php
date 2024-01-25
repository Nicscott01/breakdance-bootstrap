<?php
/**
 *  Plugin Name: Breakdance Bootstrap
 *  Description: Some baseline additions for websites built with Breakdance.
 *  Author: Nic Scott
 *  Version: 0.1
 * 
 * 
 */

  


 class BreakdanceBS {


    static $instance;



    public function __construct() {


        add_action( 'init', [ $this, 'theme' ] );

        add_shortcode( 'website_copyright', [ $this, 'website_copyright_sc'] );
        add_shortcode( 'page_title', [ $this, 'page_title_sc'] );
        add_shortcode( 'list_terms', [ $this, 'list_terms_sc' ] );
        add_shortcode( 'singular_plural', [ $this, 'singular_plural_sc' ] );


        add_filter( 'get_the_archive_title', [ $this, 'get_the_archive_title' ]);

    }



    /**
     *  Add some theme support
     * 
     * 
     */


    public function theme() {

        add_theme_support( 'custom-logo' );
        
    }




    /**
     *  Use Page for Posts title for Archive Page
     * 
     * 
     */

    public function get_the_archive_title( $title ) {

        if ( ( get_post_type() == 'post' ) ) {
  
            //Get post page
            $page_for_posts = get_option( 'page_for_posts' );
            
            $pfp = get_post( $page_for_posts );
            
            return $pfp->post_title;
            
        } 
   
        return $title;

    }





    /**
     *  Add a copyright text generator
     * 
     * 
     * 
     */


    public function website_copyright_sc( $atts ) {

        $atts = shortcode_atts( [
            'start_year' => '',
            'company_name' => '',
        ], $atts );

        extract( $atts );




        $today = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );



        if ( empty( $start_year ) ) {
            
            $start_year = $today->format( 'Y' );

        }

        if ( empty( $company_name ) ) {

            $company_name = get_option( 'blogname' );
        }


        if ( $start_year == $today->format( 'Y' ) ) {
           
            $year_string = $start_year;

        } else {

            $year_string = sprintf( '%s-%s', $start_year, $today->format('Y') );
        }

        return sprintf( 'Copyright &copy; %s %s. All Rights Reserved.', $year_string, $company_name );


    }







    /**
     *  Get the Page Title
     * 
     *  
     */

    public function page_title_sc( $atts ) {

        $atts = shortcode_atts( [], $atts );


        global $post;



        if ( is_home() )  {

            $page_for_posts = get_post( get_option( 'page_for_posts' ) );

            return $page_for_posts->post_title;

        } elseif ( is_archive() ) {

            $archive_title = get_the_archive_title(  );

            return $archive_title;

        } elseif ( !empty( $post->post_title ) ) {

            return $post->post_title;

        }


        return 'There was an error returning the title.';
        
    }





    /**
     *  List Terms In a Tax
     * 
     * 
     */

    
    public function list_terms_sc( $atts ) {

        $atts = shortcode_atts( [
            'taxonomy' => 'post_cat',
            'post_id' => get_the_ID(),
            'link' => 'true',
            'class' => '',
            'icon' => '',
        ], $atts);


        $terms = wp_get_object_terms( $atts['post_id'], $atts['taxonomy'] );

        ob_start();

        if ( !is_wp_error( $terms ) && !empty( $terms ) ) {


            //var_dump( $terms );
            printf( '<ul class="terms-list %s">', $atts['class'] );

            foreach( $terms as $term ) {


                $term_text = $term->name;

                if ( !empty( $atts['icon'] ) ) {

                    if ( function_exists( 'get_field' ) ) {

                        $icon = get_field( $atts['icon'], $term );
                        
                        if ( !empty( $icon ) ) {
        
                            $term_text = sprintf( '<span class="icon">%s</span>%s',  wp_get_attachment_image( $icon['id'], 'full', true, [] ), $term_text );

                        }
                    }

                }




                if ( $atts['link'] == 'true' ) {

                    $term_html = sprintf( '<a href="%s">%s</a>', get_term_link( $term, $atts['taxonomy'] ), $term_text );

                } else {

                    $term_html = $term_text;
                }

                printf( '<li>%s</li>', $term_html );

            }

            echo '</ul>';

        }

        $output = ob_get_clean();


        return $output;


    }






    /**
     *  Singular Plural Shortcode
     *  
     * 
     */


    public function singular_plural_sc( $atts ) {

        $atts = shortcode_atts( [
            'singular' => '',
            'plural' => '',
            'field' => '',
            'id' => get_the_ID()
        ], $atts );


        if ( empty( $atts['singular'] ) || empty( $atts['plural'] ) || empty( $atts['field'] ) ) {

            return 'You must have shortcode attributes for singular, plural and field.';

        }

        //Get the field
        $field = get_field( $atts['field'], $atts['id'] );

        if ( !empty( $field ) && is_array( $field ) ) {

            $count = count( $field );

            return $count > 1 ? $atts['plural'] : $atts['singular'];

        } else {

            return 'The field was blank or invalid.';
        }


    }













    /**
     *  Get Instance
     * 
     * 
     */
    public static function get_instance() {

        if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

 }




BreakdanceBS::get_instance();