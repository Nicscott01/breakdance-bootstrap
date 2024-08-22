<?php
global $post;

$popupPostId = $post->ID;

?>
<div
    class="breakdance-popup<?php echo isset( $propertiesData['content']['popup']['advanced']['popup_css_class'] ) ? $propertiesData['content']['popup']['advanced']['popup_css_class'] :'';  ?>"
    data-breakdance-popup-id="<?php echo get_the_ID(); ?>" 
    <?php echo isset( $propertiesData['content']['popup']['advanced']['popup_html_id'] ) ? "id=" . $propertiesData['content']['popup']['advanced']['popup_html_id'] : ''; ?>
>
  <div class='breakdance-popup-content'>
    %%CHILDREN%%
  </div>
<?php 

if ( ! $propertiesData['content']['popup']['disable_close_button'] ) {
  ?>
    <div class="breakdance-popup-close-button<?php echo $propertiesData['design']['close_button']['show_after'] ? ' hidden' : '';  ?> breakdance-popup-position-<?php echo isset( $propertiesData['design']['close_button']['position'] ) ?: 'top-right'; ?>" data-breakdance-popup-reference="%%BRIC_GATED_DOWNLOAD_ID%%" data-breakdance-popup-action="close">
      <div class="breakdance-popup-close-icon">
        <?php 
        
        if ( $propertiesData['design']['close_button']['custom_icon'] ) {
          echo $propertiesData['design']['close_button']['custom_icon']['svgCode'];
        } else {
?>
          <svg viewBox="0 0 24 24">
            <path d="M20 6.91L17.09 4L12 9.09L6.91 4L4 6.91L9.09 12L4 17.09L6.91 20L12 14.91L17.09 20L20 17.09L14.91 12L20 6.91Z" />
          </svg>
<?php

        }
        ?>
      </div>
    </div>

  <?php
}
?>
</div>