<?php

namespace BricBreakdance;


class AnalyticsBreakdanceSubmissionTracking {
    

    /**
     *  Instance
     * 
     * @var object
     */
    public static $instance;



    public function __construct() {


        add_action( 'plugins_loaded', [ $this, 'init' ] );


        add_action( 'wp_DISABLED', function() {

            if ( isset( $_GET['nrs'] ) ) {

                $this->track_breakdance_submission_analytics_wp( 226, get_post( 226 ), false );
            }

        });


    }




    public function init( ) {

        if ( self::is_analyticswp_installed() ) {

            //Remove the built-in breakdance action
            //        add_action('wp_insert_post', '\Breakdance\Partners\AnalyticsWP\breakdance_form_submission_handler', 10, 3);

            remove_action( 'wp_insert_post', '\Breakdance\Partners\AnalyticsWP\breakdance_form_submission_handler', 10, 3 );
            add_action( 'wp_insert_post', [ $this, 'track_breakdance_submission_analytics_wp' ], 11, 3 );


        }

    }




    public function track_breakdance_submission_analytics_wp( $post_id, $post, $update ) {

        if ($post->post_type == 'breakdance_form_res') {

            $email = '';

            //Get the email address of submission
            $submission_meta = \Breakdance\Forms\Submission\getMeta( $post_id );

            foreach( $submission_meta['fields'] as $key => $field ) {


                if ( $key == 'email' || strpos( $key, 'email' ) !== false ){
                    
                    if ( is_email( $field ) ) {

                        $email = $field;

                        break;

                    }
                }

            }


            dlog( $email, 'email' );


            \AnalyticsWP\Lib\Event::track_server_event(
                'breakdance_form_submission',
                [
                    'user_email' => $email,
                    //'user_id' => 4,
                    'unique_event_identifier' => \Breakdance\Partners\AnalyticsWP\get_unique_event_identifier_for_form_submission($post_id),
                ]
            );
        }

    }



    public static function is_analyticswp_installed()
    {
        return class_exists('\AnalyticsWP\Lib\Core');
    }






    public static function get_instance(){

        if ( self::$instance == null ) {

            self::$instance = new self;
        }

        return self::$instance;
    }

}


AnalyticsBreakdanceSubmissionTracking::get_instance();