<?php

namespace BricBreakdance\TwigFunctions;


function get_map_from_post( $post, $map_field ) {


    $map = get_field( $map_field, $post->ID );

    return $map;

}

\Breakdance\PluginsAPI\PluginsController::getInstance()->registerTwigFunction(
    'getMapFromPost',
    'BricBreakdance\TwigFunctions\get_map_from_post',
    '(post, mapField) => { return { lat: 0, lng: 0 }; }',
    true
);  


\Breakdance\PluginsAPI\PluginsController::getInstance()->registerTwigFunction(
    'getPostObj',
    'get_post',
    '(post_id) => { return { ID: post_id }; }',
    true
);  


function slugify($string) {
    return sanitize_title($string);
}

function shout($string) {
    return strtoupper($string);
}

\Breakdance\PluginsAPI\PluginsController::getInstance()->registerTwigFunction(
    'slugify', 
    '\BricBreakdance\TwigFunctions\slugify', 
    '(str) => { return {}; }'
);
\Breakdance\PluginsAPI\PluginsController::getInstance()->registerTwigFunction(
    'shout', 
    '\BricBreakdance\TwigFunctions\shout', 
    '(str) => str.toUpperCase()'
);
