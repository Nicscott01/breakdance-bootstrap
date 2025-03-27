<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\PressList",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class PressList extends \Breakdance\Elements\Element
{
    static function uiIcon()
    {
        return 'SquareIcon';
    }

    static function tag()
    {
        return 'ul';
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
        return 'Press List';
    }

    static function className()
    {
        return 'bricbd-press-list';
    }

    static function category()
    {
        return 'blocks';
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
        "list",
        "List",
        [c(
        "list_style",
        "List Style",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [['value' => 'disc', 'text' => 'Default filled circle'], ['text' => 'Hollow circle', 'value' => 'circle'], ['text' => 'Filled square', 'value' => 'square'], ['text' => 'No marker', 'value' => 'none'], ['text' => 'Numbers (1, 2, 3, ...)', 'value' => 'decimal']]],
        false,
        false,
        [],
      ), c(
        "padding_left",
        "Padding left",
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
      ), c(
        "typography",
        "Typography",
        [getPresetSection(
      "EssentialElements\\typography",
      "General",
      "general",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Date",
      "date",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Publication",
      "publication",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Link",
      "link",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Title",
      "title",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Separator",
      "separator",
       ['type' => 'popout']
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
        "items",
        "Items",
        [c(
        "date",
        "Date",
        [],
        ['type' => 'date_picker', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "publication",
        "Publication",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "article_title",
        "Article Title",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "link",
        "Link",
        [],
        ['type' => 'url', 'layout' => 'vertical'],
        false,
        false,
        [],
      )],
        ['type' => 'repeater', 'layout' => 'vertical', 'repeaterOptions' => ['titleTemplate' => '{article_title}', 'defaultTitle' => '', 'buttonName' => '+ Item']],
        false,
        false,
        [],
      ), c(
        "separator",
        "Separator",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain', 'multiline' => null], 'variableOptions' => ['enabled' => false], 'placeholder' => '|'],
        false,
        false,
        [],
      ), c(
        "date_format",
        "Date Format",
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
        return [];
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
        return false;
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
