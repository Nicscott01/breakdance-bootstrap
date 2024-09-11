<?php

global $post;

$popup = $propertiesData['content']['data']['popup'];

//Get the post outlink and put in as data-attribute for tokyo event lsta
$website_url = get_field( 'website', $post->ID );

?>
<button type="button" class="loop-popup-button" data-bs-toggle="modal" data-bs-target="#loop-modal-<?php echo $post->ID; ?>" data-website-link="<?php echo $website_url; ?>">
    %%CHILDREN%%
</button>
<?php
//This is the trigger, but we still need the modal
\Bric\Breakdance\ModalQueue()->add_modal( $post->ID, \Breakdance\Render\render($popup, $post->ID) );