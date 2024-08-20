<?php

namespace BricBreakdanceElements;

use function \Breakdance\Util\getDirectoryPathRelativeToPluginFolder;
use function \Breakdance\Elements\controlSection;
use function \Breakdance\Elements\c;
use function \Breakdance\Elements\PresetSections\getPresetSection;



add_action('breakdance_loaded', function () {
    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/elements',
        'BricBreakdanceElements',
        'element',
        'Bric Elements',
        false
    );

        \Breakdance\ElementStudio\registerSaveLocation(
            getDirectoryPathRelativeToPluginFolder(__DIR__) . '/macros',
            'BricBreakdanceElements',
            'macro',
            'Bric Macros',
            false,
        );

        \Breakdance\ElementStudio\registerSaveLocation(
            getDirectoryPathRelativeToPluginFolder(__DIR__) . '/presets',
            'BricBreakdanceElements',
            'preset',
            'Bric Presets',
            false,
        );
    },
    // register elements before loading them
    9
);


/**
 *  TODO: Register a Save Location for Custom Elements
 *  tied to a project, not necessarily for distribution
 * 
 * 
 */




add_action('breakdance_reusable_dependencies_urls', function ($urls) {

    //    $urls['bsAccordion'] = plugin_dir_url( __FILE__ ) . 'dependencies-files/bootstrap-partials@1/bootstrap-partial.min.js';
    $urls['bsPartialJs'] = plugin_dir_url(__FILE__) . 'assets/js/bootstrap-partial.min.js';
    $urls['bsAccordionCss'] = plugin_dir_url(__FILE__) . 'assets/css/accordion.min.css';
    
    //%%BREAKDANCE_REUSABLE_GOOGLE_MAPS_JS%%
    //<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=marker"></script>

    // Fetch the API key from Breakdance
    $google_maps_api_key = \Breakdance\APIKeys\getKey(BREAKDANCE_GOOGLE_MAPS_API_KEY_NAME);

    $urls['googleMapsJs'] = 'https://maps.googleapis.com/maps/api/js?libraries=marker&v=weekly&key=' . $google_maps_api_key;

    //%%BREAKDANCE_REUSABLE_GOOGLE_MAPS_LOCATIONS_INIT_JS%%
    $urls['googleMapsLocationsInitJs'] = plugin_dir_url( __FILE__ ) . 'assets/src/js/google-maps-locations-init.js';
    //%%BREAKDANCE_REUSABLE_BRIC_GOOGLE_MAPS_LOCATIONS_JS%%
    $urls['bricGoogleMapsLocationsJs'] = plugin_dir_url( __FILE__ ) . 'assets/src/js/bric-google-maps-locations.js';

    return $urls;

 });






 /**
  *     Global Settings
  *
  *
  *
  */

add_filter('breakdance_global_settings_control_sections_append', function ($appendedControlSections) {

   $control = [c(
        "spacing",
        "Spacing",
        [getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Paragraphs",
      "paragraphs",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Headings",
      "headings",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Buttons",
      "buttons",
       ['type' => 'popout']
     )],
        ['type' => 'section'],
        false,
        false,
        [],
      )];

      $spacing_controls = controlSection( 'bric_global_spacing', 'Global Spacing', $control );

      return array_merge( $appendedControlSections, [$spacing_controls] ); 


});





add_filter('breakdance_global_settings_css_twig_template_append', function ($appendedTwigTemplate) {

    //error_log(  __DIR__ . '/global-settings/css.twig');

    $global_css_twig = file_get_contents( __DIR__ . '/global-settings/css.twig' );

    return $appendedTwigTemplate . $global_css_twig;

});



