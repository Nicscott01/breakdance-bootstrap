<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\DynamicAccordionItem",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class DynamicAccordionItem extends \Breakdance\Elements\Element
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
        return 'Dynamic Accordion Item';
    }

    static function className()
    {
        return 'creare--dynamic-accordion-item';
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
        "title",
        "Title",
        [getPresetSection(
      "EssentialElements\\typography",
      "Typography",
      "typography",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\layout",
      "Layout",
      "layout",
       ['type' => 'popout']
     ), c(
        "width",
        "Width",
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
        "icon",
        "Icon",
        [getPresetSection(
      "EssentialElements\\AtomV1IconDesignWithHover",
      "Icon",
      "icon",
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
        "how_to_use_this",
        "How To Use This",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'info', 'content' => '<p>Set your title and icon to the Accordion item here. Then, add your body content within the Accordion Item.</p>']],
        false,
        false,
        [],
      ), c(
        "id",
        "ID",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "slug",
        "Slug",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "title",
        "Title",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "arrow",
        "Arrow",
        [],
        ['type' => 'icon', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "markup_as_faq",
        "Markup as FAQ",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
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
        return ['shareStateWithChildSSR' => true];
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
        return ["type" => "container",   ];
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
        return [['accepts' => 'string', 'path' => 'content.data.id'], ['accepts' => 'string', 'path' => 'content.data.title'], ['accepts' => 'string', 'path' => 'content.data.slug']];
    }

    static function additionalClasses()
    {
        return [['name' => 'accordion-item', 'template' => 'no']];
    }

    static function projectManagement()
    {
        return false;
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return ['design.layout.layout.horizontal.vertical_at'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
