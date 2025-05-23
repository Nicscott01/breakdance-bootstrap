<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\Dynamic_Popup",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class Dynamic_Popup extends \Breakdance\Elements\Element
{
    static function uiIcon()
    {
        return 'ExpandIcon';
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
        return 'Dynamic Popup';
    }

    static function className()
    {
        return 'bde-popup';
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
        return ['design' => ['overlay' => ['color' => ['breakpoint_base' => '#000000BF']], 'container' => ['background' => '#FFFFFFFF', 'position' => ['align' => ['breakpoint_base' => 'center'], 'vertical_align' => ['breakpoint_base' => 'center']], 'size' => ['width' => ['breakpoint_base' => 'custom'], 'custom_width' => ['breakpoint_base' => ['number' => 800, 'unit' => 'px', 'style' => '800px']], 'height' => ['breakpoint_base' => 'custom'], 'custom_height' => ['breakpoint_base' => ['number' => 600, 'unit' => 'px', 'style' => '600px']]], 'borders' => null, 'padding' => null, 'layout' => ['align' => null, 'vertical_align' => null]], 'close_button' => ['size' => ['breakpoint_base' => ['number' => 20, 'unit' => 'px', 'style' => '20px']], 'wrapped' => true, 'background' => '#000000BD', 'color' => '#FFFFFFFF', 'padding' => ['breakpoint_base' => ['number' => 6, 'unit' => 'px', 'style' => '6px']]], 'popup' => ['position' => null, 'background' => null, 'size' => null, 'padding' => null, 'borders' => null, 'layout' => ['align' => null], 'animation' => null]], 'content' => ['popup' => ['disable_overlay' => null, 'allow_scroll' => null, 'disable_close_button' => false, 'popup_can_t_be_closed' => false, 'automatically_close_after' => null, 'show_close_button_after' => null]]];
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
        "popup",
        "Popup",
        [c(
        "position",
        "Position",
        [c(
        "align",
        "Align",
        [],
        ['type' => 'button_bar', 'layout' => 'inline', 'items' => ['0' => ['value' => 'left', 'text' => 'Left', 'icon' => 'FlexAlignLeftIcon'], '1' => ['text' => 'Center', 'value' => 'center', 'icon' => 'FlexAlignCenterHorizontalIcon'], '2' => ['text' => 'Right', 'value' => 'right', 'icon' => 'FlexAlignRightIcon']], 'buttonBarOptions' => ['size' => 'small']],
        true,
        false,
        [],
      ), c(
        "vertical_align",
        "Vertical Align",
        [],
        ['type' => 'button_bar', 'layout' => 'inline', 'items' => ['0' => ['text' => 'Top', 'value' => 'flex-start', 'icon' => 'FlexAlignTopIcon'], '1' => ['text' => 'Middle', 'value' => 'center', 'icon' => 'FlexAlignCenterVerticalIcon'], '2' => ['text' => 'Bottom', 'value' => 'flex-end', 'icon' => 'FlexAlignBottomIcon']], 'buttonBarOptions' => ['size' => 'small']],
        true,
        false,
        [],
      ), c(
        "translate",
        "Translate",
        [c(
        "x",
        "X",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'rangeOptions' => ['min' => -100, 'max' => 100]],
        true,
        false,
        [],
      ), c(
        "y",
        "Y",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'rangeOptions' => ['min' => -100, 'max' => 100]],
        true,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'inline', 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'inline', 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      ), c(
        "background",
        "Background",
        [],
        ['type' => 'color', 'layout' => 'inline', 'colorOptions' => ['type' => 'solidAndGradient']],
        false,
        false,
        [],
      ), c(
        "size",
        "Size",
        [c(
        "width",
        "Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "height",
        "Height",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "max_height",
        "Max Height",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      )],
        ['type' => 'section', 'sectionOptions' => ['type' => 'popout']],
        false,
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
     ), getPresetSection(
      "EssentialElements\\simpleLayout",
      "Layout",
      "layout",
       ['type' => 'popout']
     ), c(
        "animation",
        "Animation",
        [c(
        "entrance",
        "Entrance",
        [c(
        "animation_type",
        "Animation Type",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => ['0' => ['text' => 'Fade', 'value' => 'fade'], '1' => ['text' => 'Slide Up', 'value' => 'slideUp'], '2' => ['text' => 'Slide Down', 'value' => 'slideDown'], '3' => ['text' => 'Slide Left', 'value' => 'slideLeft'], '4' => ['text' => 'Slide Right', 'value' => 'slideRight'], '5' => ['text' => 'Flip Up', 'value' => 'flipUp'], '6' => ['text' => 'Flip Down', 'value' => 'flipDown'], '7' => ['text' => 'Flip Left', 'value' => 'flipLeft'], '8' => ['text' => 'Flip Right', 'value' => 'flipRight'], '9' => ['text' => 'Zoom In', 'value' => 'zoomIn'], '10' => ['text' => 'Zoom Out', 'value' => 'zoomOut']]],
        false,
        false,
        [],
      ), c(
        "duration",
        "Duration",
        [],
        ['type' => 'unit', 'unitOptions' => ['types' => ['0' => 'ms', '1' => 's']], 'rangeOptions' => ['min' => 0, 'max' => 3000, 'step' => 50], 'condition' => ['path' => '%%CURRENTPATH%%.animation_type', 'operand' => 'is set']],
        false,
        false,
        [],
      ), c(
        "delay",
        "Delay",
        [],
        ['type' => 'unit', 'unitOptions' => ['types' => ['0' => 'ms', '1' => 's']], 'rangeOptions' => ['min' => 0, 'max' => 3000, 'step' => 50], 'condition' => ['path' => '%%CURRENTPATH%%.animation_type', 'operand' => 'is set']],
        false,
        false,
        [],
      ), c(
        "ease",
        "Ease",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => ['0' => ['text' => 'Linear', 'value' => 'linear'], '1' => ['text' => 'Expo In', 'value' => 'expo.in'], '2' => ['text' => 'Expo Out', 'value' => 'expo.out'], '3' => ['text' => 'Expo InOut', 'value' => 'expo.inOut'], '4' => ['text' => 'Power1 In', 'value' => 'power1.in'], '5' => ['text' => 'Power1 Out', 'value' => 'power1.out'], '6' => ['text' => 'Power1 InOut', 'value' => 'power1.inOut'], '7' => ['text' => 'Power2 In', 'value' => 'power2.in'], '8' => ['text' => 'Power2 Out', 'value' => 'power2.out'], '9' => ['text' => 'Power2 InOut', 'value' => 'power2.inOut'], '10' => ['text' => 'Power3 In', 'value' => 'power3.in'], '11' => ['text' => 'Power3 Out', 'value' => 'power3.out'], '12' => ['text' => 'Power3 InOut', 'value' => 'power3.inOut'], '13' => ['text' => 'Power4 In', 'value' => 'power4.in'], '14' => ['text' => 'Power4 Out', 'value' => 'power4.out'], '15' => ['text' => 'Power4 InOut', 'value' => 'power4.inOut'], '16' => ['text' => 'Back In', 'value' => 'back.in'], '17' => ['text' => 'Back Out', 'value' => 'back.out'], '18' => ['text' => 'Back InOut', 'value' => 'back.inOut'], '19' => ['text' => 'Elastic In', 'value' => 'elastic.in'], '20' => ['text' => 'Elastic Out', 'value' => 'elastic.out'], '21' => ['text' => 'Elastic InOut', 'value' => 'elastic.inOut'], '22' => ['text' => 'Bounce In', 'value' => 'bounce.in'], '23' => ['text' => 'Bounce Out', 'value' => 'bounce.out'], '24' => ['text' => 'Bounce InOut', 'value' => 'bounce.inOut']], 'condition' => ['path' => '%%CURRENTPATH%%.animation_type', 'operand' => 'is set', 'value' => '']],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      ), c(
        "exit",
        "Exit",
        [c(
        "animation_type",
        "Animation Type",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => ['0' => ['text' => 'Fade', 'value' => 'fade'], '1' => ['text' => 'Slide Up', 'value' => 'slideUp'], '2' => ['text' => 'Slide Down', 'value' => 'slideDown'], '3' => ['text' => 'Slide Left', 'value' => 'slideLeft'], '4' => ['text' => 'Slide Right', 'value' => 'slideRight'], '5' => ['text' => 'Flip Up', 'value' => 'flipUp'], '6' => ['text' => 'Flip Down', 'value' => 'flipDown'], '7' => ['text' => 'Flip Left', 'value' => 'flipLeft'], '8' => ['text' => 'Flip Right', 'value' => 'flipRight'], '9' => ['text' => 'Zoom In', 'value' => 'zoomIn'], '10' => ['text' => 'Zoom Out', 'value' => 'zoomOut']]],
        false,
        false,
        [],
      ), c(
        "duration",
        "Duration",
        [],
        ['type' => 'unit', 'unitOptions' => ['types' => ['0' => 'ms', '1' => 's']], 'rangeOptions' => ['min' => 0, 'max' => 3000, 'step' => 50], 'condition' => ['path' => '%%CURRENTPATH%%.animation_type', 'operand' => 'is set']],
        false,
        false,
        [],
      ), c(
        "delay",
        "Delay",
        [],
        ['type' => 'unit', 'unitOptions' => ['types' => ['0' => 'ms', '1' => 's']], 'rangeOptions' => ['min' => 0, 'max' => 3000, 'step' => 50], 'condition' => ['path' => '%%CURRENTPATH%%.animation_type', 'operand' => 'is set']],
        false,
        false,
        [],
      ), c(
        "ease",
        "Ease",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => ['0' => ['text' => 'Linear', 'value' => 'linear'], '1' => ['text' => 'Expo In', 'value' => 'expo.in'], '2' => ['text' => 'Expo Out', 'value' => 'expo.out'], '3' => ['text' => 'Expo InOut', 'value' => 'expo.inOut'], '4' => ['text' => 'Power1 In', 'value' => 'power1.in'], '5' => ['text' => 'Power1 Out', 'value' => 'power1.out'], '6' => ['text' => 'Power1 InOut', 'value' => 'power1.inOut'], '7' => ['text' => 'Power2 In', 'value' => 'power2.in'], '8' => ['text' => 'Power2 Out', 'value' => 'power2.out'], '9' => ['text' => 'Power2 InOut', 'value' => 'power2.inOut'], '10' => ['text' => 'Power3 In', 'value' => 'power3.in'], '11' => ['text' => 'Power3 Out', 'value' => 'power3.out'], '12' => ['text' => 'Power3 InOut', 'value' => 'power3.inOut'], '13' => ['text' => 'Power4 In', 'value' => 'power4.in'], '14' => ['text' => 'Power4 Out', 'value' => 'power4.out'], '15' => ['text' => 'Power4 InOut', 'value' => 'power4.inOut'], '16' => ['text' => 'Back In', 'value' => 'back.in'], '17' => ['text' => 'Back Out', 'value' => 'back.out'], '18' => ['text' => 'Back InOut', 'value' => 'back.inOut'], '19' => ['text' => 'Elastic In', 'value' => 'elastic.in'], '20' => ['text' => 'Elastic Out', 'value' => 'elastic.out'], '21' => ['text' => 'Elastic InOut', 'value' => 'elastic.inOut'], '22' => ['text' => 'Bounce In', 'value' => 'bounce.in'], '23' => ['text' => 'Bounce Out', 'value' => 'bounce.out'], '24' => ['text' => 'Bounce InOut', 'value' => 'bounce.inOut']], 'condition' => ['path' => '%%CURRENTPATH%%.animation_type', 'operand' => 'is set', 'value' => '']],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\background",
      "Overlay",
      "overlay",
       ['type' => 'popout']
     ), c(
        "close_button",
        "Close Button",
        [c(
        "color",
        "Color",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        true,
        [],
      ), c(
        "size",
        "Size",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'unitOptions' => ['types' => ['0' => 'px'], 'defaultType' => 'px'], 'rangeOptions' => ['min' => 8, 'max' => 80, 'step' => 2]],
        true,
        false,
        [],
      ), c(
        "wrapped",
        "Wrapped",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "background",
        "Background",
        [],
        ['type' => 'color', 'layout' => 'inline', 'condition' => ['path' => 'design.close_button.wrapped', 'operand' => 'is set', 'value' => '']],
        false,
        true,
        [],
      ), c(
        "padding",
        "Padding",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'unitOptions' => ['types' => ['0' => 'px'], 'defaultType' => 'px'], 'rangeOptions' => ['min' => 8, 'max' => 80, 'step' => 2], 'condition' => ['path' => 'design.close_button.wrapped', 'operand' => 'is set', 'value' => '']],
        true,
        false,
        [],
      ), c(
        "radius",
        "Radius",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'condition' => ['path' => 'design.close_button.wrapped', 'operand' => 'is set', 'value' => ''], 'rangeOptions' => ['min' => 0, 'max' => 50]],
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
        "position",
        "Position",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => ['0' => ['value' => 'top-right', 'text' => 'Top Right'], '1' => ['value' => 'middle-right', 'text' => 'Middle Right'], '2' => ['value' => 'bottom-right', 'text' => 'Bottom Right'], '3' => ['value' => 'top-left', 'text' => 'Top Left'], '4' => ['value' => 'middle-left', 'text' => 'Middle Left'], '5' => ['value' => 'bottom-left', 'text' => 'Bottom Left']]],
        false,
        false,
        [],
      ), c(
        "outside",
        "Outside",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "translate",
        "Translate",
        [c(
        "x",
        "X",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'rangeOptions' => ['min' => -100, 'max' => 100]],
        false,
        false,
        [],
      ), c(
        "y",
        "Y",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'rangeOptions' => ['min' => -100, 'max' => 100]],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'inline', 'sectionOptions' => ['type' => 'popout']],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'condition' => ['path' => 'content.popup.disable_close_button', 'operand' => 'is not set', 'value' => '']],
        false,
        false,
        [],
      )];
    }

    static function contentControls()
    {
        return [c(
        "popup",
        "Popup",
        [c(
        "disable_overlay",
        "Disable Overlay",
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
      ), c(
        "show_close_button_after",
        "Show Close Button After",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'unitOptions' => ['types' => ['0' => 'ms'], 'defaultType' => 'ms'], 'condition' => ['path' => 'content.popup.disable_close_button', 'operand' => 'is not set', 'value' => '']],
        false,
        false,
        [],
      ), c(
        "popup_can_t_be_closed",
        "Popup Can't Be Closed",
        [],
        ['type' => 'toggle', 'layout' => 'inline', 'condition' => ['path' => 'content.popup.disable_close_button', 'operand' => 'is set', 'value' => '']],
        false,
        false,
        [],
      ), c(
        "automatically_close_after",
        "Automatically Close After",
        [],
        ['type' => 'unit', 'layout' => 'inline', 'unitOptions' => ['types' => ['0' => 'ms'], 'defaultType' => 'ms']],
        false,
        false,
        [],
      ), c(
        "advanced",
        "Advanced",
        [c(
        "popup_html_id",
        "Popup HTML ID",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "popup_css_class",
        "Popup CSS Class",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "keep_open_on_hashlink_clicks",
        "Keep Open On Hashlink Clicks",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'vertical', 'sectionOptions' => ['type' => 'popout']],
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
        return ['0' =>  ['title' => 'Popups','scripts' => ['%%BREAKDANCE_ELEMENTS_PLUGIN_URL%%dependencies-files/popups@1/popups.js'],'styles' => ['%%BREAKDANCE_ELEMENTS_PLUGIN_URL%%dependencies-files/popups@1/popups.css'],],'1' =>  ['title' => 'Animation','scripts' => ['%%BREAKDANCE_REUSABLE_GSAP%%','%%BREAKDANCE_ELEMENTS_PLUGIN_URL%%dependencies-files/popups@1/popup-animations.js'],'frontendCondition' => '$hasEntranceAnimation = !!\'{{design.popup.animation.entrance.animation_type}}\';
$hasExitAnimation = !!\'{{design.popup.animation.exit.animation_type}}\';
return $hasEntranceAnimation || $hasExitAnimation;',],'2' =>  ['inlineScripts' => ['if (window.breakdancePopupInstances) {
  const popupInstance = window.breakdancePopupInstances[%%POSTID%%] ?? null;
  if (popupInstance) {
    popupInstance.setOptions({
      keepOpenOnHashlinkClicks: {{ content.popup.advanced.keep_open_on_hashlink_clicks ? \'true\' : \'false\'}},
      closeOnClickOutside: {{ content.popup.popup_can_t_be_closed == 1 ? \'false\' : \'true\'}},
      closeOnEscapeKey: {{ content.popup.popup_can_t_be_closed == 1 ? \'false\' : \'true\' }},
      closeAfterMilliseconds: {{ content.popup.automatically_close_after.number ?? \'null\' }},
      showCloseButtonAfterMilliseconds: {{ content.popup.show_close_button_after.number ?? \'null\' }},
      disableScrollWhenOpen: {{ content.popup.allow_scroll == 1 ? \'false\' : \'true\' }},
      {% if design.popup.animation.entrance.animation_type is not empty %}
        entranceAnimation: {{ design.popup.animation.entrance | json_encode }},
      {% endif %}
      {% if design.popup.animation.exit.animation_type is not empty %}
        exitAnimation: {{ design.popup.animation.exit | json_encode }},
      {% endif %}
    });
  }
}
'],'title' => 'Frontend','builderCondition' => 'return false;',],'3' =>  ['title' => 'Builder','frontendCondition' => 'return false;','inlineScripts' => ['window.breakdancePostId = %%POSTID%%;
if (typeof BreakdancePopup !== \'undefined\') {
  if (!window.breakdancePopupInstances) window.breakdancePopupInstances = {};
  window.breakdancePopupInstances[%%POSTID%%] = new BreakdancePopup(%%POSTID%%, {
    closeOnClickOutside: false,
    closeOnEscapeKey: false,
  });
  window.breakdancePopupInstances[%%POSTID%%].open();
}'],'inlineStyles' => ['.breakdance .bde-popup,
.breakdance .bde-popup .breakdance-popup {
  visibility: visible;
  opacity: 1;
  z-index: var(--bde-z-index-popup);
  pointer-events: unset;
}'],],];
    }

    static function settings()
    {
        return false;
    }

    static function addPanelRules()
    {
        return ['alwaysHide' => false];
    }

    static public function actions()
    {
        return [

'onPropertyChange' => [['script' => '{% if design.popup.animation is not empty and design.popup.animation.entrance is not empty %}
  const postId = window.breakdancePostId;
  const breakdancePopupInstance = window.breakdancePopupInstances[postId];
  breakdancePopupInstance.setOptions({
    {% if design.popup.animation.entrance.animation_type is not empty %}
      entranceAnimation: {{ design.popup.animation.entrance | json_encode }},
    {% endif %}
    {% if design.popup.animation.exit.animation_type is not empty %}
      exitAnimation: {{ design.popup.animation.exit | json_encode }},
    {% endif %}
  });
  breakdancePopupInstance.playEntranceAnimation();
{% endif %}','dependencies' => ['design.popup.animation.entrance'],
],['script' => '{% if design.popup.animation is not empty and design.popup.animation.exit is not empty %}
  const postId = window.breakdancePostId;
  const breakdancePopupInstance = window.breakdancePopupInstances[postId];
  if (breakdancePopupInstance) {
    breakdancePopupInstance.setOptions({
      {% if design.popup.animation.entrance.animation_type is not empty %}
        entranceAnimation: {{ design.popup.animation.entrance | json_encode }},
      {% endif %}
      {% if design.popup.animation.exit.animation_type is not empty %}
        exitAnimation: {{ design.popup.animation.exit | json_encode }},
      {% endif %}
    });
    breakdancePopupInstance.playExitAnimation(true);
  }
{% endif %}','dependencies' => ['design.popup.animation.exit'],
],],];
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
        return 10000;
    }

    static function dynamicPropertyPaths()
    {
        return ['0' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '1' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '2' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string']];
    }

    static function additionalClasses()
    {
        return [['name' => 'breakdance-popup-open', 'template' => '{% if isBuilder %}
return true;
{% endif %}']];
    }

    static function projectManagement()
    {
        return false;
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return ['design.layout', 'design.spacing', 'design.size', 'design.entrance_animation.animation_type', 'design.popup.position.horizontal.vertical_at', 'design.popup.position', 'design.popup.size', 'design.popup.layout', 'design.overlay', 'design.close_button', 'design.close_button.wrapped'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
