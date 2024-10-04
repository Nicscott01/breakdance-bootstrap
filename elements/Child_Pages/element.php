<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\ChildPages",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class ChildPages extends \Breakdance\Elements\Element
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
        return 'Child Pages';
    }

    static function className()
    {
        return 'bric-children';
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
        return ['content' => ['image' => ['featured_image' => true], 'data' => ['order_by' => 'title', 'order' => 'ASC', 'source' => 'children']], 'design' => ['container' => ['width' => ['breakpoint_base' => ['number' => 100, 'unit' => '%', 'style' => '100%']], 'padding' => null], 'layout_v2' => ['layout' => 'grid', 'g_items_per_row' => ['breakpoint_base' => 3, 'breakpoint_tablet_portrait' => 2, 'breakpoint_phone_landscape' => 1], 'g_space_between_items' => ['breakpoint_base' => ['number' => 1, 'unit' => 'rem', 'style' => '1rem']]], 'child' => ['padding' => ['padding' => null], 'text_horizontal_align' => 'center', 'text_vertical_align' => 'center', 'aspect_ratio' => ['breakpoint_base' => '16/9'], 'text_background' => null, 'text_margin' => ['margin_bottom' => ['breakpoint_base' => ['number' => 0, 'unit' => 'px', 'style' => '0px']], 'margin_top' => ['breakpoint_base' => ['number' => 0, 'unit' => 'px', 'style' => '0px']]], 'background' => ['type' => 'image', 'image_settings' => ['repeat' => ['breakpoint_base' => 'no-repeat'], 'size' => ['breakpoint_base' => 'cover'], 'position' => ['breakpoint_base' => 'center center']], 'overlay' => ['color' => 'var(--bde-brand-primary-color)', 'opacity' => 0.4]], 'text_padding' => ['padding' => ['breakpoint_base' => ['left' => ['number' => 1, 'unit' => 'rem', 'style' => '1rem'], 'right' => ['number' => 1, 'unit' => 'rem', 'style' => '1rem'], 'top' => ['number' => 1, 'unit' => 'rem', 'style' => '1rem'], 'bottom' => ['number' => 1, 'unit' => 'rem', 'style' => '1rem']]]], 'borders' => null], 'typography' => ['typography' => ['custom' => ['customTypography' => ['advanced' => ['decoration' => ['line' => ['breakpoint_base' => ['none']]]]]]], 'color' => ['breakpoint_base' => 'var(--bde-background-color)']]]];
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
      "EssentialElements\\LayoutV2",
      "Layout",
      "layout_v2",
       ['type' => 'popout']
     ), c(
        "container",
        "Container",
        [c(
        "width",
        "Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "min_height",
        "Min Height",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Padding",
      "padding",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\borders",
      "Borders",
      "borders",
       ['type' => 'popout']
     )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Spacing",
      "spacing",
       ['type' => 'popout']
     ), c(
        "child",
        "Child",
        [getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Padding",
      "padding",
       ['type' => 'popout']
     ), getPresetSection(
      "BricBreakdanceElements\\LessFancyBackground",
      "Background",
      "background",
       ['type' => 'popout']
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
        ['type' => 'section', 'layout' => 'inline', 'condition' => [[['path' => '%%CURRENTPATH%%.aspect_ratio', 'operand' => 'equals', 'value' => 'custom']]], 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      ), c(
        "text_horizontal_align",
        "Text Horizontal Align",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [['value' => 'left', 'text' => 'Left'], ['text' => 'Center', 'value' => 'center'], ['text' => 'Right', 'value' => 'right']]],
        false,
        false,
        [],
      ), c(
        "text_vertical_align",
        "Text Vertical Align",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [['value' => 'flex-start', 'text' => 'Top'], ['text' => 'Middle', 'value' => 'center'], ['text' => 'Bottom', 'value' => 'flex-end']]],
        false,
        false,
        [],
      ), c(
        "text_background",
        "Text Background",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Text Margin",
      "text_margin",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Text Padding",
      "text_padding",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\borders",
      "Borders",
      "borders",
       ['type' => 'popout']
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
     )];
    }

    static function contentControls()
    {
        return [c(
        "image",
        "Image",
        [c(
        "featured_image",
        "Featured Image",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "featured_image_size",
        "Featured Image Size",
        [],
        ['type' => 'media_size_dropdown', 'layout' => 'vertical', 'condition' => [[['path' => 'content.image.featured_image', 'operand' => 'is set', 'value' => '']]]],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "data",
        "Data",
        [c(
        "source",
        "Source",
        [],
        ['type' => 'button_bar', 'layout' => 'vertical', 'items' => [['value' => 'children', 'text' => 'Children'], ['text' => 'Custom Query', 'value' => 'custom_query']], 'buttonBarOptions' => ['layout' => 'default', 'size' => 'small']],
        false,
        false,
        [],
      ), c(
        "order_by",
        "Order By",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'none', 'text' => 'None'], ['text' => 'Post ID', 'value' => 'ID'], ['text' => 'Author', 'value' => 'author'], ['text' => 'Title', 'value' => 'title'], ['text' => 'Name (post slug)', 'value' => 'name'], ['text' => 'Date', 'value' => 'date'], ['text' => 'Modified Date', 'value' => 'modified'], ['text' => 'Random', 'value' => 'rand'], ['text' => 'Comment Count', 'value' => 'comment_count'], ['text' => 'Menu Order', 'value' => 'menu_order']], 'condition' => [[['path' => 'content.data.source', 'operand' => 'equals', 'value' => 'children']]]],
        false,
        false,
        [],
      ), c(
        "order",
        "Order",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'ASC', 'text' => 'Ascending'], ['text' => 'Descending', 'value' => 'DESC']], 'condition' => [[['path' => 'content.data.source', 'operand' => 'equals', 'value' => 'children']]]],
        false,
        false,
        [],
      ), c(
        "query",
        "Query",
        [],
        ['type' => 'wp_query', 'layout' => 'vertical', 'condition' => [[['path' => 'content.data.source', 'operand' => 'equals', 'value' => 'custom_query']]]],
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
        return ['0' =>  ['styles' => ['%%BREAKDANCE_ELEMENTS_PLUGIN_URL%%dependencies-files/breakdance-fancy-background@1/fancy-background.css'],'title' => 'Fancy Background',],];
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
        return [['cssProperty' => 'margin-top', 'location' => 'outside-top', 'affectedPropertyPath' => 'design.spacing.margin_top.%%BREAKPOINT%%'], ['cssProperty' => 'margin-bottom', 'location' => 'outside-bottom', 'affectedPropertyPath' => 'design.spacing.margin_bottom.%%BREAKPOINT%%']];
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
        return ['design.layout_v2.layout', 'design.layout_v2.h_vertical_at', 'design.layout_v2.h_alignment_when_vertical', 'design.layout_v2.a_display', 'design.child.background.image', 'design.child.background.overlay.image', 'design.child.background.image_settings.unset_image_at', 'design.child.background.image_settings.size', 'design.child.background.image_settings.height', 'design.child.background.image_settings.repeat', 'design.child.background.image_settings.position', 'design.child.background.image_settings.left', 'design.child.background.image_settings.top', 'design.child.background.image_settings.attachment', 'design.child.background.image_settings.custom_position', 'design.child.background.image_settings.width', 'design.child.background.overlay.image_settings.custom_position', 'design.child.background.image_size', 'design.child.background.overlay.image_size', 'design.child.background.overlay.type', 'design.child.background.image_settings'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
