<?php

namespace BricBreakdance;

use function Breakdance\Render\renderTags;

//use Breakdance\Themeless;


class DynamicPopup {

    public static $instance;
    public $popup_queue = [];
    public $popups_to_render = [];
    public $popups_to_instantiate = [];
    public $download_id;

    public function __construct()
    {

        add_action( 'wp_footer', [ $this, 'render_popups'], 100 );
        add_action( 'wp_footer', [ $this, 'instantiate_popups'], 101 );

        add_action( 'breakdance_form_before_footer', [ $this, 'add_hidden_field_to_popup' ]);
  
    }



    public function queue_popup( $popup_template_id, $download_id ) {
        
        $this->popup_queue[$download_id] = [
           'template_id' => $popup_template_id, 
           'download_id' => $download_id
        ];
    }



    public function add_popup( $popup_html, $id ) {

        $this->popups_to_render[$id] = $popup_html;

    }



    public function render_popups() {
        
        global $post;

        if ( !empty( $this->popup_queue ) ) {

            echo '<div class="breakdance bric-popups">';

            foreach ( $this->popup_queue as $popup ) {

                
                $this->download_id = $popup['download_id'];

                $post = get_post( $this->download_id );

                setup_postdata( $post );

                /** 
                 * Don't know why I needed to add wpautop filter, but it worked 
                 * to add the <p> tags to the post_content of a DLM download
                 * 
                 * 
                 */
                add_filter( 'the_content', 'wpautop' );

                //$post->post_content = wpautop( $post->post_content );

                $popup_html = \Breakdance\Render\render($popup['template_id'], $this->download_id );

                $popup_html = renderTags( $popup_html, [
                    'bric_gated_download_id' => $this->download_id,
                    'bric_gated_download_title' => $post->post_title
                ]);

                echo $popup_html;

                $this->add_popup_to_instantiate( $popup['download_id'] );

            }

            //Removed it here to put things back how we found them
            remove_filter( 'the_content', 'wp_autop' );

            wp_reset_postdata();

            echo '</div>';
        }
    }





    /**
     * document.addEventListener('DOMContentLoaded', function(){ new BreakdancePopup(167, {"onlyShowOnce":false,"avoidMultiple":false,"limitSession":null,"limitPageLoad":null,"limitForever":null,"triggers":[],"breakpointConditions":[]}); }) 
     * 
     */

    public function add_popup_to_instantiate( $id ) {

        $this->popups_to_instantiate[$id] = sprintf( '
<script>
    document.addEventListener( "DOMContentLoaded", function() {
        new BreakdancePopup( %s );
        
    });
</script>
', $id );


    }



    public function instantiate_popups() {

        foreach( $this->popups_to_instantiate as $popup ) {
            echo $popup;
        }
    }





    public function add_hidden_field_to_popup( $form ) {

        //global $post;

        if ( isset( $form['actions']['bric_gated_download'] ) ) {

            //Add our hidden field for download id. We need to add placeholder tags which will be replaced with the real IDs/content later
            ?>
    <div class="breakdance-form-field breakdance-form-field--hidden">
        <input id="bric_gated_download_id" type="hidden" name="fields[bric_gated_download_id]" value="%%BRIC_GATED_DOWNLOAD_ID%%">
        <input id="bric_gated_download_title" type="hidden" name="fields[bric_gated_download_title]" value="%%BRIC_GATED_DOWNLOAD_TITLE%%">
    </div>
            <?php
    
        }
    

    }




    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

}

function DynamicPopup() {
    return DynamicPopup::get_instance();
}


DynamicPopup();


















class GatedDownloadResponder {

    public static $instance;

    public $download_link;

    public function __construct()
    {
        
        add_action( 'template_redirect', [ $this, 'maybe_grab_url_for_download' ] );

    }


    public static function encode_hash( $download_id, $email ) {

        return urlencode ( base64_encode( 'bric_gated_download_responder_' . $download_id . '_' . $email ) );
    }


    public static function decode_hash( $hash ) {

        return urldecode( base64_decode( $hash ) );
    }


    public static function get_email_responder_link( $email, $download_id ) {

        return add_query_arg( [
            'bric_gated_download_responder' => self::encode_hash( $download_id, $email ),
            'download_id' => $download_id,
            'email' => $email 
        ], get_home_url() );

    }



    public function maybe_grab_url_for_download( ) {

        if ( !isset( $_GET['bric_gated_download_responder'] ) && !isset( $_GET['download_id'] ) && !isset( $_GET['email'] ) ) {
            return;
        }


        if( strpos( self::decode_hash( $_GET['bric_gated_download_responder'] ), 'bric_gated_download_responder' ) === 0  && isset( $_GET['download_id'] ) && isset( $_GET['email'] ) ) {


            //Do the redirect to the download url
            if ( class_exists( 'DLM_Download' ) ) {

                $DLM = download_monitor()->service( 'download_repository' )->retrieve_single( $_GET['download_id'] );

                $this->download_link = $DLM->get_the_download_link();

                wp_safe_redirect( $this->download_link );
                die();



            }
        } 


    }




    public function ww_trigger_file_download() {


        if ( !empty( $this->download_link ) ) {
        ?>
    <script>
        document.addEventListener("DOMContentLoaded", function(){
            window.location = '<?php echo $this->download_link; ?>';
        });
    </script>
        <?php
        }

    }







    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

}



GatedDownloadResponder::get_instance();

