<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\WebsiteCopyright",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class WebsiteCopyright extends \Breakdance\Elements\Element
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
        return 'Website Copyright';
    }

    static function className()
    {
        return 'bric-website-copyright';
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
        return ['content' => ['data' => ['copyright_holder' => '[breakdance_dynamic field=\'site_title\']', 'copyright_holder_dynamic_meta' => ['field' => ['category' => 'Site Info', 'subcategory' => '', 'label' => 'Site Title', 'slug' => 'site_title', 'returnTypes' => ['string'], 'defaultAttributes' => (object)[], 'controls' => [['slug' => 'advanced', 'label' => 'Advanced', 'children' => [['slug' => 'beforeContent', 'label' => 'Prepend', 'children' => [], 'options' => ['type' => 'text', 'layout' => 'vertical'], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []], ['slug' => 'afterContent', 'label' => 'Append', 'children' => [], 'options' => ['type' => 'text', 'layout' => 'vertical'], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []], ['slug' => 'truncate', 'label' => 'Limit Characters', 'children' => [], 'options' => ['type' => 'number', 'layout' => 'vertical'], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []], ['slug' => 'fallback', 'label' => 'Fallback', 'children' => [], 'options' => ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['multiline' => false]], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []]], 'options' => ['type' => 'section', 'sectionOptions' => ['type' => 'popout']], 'enableMediaQueries' => false, 'enableHover' => false]], 'proOnly' => true], 'shortcode' => '[breakdance_dynamic field=\'site_title\']', 'attributes' => (object)[]]]]];
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
        return [getPresetSection(
      "EssentialElements\\typography",
      "Typography",
      "typography",
       ['type' => 'popout']
     )];
    }

    static function contentControls()
    {
        return [c(
        "data",
        "Data",
        [c(
        "info",
        "Info",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'info', 'content' => '<p>Use this to set the begin date for the website copyright. The end date will automagically update each year!</p>']],
        false,
        false,
        [],
      ), c(
        "start_date",
        "Start Date",
        [],
        ['type' => 'date_picker', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "copyright_holder",
        "Copyright Holder",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => 'Company name'],
        false,
        false,
        [],
      ), c(
        "suffix",
        "Suffix",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => 'All Rights Reserved'],
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
        return [['accepts' => 'string', 'path' => 'content.data.copyright_holder']];
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
