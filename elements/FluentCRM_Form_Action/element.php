<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\FluentcrmFormAction",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class FluentcrmFormAction extends \Breakdance\Elements\Element
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
        return 'FluentCRM Form Action';
    }

    static function className()
    {
        return 'bric-fluentcrm-form-action';
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
        return [];
    }

    static function contentControls()
    {
        return [c(
        "lists",
        "Lists",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [], 'dropdownOptions' => ['populate' => ['path' => '', 'text' => '', 'value' => '', 'fetchDataAction' => 'bdbs_fetch_fluentcrm_lists', 'fetchContextPath' => 'content.controls.lists', 'refetchPaths' => []]]],
        false,
        false,
        [],
      ), c(
        "tags",
        "Tags",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [], 'dropdownOptions' => ['populate' => ['path' => '', 'text' => '', 'value' => '', 'fetchDataAction' => 'bdbs_fetch_fluentcrm_tags', 'fetchContextPath' => 'content.controls.lists', 'refetchPaths' => []]]],
        false,
        false,
        [],
      ), c(
        "field_map",
        "Field Map",
        [c(
        "crm_field",
        "CRM Field",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'dropdownOptions' => ['populate' => ['path' => 'content.controls.field_map', 'text' => '', 'value' => '', 'fetchDataAction' => 'bdbs_fetch_fluentcrm_fields', 'fetchContextPath' => 'content.controls.field_map.crm_field', 'refetchPaths' => []]]],
        false,
        false,
        [],
      ), c(
        "formfield",
        "Form Field",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id']], 'dropdownOptions' => ['populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id', 'fetchDataAction' => '', 'fetchContextPath' => '', 'refetchPaths' => []]]],
        false,
        false,
        [],
      )],
        ['type' => 'repeater', 'layout' => 'vertical', 'repeaterOptions' => ['titleTemplate' => '{crm_field}', 'defaultTitle' => '', 'buttonName' => '']],
        false,
        false,
        [],
      ), c(
        "about_double_opt_in",
        "About Double Opt In",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'warning', 'content' => '<p>Only disable double opt-in if you know what you\'re doing. This could lead to a very dirty list and blacklisting by email providers.</p>']],
        false,
        false,
        [],
      ), c(
        "disable_double_opt_in",
        "Disable Double Opt-In",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
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
        return ['proOnly' => false];
    }

    static function addPanelRules()
    {
        return ['alwaysHide' => true];
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
