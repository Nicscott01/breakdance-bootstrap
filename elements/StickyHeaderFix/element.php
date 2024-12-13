<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\Stickyheaderfix",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class Stickyheaderfix extends \Breakdance\Elements\Element
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
        return 'StickyHeaderFix';
    }

    static function className()
    {
        return 'bric-sticky-header-fix';
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
        "controls",
        "Controls",
        [c(
        "notice",
        "Notice",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'default', 'content' => '<p>Use this if you set your Header Builder to sticky. It will push the content down in the .bde-section that occurs right after the Header Builder. </p>']],
        false,
        false,
        [],
      ), c(
        "behavior",
        "Behavior",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'push', 'text' => 'Push flow down'], ['text' => 'Prevent text from overlap', 'value' => 'text-push']]],
        false,
        false,
        [],
      ), c(
        "extra_padding",
        "Extra Padding",
        [],
        ['type' => 'unit', 'layout' => 'vertical', 'condition' => [[['path' => 'content.controls.behavior', 'operand' => 'equals', 'value' => 'text-push']]]],
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
        return ['0' =>  ['inlineScripts' => ['(function() {
    function setStickyFixHeight() {
        const stickyHeader = document.querySelector(\'.bde-header-builder--sticky:first-of-type\');
        const stickyFix = document.querySelector( \'%%SELECTOR%%\' );
            
        if (stickyHeader && stickyFix) {
            const headerHeight = stickyHeader.offsetHeight;
            //stickyFix.style.height = headerHeight + \'px\';
          	document.documentElement.style.setProperty(\'--bric-sticky-header-height\', headerHeight + \'px\');
          
        }
    }

    // Set height on page load
    window.addEventListener(\'DOMContentLoaded\', setStickyFixHeight);
	window.addEventListener(\'load\', setStickyFixHeight);
    // Recalculate height on window resize
    window.addEventListener(\'resize\', setStickyFixHeight);
})();
'],],];
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
        return ["type" => "section",   ];
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
