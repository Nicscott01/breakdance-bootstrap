<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\Fluentbooking",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class Fluentbooking extends \Breakdance\Elements\Element
{
    static function uiIcon()
    {
        return '<svg width="96" height="101" viewBox="0 0 96 101" fill="#fff" xmlns="http://www.w3.org/2000/svg">   <rect x="25.5746" width="6.39365" height="15.9841" rx="3.19683" fill="#fff"/>   <rect x="63.9365" width="6.39365" height="15.9841" rx="3.19683" fill="#fff"/>   <path fill-rule="evenodd" clip-rule="evenodd" d="M54.878 53.0655C54.544 55.6678 53.4646 58.035 51.8535 59.9427C50.1623 61.9572 47.886 63.4614 45.2863 64.1988L45.1741 64.2309L44.9203 64.2976L44.8989 64.303L24.7671 69.7V65.019C24.7671 64.9148 24.7671 64.8106 24.7778 64.7064C24.8953 62.748 26.127 61.0862 27.8476 60.3514C28.0427 60.2659 28.2431 60.1938 28.4515 60.1377L28.6412 60.0869L54.8753 53.0575V53.0655H54.878Z" fill="#fff"/>   <path fill-rule="evenodd" clip-rule="evenodd" d="M71.1411 35.8059C70.4571 41.1467 66.6178 45.5017 61.5494 46.9391L61.4372 46.9712L61.1861 47.038H61.1834L61.162 47.0433L24.7671 56.7953V52.1144C24.7671 50.0197 26.0362 48.2216 27.8476 47.4468L28.4515 47.233L28.6385 47.1823L71.1384 35.7952V35.8059H71.1411Z" fill="#fff"/>   <path fill-rule="evenodd" clip-rule="evenodd" d="M19.9802 11.1889H75.9246C83.4282 11.1889 89.5111 17.2718 89.5111 24.7754V70H95.9048V24.7754C95.9048 13.7406 86.9593 4.79523 75.9246 4.79523H19.9802C8.94542 4.79523 0 13.7406 0 24.7754V80.7198C0 91.7546 8.94542 100.7 19.9802 100.7L64.9524 100.7V94.3063H19.9802C12.4765 94.3063 6.39365 88.2234 6.39365 80.7198V24.7754C6.39365 17.2718 12.4765 11.1889 19.9802 11.1889Z" fill="#fff"/>   <path fill-rule="evenodd" clip-rule="evenodd" d="M95.9524 70.7477V69.7H64.9524V100.7H66.0001L95.9524 70.7477Z" fill="#fff"/> </svg>';
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
        return 'FluentBooking';
    }

    static function className()
    {
        return 'bric-fluent-booking';
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
        "data",
        "Data",
        [c(
        "calendar",
        "Calendar",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'dropdownOptions' => ['populate' => ['path' => '', 'text' => '', 'value' => '', 'fetchDataAction' => 'bdbs_get_fluent_booking_calendars', 'fetchContextPath' => '', 'refetchPaths' => []]]],
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
        return ['0' =>  ['title' => 'Fluent Booking App','scripts' => ['%%BREAKDANCE_REUSABLE_FLUENT_BOOKING_LOADER_JS%%'],],];
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
        return ['type' => 'final'];
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

    static function availableIn()
    {
        return ['breakdance'];
    }


    static function order()
    {
        return 0;
    }

    static function dynamicPropertyPaths()
    {
        return false;
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
        return ['content.data.calendar'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
