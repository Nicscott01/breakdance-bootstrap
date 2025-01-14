<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "BricBreakdanceElements\\JobviteEmbed",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class JobviteEmbed extends \Breakdance\Elements\Element
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
        return 'Jobvite Embed';
    }

    static function className()
    {
        return 'nrs-jobvite-embed';
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
        "container",
        "Container",
        [c(
        "width",
        "Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "background",
        "Background",
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
     ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Padding",
      "padding",
       ['type' => 'popout']
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
      "Global",
      "global",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "H2",
      "h2",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "H3",
      "h3",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography",
      "Links",
      "links",
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
        "new_section",
        "New Section",
        [c(
        "career_site",
        "Career Site",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => 'should be a single string'],
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
        return ['0' =>  ['scripts' => ['//jobs.jobvite.com/__assets__/scripts/careersite/public/iframe.js'],'title' => 'Jobvite iFrame','inlineScripts' => ['      var l = window.location.href;
      var args = \'\';
      var k = \'\';
      var iStart = l.indexOf(\'?jvk=\');
      if (iStart == -1) iStart = l.indexOf(\'&jvk=\');
      if (iStart != -1) {
            iStart += 5;
            var iEnd = l.indexOf(\'&\', iStart);
            if (iEnd == -1) iEnd = l.length;
            k = l.substring(iStart, iEnd);
      }

      iStart = l.indexOf(\'?jvi=\');
      if (iStart == -1) iStart = l.indexOf(\'&jvi=\');
      if (iStart != -1) {
            iStart += 5;
            var iEnd = l.indexOf(\'&\', iStart);
            if (iEnd == -1) iEnd = l.length;
            args += \'&j=\' + l.substring(iStart, iEnd);
            if (!k.length) args += \'&k=Job\';
            var iStart = l.indexOf(\'?jvs=\');
            if (iStart == -1) iStart = l.indexOf(\'&jvs=\');
            if (iStart != -1){
                  iStart += 5;
                  var iEnd = l.indexOf(\'&\', iStart);
                  if (iEnd == -1) iEnd = l.length;
                  args += \'&s=\' + l.substring(iStart, iEnd);
            }
      }

      iStart = l.indexOf(\'?jvsrc=\');
      if (iStart == -1) iStart = l.indexOf(\'&jvsrc=\');
      if (iStart != -1) {
            iStart += 7;
            var iEnd = l.indexOf(\'&\', iStart);
            if (iEnd == -1) iEnd = l.length;
            args += \'&jtsrc=\' + l.substring(iStart, iEnd);
      }

      if (k.length) args += \'&k=\' + k;
      if (args.length) document.getElementById(\'jobviteframe\').src += args;
            function resizeFrame(height, scrollToTop) {
            if (scrollToTop) window.scrollTo(0, 0);
            var oFrame = document.getElementById(\'jobviteframe\');
            if (oFrame) oFrame.height = height;
      }'],],];
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
        return ['0' => ['accepts' => 'image_url', 'path' => 'design.new_section.background.layers[].image'], '1' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '2' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string'], '3' => ['path' => 'settings.advanced.attributes[].value', 'accepts' => 'string']];
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
        return ['design.new_section.background.image', 'design.new_section.background.overlay.image', 'design.new_section.background.image_settings.unset_image_at', 'design.new_section.background.image_settings.size', 'design.new_section.background.image_settings.height', 'design.new_section.background.image_settings.repeat', 'design.new_section.background.image_settings.position', 'design.new_section.background.image_settings.left', 'design.new_section.background.image_settings.top', 'design.new_section.background.image_settings.attachment', 'design.new_section.background.image_settings.custom_position', 'design.new_section.background.image_settings.width', 'design.new_section.background.overlay.image_settings.custom_position', 'design.new_section.background.image_size', 'design.new_section.background.overlay.image_size', 'design.new_section.background.overlay.type', 'design.new_section.background.image_settings'];
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
