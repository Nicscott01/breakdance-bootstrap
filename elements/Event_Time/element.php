<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\EventTime",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class EventTime extends \Breakdance\Elements\Element
{
    static function uiIcon()
    {
        return 'SquareIcon';
    }

    static function tag()
    {
        return 'p';
    }

    static function tagOptions()
    {
        return ['div', 'span', 'details'];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'Event Time';
    }

    static function className()
    {
        return 'bric-event-time';
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
        "typography",
        "Typography",
        [getPresetSection(
      "EssentialElements\\typography",
      "Date",
      "date",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Time",
      "time",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Day-Time Separator",
      "day_time_separator",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Time Separator",
      "time_separator",
       ['type' => 'popout']
     )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "spacing",
        "Spacing",
        [getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Wrapper",
      "wrapper",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\spacing_margin_x",
      "Day-Time Separator",
      "day_time_separator",
       ['type' => 'popout']
     )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\layout_basic_flex",
      "Layout",
      "layout",
       ['type' => 'popout']
     )];
    }

    static function contentControls()
    {
        return [c(
        "data",
        "Data",
        [c(
        "start_time",
        "Start Time",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'variableOptions' => ['enabled' => false], 'placeholder' => 'Use a proper PHP DateTime format'],
        false,
        false,
        [],
      ), c(
        "end_time",
        "End Time",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'variableOptions' => ['enabled' => false], 'placeholder' => 'Use a proper PHP DateTime format'],
        false,
        false,
        [],
      ), c(
        "about_datetime",
        "About DateTime",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'info', 'content' => '<p>DateTime format variables can be found in <a target="_blank" href="https://www.php.net/manual/en/datetime.format.php">the PHP manual.</a> We use the site settings as defaults.</p>']],
        false,
        false,
        [],
      ), c(
        "date_format",
        "Date Format",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => 'PHP DateTime Format (F j, Y)'],
        false,
        false,
        [],
      ), c(
        "time_format",
        "Time Format",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => 'PHP DateTime Format (g:ia)'],
        false,
        false,
        [],
      ), c(
        "day_time_separator",
        "Day-Time Separator",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => ',&nbsp;'],
        false,
        false,
        [],
      ), c(
        "time_separator",
        "Time Separator",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => '&nbsp;-&nbsp;'],
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
        return false;
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
        return false;
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
        return ['0' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '1' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '2' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '3' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '4' => ['accepts' => 'string', 'path' => 'content.data.start_time'], '5' => ['accepts' => 'string', 'path' => 'content.data.end_time']];
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
        return ['content.data.time_format', 'content.data.date_format', 'content.data.end_time', 'content.data.day_time_separator', 'content.data.time_separator', 'content.data.start_time'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return ['content.data.day_time_separator', 'content.data.time_separator', 'content.data.end_time', 'content.data.start_time', 'content.data.date_format', 'content.data.time_format'];
    }
}
