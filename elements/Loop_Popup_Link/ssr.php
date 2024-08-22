<?php

global $post;

//var_dump( $propertiesData );

$popup = $propertiesData['content']['data']['popup'];

?>
<button type="button" class="loop-popup-button" data-bs-toggle="modal" data-bs-target="#loop-modal-<?php echo $post->ID; ?>">
    %%CHILDREN%%
</button>
<?php
//This is the trigger, but we still need the modal
\Bric\Breakdance\ModalQueue()->add_modal( $post->ID, \Breakdance\Render\render($popup, $post->ID) );