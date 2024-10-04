<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\RezstreamCalendar",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class RezstreamCalendar extends \Breakdance\Elements\Element
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
        return 'RezStream Calendar';
    }

    static function className()
    {
        return 'bric-rs-calendar';
    }

    static function category()
    {
        return 'other';
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
        "layout",
        "Layout",
        [c(
        "stack",
        "Stack",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [['value' => 'row', 'text' => 'Horizontal'], ['text' => 'Vertical', 'value' => 'column']]],
        true,
        false,
        [],
      ), c(
        "align",
        "Align",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [['value' => 'flex-start', 'text' => 'Flex Start'], ['text' => 'Center', 'value' => 'center'], ['text' => 'Flex End', 'value' => 'flex-end']]],
        false,
        false,
        [],
      ), c(
        "hide_at_breakpoint",
        "Hide At Breakpoint",
        [],
        ['type' => 'breakpoint_dropdown', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "form",
        "Form",
        [getPresetSection(
      "EssentialElements\\AtomV1ButtonDesign",
      "Button",
      "button",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\AtomV1ButtonDesign",
      "Mobile Button",
      "mobile_button",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Input Size",
      "input_size",
       ['type' => 'popout']
     ), c(
        "font_size",
        "Font Size",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "select_field_size",
        "Select Field Size",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\typography",
      "Typography",
      "typography",
       ['type' => 'popout']
     ), c(
        "calendar",
        "Calendar",
        [c(
        "max_width",
        "Max Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "hide_check_in_check_out",
        "Hide Check-In Check-Out",
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
      )];
    }

    static function contentControls()
    {
        return [c(
        "data",
        "Data",
        [c(
        "business",
        "Business",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "unit",
        "Unit",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => 'Unit or Room ID'],
        false,
        false,
        [],
      ), c(
        "inline_months",
        "Inline Months",
        [],
        ['type' => 'number', 'layout' => 'vertical', 'rangeOptions' => ['min' => 0, 'max' => 12, 'step' => 1]],
        false,
        false,
        [],
      ), c(
        "show_rates",
        "Show Rates",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "button_text",
        "Button Text",
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
        return ['0' =>  ['title' => 'rezStreamLoader','scripts' => ['%%BREAKDANCE_REUSABLE_REZ_STREAM_LOADER_JS%%','%%BREAKDANCE_REUSABLE_REZ_STREAM_INIT_JS%%'],],'1' =>  ['title' => 'Form','styles' => ['%%BREAKDANCE_ELEMENTS_PLUGIN_URL%%dependencies-files/awesome-form@1/css/form.css'],],'2' =>  ['inlineScripts' => ['queueRezCalendar(\'#rscalendar-%%ID%%\', {
    business: "{{ content.data.business|default(\'white-sails-inn\') }}",
    unit: \'{{ content.data.unit }}\',
    inlineMonths: {{ content.data.inline_months|default(0) }},
    showRates: {{ content.data.show_rates|default("false") }}
  });'],'title' => 'Load','frontendCondition' => 'return true;','builderCondition' => 'return true;',],];
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

'onMountedElement' => [['script' => 'window.triggerRezStreamLoaderReady();',
],],

'onPropertyChange' => [['script' => 'window.triggerRezStreamLoaderReady();',
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
        return [['accepts' => 'string', 'path' => 'content.data.business'], ['accepts' => 'string', 'path' => 'content.data.unit']];
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
        return ['design.layout.horizontal.vertical_at', 'design.layout_v2.layout', 'design.layout_v2.h_vertical_at', 'design.layout_v2.h_alignment_when_vertical', 'design.layout_v2.a_display', 'design.layout.hide_at_breakpoint', 'design.controls.mobile_button.custom.size.full_width_at', 'design.controls.mobile_button.styles'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
