<?php 

namespace BricBreakdanceElements;

use function Breakdance\Util\getDirectoryPathRelativeToPluginFolder;

add_action('breakdance_loaded', function () {
    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/elements',
        'BricBreakdanceElements',
        'element',
        'Custom Elements',
        false
    );

    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/macros',
        'BricBreakdanceElements',
        'macro',
        'Custom Macros',
        false,
    );

    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/presets',
        'BricBreakdanceElements',
        'preset',
        'Custom Presets',
        false,
    );
},
    // register elements before loading them
    9
);



add_action('breakdance_reusable_dependencies_urls', function ($urls) {
    
//    $urls['bsAccordion'] = plugin_dir_url( __FILE__ ) . 'dependencies-files/bootstrap-partials@1/bootstrap-partial.min.js';
    $urls['bsPartialJs'] = plugin_dir_url( __FILE__ ) . 'assets/js/bootstrap-partial.min.js';
    $urls['bsAccordionCss'] = plugin_dir_url( __FILE__ ) . 'assets/css/accordion.min.css';

    return $urls;

 });