<?php

global $post;
global $query;

if ( $query ){
  var_dump( $query->previous_post() );
  var_dump( $query->next_post() );

}

$id = 'loop-modal-' . $post->ID;

$adjacent_nav = isset( $propertiesData['content']['data']['adjacent_navigation'] ) ? $propertiesData['content']['data']['adjacent_navigation'] : false;

/*
echo '<br><br>';
var_dump($propertiesData['design']['navigation']['padding']);
*/


if ( $adjacent_nav ) {

  $prev_icon = isset( $propertiesData['design']['navigation']['previous']['svgCode'] ) ? $propertiesData['design']['navigation']['previous']['svgCode'] : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><!-- Font Awesome Free 5.15.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) --><path d="M31.7 239l136-136c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9L127.9 256l96.4 96.4c9.4 9.4 9.4 24.6 0 33.9L201.7 409c-9.4 9.4-24.6 9.4-33.9 0l-136-136c-9.5-9.4-9.5-24.6-.1-34z"/></svg>';
  $next_icon = isset( $propertiesData['design']['navigation']['next']['svgCode'] ) ? $propertiesData['design']['navigation']['next']['svgCode'] : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><!-- Font Awesome Free 5.15.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) --><path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"/></svg>';

}

$disable_close_button = isset( $propertiesData['content']['data']['disable_close_button'] ) ? $propertiesData['content']['data']['disable_close_button'] : false;

//design.close_button.custom_icon.svgCode
$close_icon = isset( $propertiesData['design']['close_button']['icon']['svgCode'] ) ? $propertiesData['design']['close_button']['icon']['svgCode'] : '<svg viewBox="0 0 24 24"><path d="M20 6.91L17.09 4L12 9.09L6.91 4L4 6.91L9.09 12L4 17.09L6.91 20L12 14.91L17.09 20L20 17.09L14.91 12L20 6.91Z"></path></svg>';


?>
<!-- Modal -->
<div class="modal fade" id="<?php echo $id; ?>" tabindex="-1" aria-labelledby="" aria-hidden="true" <?php echo \Breakdance\isRequestFromBuilderSsr() ? "style=\"display:block;\"" : ""; ?>>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-body">
          %%CHILDREN%%
      </div>
      <?php 
        if ( $adjacent_nav ) {
          printf ( '<div class="modal-nav-wrapper modal-nav--prev"><button class="button-atom icon-wrapper modal-nav" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#{{bric_loop_modal_prev}}"><div class="breakdance-icon-atom nav-icon prev-icon">%s</div></button></div>', $prev_icon );
        }

      if ( $adjacent_nav ) {
          printf ( '<div class="modal-nav-wrapper modal-nav--next"><button class="button-atom icon-wrapper modal-nav" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#{{bric_loop_modal_next}}"><div class="breakdance-icon-atom nav-icon next-icon">%s</div></button></div>', $next_icon );
        }

      if ( ! $disable_close_button ) : ?>
        <button class="button-atom modal-close-button-wrapper" data-bs-dismiss="modal">
          <div class="breakdance-icon-atom modal-close-button">
            <?php echo $close_icon; ?>
          </div>
        </button>
      <?php endif; ?>      
      <div class="modal-footer">
        <button type="button" class="button-atom button-atom--secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>