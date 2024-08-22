<?php

namespace Bric\Breakdance;

class ModalQueue {

    public static $instance;
    public $modals = [];
    public $modals_order = [];
    public $k;
    public $count;

    public function __construct()
    {
        add_action( 'wp_footer', [ $this, 'print_modals'], 100 );



    }


    /**
     *  Add the modal
     * 
     */

    public function add_modal( $id, $modal ) {
        $this->modals[$id] = $modal;
        $this->modals_order[] = $id;
    }





    public function print_modals() {

        if( !empty( $this->modals ) ) {

            $this->k = 0;
            $this->count = count( $this->modals );

            foreach( $this->modals as $modal ) {
                echo $this->add_navigation_data( $modal );
                $this->k++;
            }
        }
    }




    public function add_navigation_data( $modal ) {

        if ( $this->k == 0 ) { //the top
            $prev_id = end( $this->modals_order );
            $next_id = $this->modals_order[$this->k+1];
        } elseif ( $this->count -1 == $this->k ) { //the end
            $prev_id = $this->modals_order[$this->k-1];
            $next_id = $this->modals_order[0];
        } else {
            $prev_id = $this->modals_order[$this->k-1];
            $next_id = $this->modals_order[$this->k+1];
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