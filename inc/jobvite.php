<?php

namespace BreakdanceBS;

class Jobvite {



    static $instance;

<<<<<<< HEAD
=======
    public $got_one;
>>>>>>> 52c3c071ffebff5640b409e5cbc8059e643d6c92

    public function __construct()
    {

    add_action( 'breakdance_after_save_document', [ $this, 'maybe_init_css_gen' ]);

    }



    /**
     *  Maybe Init the CSS Generation
     * 
     * 
     */
    public function maybe_init_css_gen( $postId ) {
       
        // the save button in Breakdance was clicked and the post was saved

        $data =  \Breakdance\Data\get_meta( $postId, 'breakdance_data' );


        if ( !empty( $data['tree_json_string'] )  ) {

            $tree_data = json_decode( $data['tree_json_string'] );

            if ( isset( $tree_data->root->children ) && !empty( $tree_data->root->children  ) ) {

                $jobvite = $this->check_children( $tree_data->root->children );
                
                if ( $jobvite ) {

                    //Now make our css file
                    $this->generate_css( $postId );

                }
            }

        }

      
    
    }



    /**
     * Make the CSS file from the partials
     * 
     * 
     */

     public function generate_css( $postId ) {

        $ScriptAndStyleHolder = \Breakdance\Render\ScriptAndStyleHolder::getInstance();

       $post_css_path = $ScriptAndStyleHolder->getPostGeneratedCssFilePaths( $postId );
       $global_css_path = $ScriptAndStyleHolder->getGlobalGeneratedCssFilePaths();

        error_log( json_encode( $post_css_path ) );

        error_log( json_encode( $global_css_path ) );


        $post_css_get = wp_remote_get( $post_css_path['postCssFilePath'] );
        $global_css_get = wp_remote_get( $global_css_path['globalSettingsCssFilePath'] );

        if ( $post_css_get['response']['code'] == 200 ) {
            $post_css_code = $post_css_get['body'];
        }
        if ( $global_css_get['response']['code'] == 200 ) {
            $global_css_code = $global_css_get['body'];
        }
        error_log( $global_css_code );
        error_log( $post_css_code );

        //Now, lets' put these two files together
        $jobvite_css = $global_css_code . $post_css_code;       

        file_put_contents( $this->get_save_path() . 'jobvite.css', $jobvite_css );
        
     }




     /**
      * Get Save location
      *
      *
      */

    public function get_save_path() {

        $wp_uploads = wp_upload_dir( false );

        $jv_save_location = $wp_uploads['basedir'] . '/jobvite/';

        if ( is_dir( $jv_save_location ) ) {

            return $jv_save_location;

        } elseif ( wp_mkdir_p( $jv_save_location ) ) { //make the directory if it doesn't exist. returns bool of success
            
            return $jv_save_location;

        }


    }




    /**
     *  Check for our Element
     * 
     * 
     */

    public function check_children( $nodes ) {

        $this->got_one = false;
        $found_node = null;

        foreach( $nodes as $node ) {

            error_log( $node->data->type );

            if ( $node->data->type == 'BreakdanceCustomElements\JobviteEmbed' ) {

                $this->got_one = true;
                $found_node = $node;

            } elseif ( isset( $node->children ) && !empty( $node->children ) ) {

                $this->check_children( $node->children );

            }

            if ( $this->got_one ) {
                break;
            }

        }

        return $this->got_one;


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


Jobvite::get_instance(); 