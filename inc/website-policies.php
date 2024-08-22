<?php

namespace BricBreakdance;

class WebsitePolicies {

    public static $instance;



    public function __construct()
    {
        include_once( __DIR__ . '/../acf-fields/website-policies.php' );
        

        add_action( 'wp_head', [ $this, 'print_cookie_policy_script' ], 1 );



    }





    public function print_cookie_policy_script() {

        if ( get_option( 'options_enable_cookie_consent' ) ) {

            $cookie_consent_script = get_option( 'options_cookie_consent_scripts' );

            echo $cookie_consent_script;

        }
    }





    public static function get_instance() {

        if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

}


function WebsitePolicies() {

    if ( apply_filters( 'bric_breakdance_website_policies', true ) ) {

        return WebsitePolicies::get_instance();

    }

}

WebsitePolicies();