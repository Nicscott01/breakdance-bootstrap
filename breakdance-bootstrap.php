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

    public $loaded_scripts;


    public function __construct() {


        add_action( 'init', [ $this, 'theme' ] );

        add_shortcode( 'website_copyright', [ $this, 'website_copyright_sc'] );
        add_shortcode( 'page_title', [ $this, 'page_title_sc'] );
        add_shortcode( 'list_terms', [ $this, 'list_terms_sc' ] );
        add_shortcode( 'singular_plural', [ $this, 'singular_plural_sc' ] );
        add_shortcode( 'website_author', [ $this, 'website_author_sc' ] );

        

        add_filter( 'breakdance_render_rendered_html', [ $this, 'load_scripts_based_on_class' ], 10, 3 );
        add_filter( 'breakdance_render_rendered_html', [ $this, 'breakdance_search_replace_rendered_html' ], 10, 3 );


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

        $atts = shortcode_atts( [
            'archive_title' => false,
        ], $atts );


        global $post;



        if ( is_home() )  {

            $page_for_posts = get_post( get_option( 'page_for_posts' ) );

            return $page_for_posts->post_title;

        } elseif ( is_archive() ) {

            $archive_title = get_the_archive_title(  );

            return $archive_title;

        } elseif ( !empty( $post->post_title ) ) {

            if ( $atts['archive_title'] ) {

                if ( $post->post_type == 'post' ) {

                    $page_for_posts = get_post( get_option( 'page_for_posts' ) );

                    $archive_title = $page_for_posts->post_title;

                }

                return $archive_title;

            } else {
                
                return $post->post_title;

            }



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
     *  Get the Website Author using the constants
     * 
     * 
     */

     public function website_author_sc( $atts ) {

        $atts = shortcode_atts( [
            'developer_name' => defined( 'DEVELOPER_NAME' ) ? DEVELOPER_NAME : 'Website Author Name',
            'developer_url' => defined( 'DEVELOPER_URL' ) ? DEVELOPER_URL : "#",
            'pre_text' => 'Website&nbsp;by&nbsp;'
        ], $atts );


        return sprintf( '%s <a href="%s" target="_blank">%s</a>', $atts['pre_text'], $atts['developer_url'], $atts['developer_name'] );


     }







     /**
      *  Load Scripts based on certain classes appearing
      */

    public function load_scripts_based_on_class( $html, $post_id, $node_id ) {

        //Don't do this if we're in a repeater
        if ( !empty( $node_id ) ) {
            return $html;
        }


        //Do it for accordion. We assume that our selector is unique enough to not set off a false positive. but it could happen
        // TODO: regex for class="... bs-accordion ..." and then make that our conditional
        if ( strpos( $html, 'bs-accordion' ) !== false ) {

            wp_enqueue_script( 'bs-partial', plugin_dir_url( '/breakdance-bootstrap' ) . 'breakdance-bootstrap/assets/js/bootstrap-partial.min.js', [], '1', true );
            wp_enqueue_style( 'bs-accordion', plugin_dir_url( '/breakdance-bootstrap') . 'breakdance-bootstrap/assets/css/accordion.min.css' );

            //Keeping track but I don't think we need to
            $this->loaded_scripts['bootstrap-partial'] = 1;
        }


        return $html;

    }



     /**
      *  Replace placeholders with dynamic text
      *     
      */

     public function breakdance_search_replace_rendered_html( $html, $post_id, $repeater_item_node_id ) {



        //Only do things if this is a repeater item
        if ( $repeater_item_node_id ) {

            //Replace nodeId with unique ID of the item (for things that need uniqueness)
            //Using camel case becuase {node_id} doesn't work for id field in BD
            $html = str_replace( 'nodeId', "$repeater_item_node_id", $html );

            //Look for bde-button element with "has-collapse-button"
            if ( strpos( $html, 'has-collapse-button' ) !== false ) {
                $html = str_replace( 'button-atom ', 'button-atom collapsed ', $html );
            }


        }


        return $html;

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