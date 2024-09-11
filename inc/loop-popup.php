<?php

namespace Bric\Breakdance;

class ModalQueue {

    public static $instance;
    public $modals = [];
    public $modals_order = [];
    public $k;
    public $count;
    public $modal_group;
    public $modal_groups;
    public $group_id;

    public function __construct()
    {
        add_action( 'wp_footer', [ $this, 'print_modals'], 100 );


        //do_action("breakdance_posts_loop_before_loop", $actionData);

        add_action( 'breakdance_posts_loop_before_loop_d', function( $actionData ) {
            var_dump( $actionData );

        });

        add_action( 'breakdance_posts_loop_before_loop', [ $this, 'set_modal_group_id'], 10, 1 );
        //add_action( 'breakdance_posts_loop_after_loop', [ $this, 'set_modal_group_id'], 10, 1 );


    }



    /**
     *  Set the group ID
     * 
     */

    public function set_modal_group_id( $actionData ) {
        
        if ( $this->group_id == null ) {
            $this->group_id = 1;
        } else {
            $this->group_id++;
        }

    }




    /**
     *  Add the modal
     * 
     */

    public function add_modal( $id, $modal ) {
        /*$this->modals[$id] = $modal;
        $this->modals_order[] = $id;
*/
        $this->modal_groups[$this->group_id][$id] = [
            'modal' => $modal,
            'id' => $id
        ];
    }





    public function print_modals() {

        //var_dump( $this->modal_group );

        if ( !empty( $this->modal_groups ) ) {

            foreach( $this->modal_groups as $this->modal_group ) {

                //We're in a group that has a bunch of things
                if( !empty( $this->modal_group ) ) {

                    $this->modal_group = array_values( $this->modal_group );

                    //var_dump( $modal_group );

                    $this->k = 0;
                    $this->count = count( $this->modal_group );
        
                    foreach( $this->modal_group as $modal ) {
                        echo $this->add_navigation_data( $modal['modal'] );
                        $this->k++;
                    }
                }

            }
        } 



        
    }




    public function add_navigation_data( $modal ) {

        if ( $this->k == 0 ) { //the top

            $prev = end( $this->modal_group );
            $next = $this->modal_group[$this->k+1];
            
            $prev_id = $prev['id'];
            $next_id = $next['id'];
        } elseif ( $this->count -1 == $this->k ) { //the end
            $prev = $this->modal_group[$this->k-1];
            $next = $this->modal_group[0];

            $prev_id = $prev['id']; //$this->modals_order[$this->k-1];
            $next_id = $next['id']; //$this->modals_order[0];

        } else {

            $prev = $this->modal_group[$this->k-1];
            $next = $this->modal_group[$this->k+1];

            $prev_id = $prev['id']; //$this->modals_order[$this->k-1];
            $next_id = $next['id']; //$this->modals_order[$this->k+1];
        }




        $modal = str_replace( '{{bric_loop_modal_prev}}', 'loop-modal-' . $prev_id, $modal );
        $modal = str_replace( '{{bric_loop_modal_next}}', 'loop-modal-' . $next_id, $modal );

        return $modal;

    }





    public static function get_instance() {

        if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }


}




function ModalQueue() {
    return ModalQueue::get_instance();
}


ModalQueue();