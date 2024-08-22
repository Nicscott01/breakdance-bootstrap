<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\LoopPopup",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class LoopPopup extends \Breakdance\Elements\Element
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
        return 'Loop Popup';
    }

    static function className()
    {
        return 'bric-loop-popup';
    }

    static function category()
    {
        return 'dynamic';
    }

    static function badge()
    {
        return false;
    }

    static function slug()
    {
        return get_class();
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
        return ['content' => ['data' => ['id' => '[breakdance_dynamic field=\'creare_post_id\']', 'id_dynamic_meta' => ['field' => ['category' => 'Post', 'subcategory' => '', 'label' => 'Post ID', 'slug' => 'creare_post_id', 'returnTypes' => ['0' => 'string'], 'defaultAttributes' => (object)[], 'controls' => ['0' => ['slug' => 'advanced', 'label' => 'Advanced', 'children' => ['0' => ['slug' => 'beforeContent', 'label' => 'Prepend', 'children' => [], 'options' => ['type' => 'text', 'layout' => 'vertical'], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []], '1' => ['slug' => 'afterContent', 'label' => 'Append', 'children' => [], 'options' => ['type' => 'text', 'layout' => 'vertical'], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []], '2' => ['slug' => 'truncate', 'label' => 'Limit Characters', 'children' => [], 'options' => ['type' => 'number', 'layout' => 'vertical'], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []], '3' => ['slug' => 'fallback', 'label' => 'Fallback', 'children' => [], 'options' => ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['multiline' => false]], 'enableMediaQueries' => false, 'enableHover' => false, 'keywords' => []]], 'options' => ['type' => 'section', 'sectionOptions' => ['type' => 'popout']], 'enableMediaQueries' => false, 'enableHover' => false]], 'proOnly' => true], 'shortcode' => '[breakdance_dynamic field=\'creare_post_id\']', 'attributes' => (object)[]]]]];
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
        "modal",
        "Modal",
        [c(
        "width",
        "Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "z_index",
        "Z-Index",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['step' => 1], 'unitOptions' => ['types' => ['0' => 'custom']]],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Padding",
      "padding",
       ['type' => 'popout']
     ), c(
        "margin",
        "Margin",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "background_color",
        "Background Color",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\borders",
      "Borders",
      "borders",
       ['type' => 'popout']
     ), c(
        "vertical_align",
        "Vertical Align",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => ['0' => ['value' => 'start', 'text' => 'Top'], '1' => ['text' => 'Center', 'value' => 'center'], '2' => ['text' => 'Bottom', 'value' => 'end']]],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "navigation",
        "Navigation",
        [c(
        "previous",
        "Previous",
        [],
        ['type' => 'icon', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "next",
        "Next",
        [],
        ['type' => 'icon', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\AtomV1IconDesignWithHover",
      "Icon Style",
      "icon_style",
       ['type' => 'popout']
     ), c(
        "background",
        "Background",
        [],
        ['type' => 'gradient', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_padding_x",
      "Padding",
      "padding",
       ['type' => 'popout']
     )],
        ['type' => 'section', 'condition' => ['0' => ['0' => ['path' => 'content.data.adjacent_navigation', 'operand' => 'is set', 'value' => '']]], 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      ), c(
        "backdrop",
        "Backdrop",
        [c(
        "background_color",
        "Background Color",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "opacity",
        "Opacity",
        [],
        ['type' => 'number', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "notice",
        "Notice",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'info', 'content' => '<p>You will not see the changes for these controls in the Breakdance editor, only on the front end.</p>']],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "close_button",
        "Close Button",
        [c(
        "icon",
        "Icon",
        [],
        ['type' => 'icon', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\AtomV1IconDesignWithHover",
      "Icon Style",
      "icon_style",
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
        "adjacent_navigation",
        "Adjacent Navigation",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "allow_scroll",
        "Allow Scroll",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "disable_close_button",
        "Disable Close Button",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
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
        return ['0' => ['accepts' => 'string', 'path' => 'content.data.id'], '1' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '2' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '3' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '4' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '5' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '6' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '7' => ['accepts' => 'image_url', 'path' => 'design.navigation.background.layers[].image'], '8' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string']];
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
        return ['design.navigation.background.design.layout.horizontal.vertical_at', 'design.navigation.background.type', 'design.navigation.background.image', 'design.navigation.background.overlay.image', 'design.navigation.background.image_settings.unset_image_at', 'design.navigation.background.image_settings.size', 'design.navigation.background.image_settings.height', 'design.navigation.background.image_settings.repeat', 'design.navigation.background.image_settings.position', 'design.navigation.background.image_settings.left', 'design.navigation.background.image_settings.top', 'design.navigation.background.image_settings.attachment', 'design.navigation.background.image_settings.custom_position', 'design.navigation.background.image_settings.width', 'design.navigation.background.overlay.image_settings.custom_position', 'design.navigation.background.image_size', 'design.navigation.background.overlay.image_size', 'design.navigation.background.overlay.type', 'design.navigation.background.image_settings', 'design.modal.layout.horizontal.vertical_at'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
