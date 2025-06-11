<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\GoogleMapsLocations",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class GoogleMapsLocations extends \Breakdance\Elements\Element
{
    static function uiIcon()
    {
        return 'SquareIcon';
    }

    static function tag()
    {
        return 'div';
    }

    static function tagOptions()
    {
        return [];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'Google Maps Locations';
    }

    static function className()
    {
        return 'bric-google-maps-locations';
    }

    static function category()
    {
        return 'advanced';
    }

    static function badge()
    {
        return false;
    }

    static function slug()
    {
        return __CLASS__;
    }

    static function template()
    {
        return file_get_contents(__DIR__ . '/html.twig');
    }

    static function defaultCss()
    {
        return file_get_contents(__DIR__ . '/default.css');
    }

    static function defaultProperties()
    {
        return false;
    }

    static function defaultChildren()
    {
        return false;
    }

    static function cssTemplate()
    {
        $template = file_get_contents(__DIR__ . '/css.twig');
        return $template;
    }

    static function designControls()
    {
        return [c(
        "general",
        "General",
        [c(
        "height",
        "Height",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "width",
        "Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "aspect_ratio",
        "Aspect Ratio",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [['value' => '1/1', 'text' => '1:1'], ['value' => '4/3', 'text' => '4:3'], ['value' => '3/2', 'text' => '3:2'], ['text' => '16:9', 'value' => '16/9'], ['text' => '8:5', 'value' => '8/5'], ['text' => 'Custom', 'value' => 'custom']]],
        true,
        false,
        [],
      ), c(
        "custom_ratio",
        "Custom Ratio",
        [c(
        "width",
        "Width",
        [],
        ['type' => 'number', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "height",
        "Height",
        [],
        ['type' => 'number', 'layout' => 'inline'],
        true,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'inline', 'condition' => [[['path' => 'design.general.aspect_ratio', 'operand' => 'equals', 'value' => 'custom']]], 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "controls",
        "Controls",
        [c(
        "street_view",
        "Street View",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "map_type",
        "Map Type",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "scale",
        "Scale",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "rotate",
        "Rotate",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zoom",
        "Zoom",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "full_screen",
        "Full Screen",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "icons",
        "Icons",
        [c(
        "color",
        "Color",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "size",
        "Size",
        [],
        ['type' => 'unit', 'layout' => 'vertical', 'unitOptions' => ['types' => ['px']], 'rangeOptions' => ['min' => 1, 'max' => 500, 'step' => 1]],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      )];
    }

    static function contentControls()
    {
        return [c(
        "data",
        "Data",
        [c(
        "data_source",
        "Data Source",
        [],
        ['type' => 'button_bar', 'layout' => 'vertical', 'items' => [['value' => 'builder', 'text' => 'Builder'], ['text' => 'ACF', 'value' => 'acf']], 'buttonBarOptions' => ['size' => 'small', 'layout' => 'default']],
        false,
        false,
        [],
      ), c(
        "locations_acf",
        "Locations ACF",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'html'], 'placeholder' => 'Only use dynamic data', 'condition' => [[['path' => 'content.data.data_source', 'operand' => 'equals', 'value' => 'acf']]]],
        false,
        false,
        [],
      ), c(
        "name_field",
        "Name Field",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'condition' => [[['path' => 'content.data.data_source', 'operand' => 'equals', 'value' => 'acf']]]],
        false,
        false,
        [],
      ), c(
        "map_field_type",
        "Map Field Type",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'acf_map', 'text' => 'ACF Map'], ['text' => 'Post Reference', 'value' => 'post']], 'condition' => [[['path' => 'content.data.data_source', 'operand' => 'equals', 'value' => 'acf']]]],
        false,
        false,
        [],
      ), c(
        "map_field",
        "Map Field",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'condition' => [[['path' => 'content.data.map_field_type', 'operand' => 'equals', 'value' => 'acf_map']]]],
        false,
        false,
        [],
      ), c(
        "post_field",
        "Post Field",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'condition' => [[['path' => 'content.data.map_field_type', 'operand' => 'equals', 'value' => 'post'], ['path' => 'content.data.data_source', 'operand' => 'equals', 'value' => 'acf']]]],
        false,
        false,
        [],
      ), c(
        "post_map_field",
        "Post Map Field",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'condition' => [[['path' => 'content.data.map_field_type', 'operand' => 'equals', 'value' => 'post'], ['path' => 'content.data.data_source', 'operand' => 'equals', 'value' => 'acf']]]],
        false,
        false,
        [],
      ), c(
        "locations",
        "Locations",
        [c(
        "name",
        "Name",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "address",
        "Address",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain']],
        false,
        false,
        [],
      ), c(
        "coordinates",
        "Coordinates",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'placeholder' => '40.8545698,-74.1397791'],
        false,
        false,
        [],
      ), c(
        "icon",
        "Icon",
        [],
        ['type' => 'icon', 'layout' => 'vertical', 'iconOptions' => ['suggestions' => ['Pin']]],
        false,
        true,
        [],
      ), c(
        "icon_color",
        "Icon Color",
        [],
        ['type' => 'color', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "icon_size",
        "Icon Size",
        [],
        ['type' => 'unit', 'layout' => 'vertical', 'rangeOptions' => ['min' => 1, 'max' => 500, 'step' => 1], 'unitOptions' => ['types' => ['px']]],
        false,
        false,
        [],
      )],
        ['type' => 'repeater', 'layout' => 'vertical', 'repeaterOptions' => ['titleTemplate' => '{name}', 'defaultTitle' => 'Location', 'buttonName' => 'Add Location'], 'condition' => [[['path' => 'content.data.data_source', 'operand' => 'equals', 'value' => 'builder']]]],
        false,
        false,
        [],
      ), c(
        "center_info",
        "Center Info",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'info', 'content' => '<p>The center coordinates will appear in the bottom left of the map area after you drag the map around. Copy and paste those below for the center coordinates.</p>']],
        false,
        false,
        [],
      ), c(
        "center",
        "Center",
        [],
        ['type' => 'text', 'layout' => 'inline', 'placeholder' => 'lat,lng', 'textOptions' => ['format' => 'plain'], 'autofocus' => true],
        false,
        false,
        [],
      ), c(
        "zoom",
        "Zoom",
        [],
        ['type' => 'unit', 'layout' => 'vertical', 'rangeOptions' => ['min' => 1, 'max' => 21, 'step' => 1], 'unitOptions' => ['types' => ['%'], 'defaultType' => '%']],
        false,
        false,
        [],
      ), c(
        "type",
        "Type",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'roadmap', 'text' => 'Roadmap'], ['text' => 'Satellite', 'value' => 'satellite'], ['text' => 'Hybrid', 'value' => 'hybrid'], ['text' => 'Terrain', 'value' => 'terrain']]],
        false,
        false,
        [],
      ), c(
        "custom_icon",
        "Custom Icon",
        [],
        ['type' => 'icon', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "",
        "",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'vertical'],
        false,
        false,
        [],
      )];
    }

    static function settingsControls()
    {
        return [];
    }

    static function dependencies()
    {
        return ['0' =>  ['title' => 'Google Maps','scripts' => ['%%BREAKDANCE_REUSABLE_GOOGLE_MAPS_JS%%'],],'1' =>  ['scripts' => ['%%BREAKDANCE_REUSABLE_BRIC_GOOGLE_MAPS_LOCATIONS_JS%%'],'title' => 'Bric Google Maps',],'2' =>  ['title' => 'Coordinates - Zoom Viewer','frontendCondition' => 'return false;','inlineScripts' => ['document.querySelector( \'%%SELECTOR%%\' ).insertAdjacentHTML(\'beforeend\', \'<div class="maps-data">Coordinates: <div class="center-coordinates"></div>Zoom: <div class="zoom"></div></div>\');
'],'inlineStyles' => ['
.breakdance .bric-google-maps-locations .maps-data {
  position:absolute;
  top:auto;
  bottom:0;
  left:0;
  width:auto;
  background-color: #eee;
  z-index:10;
  height:auto;
  padding:.5rem;
  display:flex; 
  font-weight:bolder;
}

.breakdance .bric-google-maps-locations .maps-data .zoom, 
.breakdance .bric-google-maps-locations .maps-data .center-coordinates{
  margin-left:0rem;
  margin-right:1rem;
  font-weight: 400;
}'],],'3' =>  ['title' => 'Frontend Init','inlineScripts' => ['var GoogleMapsOptions = {
    "center": {{ content.data.center|json_encode|raw }},
    "zoom": {{ content.data.zoom|json_encode|raw }},
    "type": {{ content.data.type|json_encode|raw }},
    "streetViewControl": {{ design.controls.street_view|json_encode|raw }},
    "mapTypeControl": {{ design.controls.map_type|json_encode|raw }},
    "scaleControl": {{ design.controls.scale|json_encode|raw }},
    "rotateControl": {{ design.controls.rotate|json_encode|raw }},
    "zoomControl": {{ design.controls.zoom|json_encode|raw }},
	"fullscreenControl": {{design.controls.full_screen|json_encode|raw}},
    "iconsColor" : "{{ design.icons.color|raw }}",
	"iconsSize" : "{{ design.icons.size.number|raw }}"
};

window.BricGoogleMapsLocations().update({
 id: "%%ID%%",
  selector: "%%SELECTOR%%",
  options: GoogleMapsOptions
});
'],'builderCondition' => 'return false;','frontendCondition' => 'return true;',],];
    }

    static function settings()
    {
        return false;
    }

    static function addPanelRules()
    {
        return false;
    }

    static public function actions()
    {
        return [

'onPropertyChange' => [['script' => 'var GoogleMapsOptions = {
    "center": {{ content.data.center|json_encode|raw }},
    "zoom": {{ content.data.zoom|json_encode|raw }},
    "type": {{ content.data.type|json_encode|raw }},
    "streetViewControl": {{ design.controls.street_view|json_encode|raw }},
    "mapTypeControl": {{ design.controls.map_type|json_encode|raw }},
    "scaleControl": {{ design.controls.scale|json_encode|raw }},
    "rotateControl": {{ design.controls.rotate|json_encode|raw }},
    "zoomControl": {{ design.controls.zoom|json_encode|raw }},
	"fullscreenControl": {{design.controls.full_screen|json_encode|raw}},
   "iconsSize" : {{ design.icons and design.icons.size and design.icons.size.number ? design.icons.size.number : \'null\' }},
    "iconsColor" : "{{ design.icons.color|raw }}"	
};

window.BricGoogleMapsLocations().update({
 id: "%%ID%%",
  selector: "%%SELECTOR%%",
  options: GoogleMapsOptions
});',
],],

'onMountedElement' => [['script' => 'var GoogleMapsOptions = {
    "center": {{ content.data.center|json_encode|raw }},
    "zoom": {{ content.data.zoom|json_encode|raw }},
    "type": {{ content.data.type|json_encode|raw }},
    "streetViewControl": {{ design.controls.street_view|json_encode|raw }},
    "mapTypeControl": {{ design.controls.map_type|json_encode|raw }},
    "scaleControl": {{ design.controls.scale|json_encode|raw }},
    "rotateControl": {{ design.controls.rotate|json_encode|raw }},
    "zoomControl": {{ design.controls.zoom|json_encode|raw }},
	"fullscreenControl": {{design.controls.full_screen|json_encode|raw}},
   "iconsSize" : {{ design.icons and design.icons.size and design.icons.size.number ? design.icons.size.number : \'null\' }},
    "iconsColor" : "{{ design.icons.color|raw }}"	
};

window.BricGoogleMapsLocations().update({
 id: "%%ID%%",
  selector: "%%SELECTOR%%",
  options: GoogleMapsOptions
});


',
],],];
    }

    static function nestingRule()
    {
        return ["type" => "final",   ];
    }

    static function spacingBars()
    {
        return false;
    }

    static function attributes()
    {
        return false;
    }

    static function experimental()
    {
        return false;
    }

    static function order()
    {
        return 0;
    }

    static function dynamicPropertyPaths()
    {
        return [['accepts' => 'repeater', 'path' => 'content.data.locations_acf']];
    }

    static function additionalClasses()
    {
        return false;
    }

    static function projectManagement()
    {
        return false;
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return ['content.data.locations[].icon', 'design.icons.size', 'design.icons.color', 'content.data.locations[].icon_color'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
