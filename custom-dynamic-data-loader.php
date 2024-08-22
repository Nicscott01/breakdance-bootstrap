<?php

namespace BricBreakdance;


add_action('init', function() {
    // Check if Breakdance is installed and class/function exists
    if (!function_exists('\Breakdance\DynamicData\registerField') || !class_exists('\Breakdance\DynamicData\Field')) {
        return;
    }

    require_once 'dynamic-data-fields/CrearePostId.php';
    require_once 'dynamic-data-fields/CrearePostSlug.php';
    require_once 'dynamic-data-fields/WooTermImage.php';

    \Breakdance\DynamicData\registerField(new CrearePostId());
    \Breakdance\DynamicData\registerField(new CrearePostSlug());
    \Breakdance\DynamicData\registerField(new WooTermImage());

});


