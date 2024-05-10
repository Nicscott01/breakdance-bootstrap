<?php

namespace BricBreakdance;

class TeamMembers {


    public static $instance;



    public function __construct()
    {
        include_once( __DIR__ . '/../acf-fields/team-members.php' );
        
        add_action( 'acf/save_post', [ $this, 'save_team_member_name_as_post_title' ] );



    }


    public function save_team_member_name_as_post_title( $post_id ) {

        $first_name = get_field( 'first_name' );

        if ( !empty( $first_name ) ) {

            wp_update_post([
                'ID' => $post_id,
                'post_title' => trim( $first_name . ' ' . get_field( 'last_name' ) ),
            ]);

        }

    }



    public static function get_instance() {

        if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }



}


function TeamMembers() {

    if ( apply_filters( 'bric_breakdance_team_members', true ) ) {

        return TeamMembers::get_instance();

    }

}

TeamMembers();