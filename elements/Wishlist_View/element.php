<?php

namespace BricBreakdanceElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;

\Breakdance\ElementStudio\registerElementForEditing(
	"BricBreakdanceElements\\WishlistView",
	\Breakdance\Util\getdirectoryPathRelativeToPluginFolder( __DIR__ )
);

class WishlistView extends \Breakdance\Elements\Element {
	static function uiIcon() {
		return 'SquareIcon';
	}

	static function tag() {
		return 'div';
	}

	static function tagOptions() {
		return array();
	}

	static function tagControlPath() {
		return false;
	}

	static function name() {
		return 'Wishlist View';
	}

	static function className() {
		return 'bric-wishlist-view';
	}

	static function category() {
		return 'other';
	}

	static function badge() {
		return false;
	}

	static function slug() {
		return __CLASS__;
	}

	static function template() {
		return file_get_contents( __DIR__ . '/html.twig' );
	}

	static function defaultCss() {
		return file_get_contents( __DIR__ . '/default.css' );
	}

	static function defaultProperties() {
		return array(
			'content' => array(
				'content' => array(
					'shortcode' => '[iconic_ww_wishlists]',
				),
			),
		);
	}

	static function defaultChildren() {
		return false;
	}

	static function cssTemplate() {
		return file_get_contents( __DIR__ . '/css.twig' );
	}

	static function designControls() {
		return array(
			c(
				'layout',
				'Layout',
				array(
					c(
						'max_width',
						'Max Width',
						array(),
						array( 'type' => 'unit', 'layout' => 'inline' ),
						true,
						false,
						array()
					),
					c(
						'background',
						'Background',
						array(),
						array( 'type' => 'color', 'layout' => 'inline' ),
						false,
						false,
						array()
					),
					getPresetSection( 'EssentialElements\\spacing_margin_y', 'Margin', 'margin', array( 'type' => 'popout' ) ),
					getPresetSection( 'EssentialElements\\spacing_padding_all', 'Padding', 'padding', array( 'type' => 'popout' ) ),
					getPresetSection( 'EssentialElements\\borders', 'Borders', 'borders', array( 'type' => 'popout' ) ),
				),
				array( 'type' => 'section' ),
				false,
				false,
				array()
			),
			c(
				'typography',
				'Typography',
				array(
					getPresetSection( 'EssentialElements\\typography', 'Base', 'base', array( 'type' => 'popout' ) ),
					getPresetSection( 'EssentialElements\\typography', 'Links', 'links', array( 'type' => 'popout' ) ),
					getPresetSection( 'EssentialElements\\typography', 'Table Heading', 'table_heading', array( 'type' => 'popout' ) ),
					getPresetSection( 'EssentialElements\\typography', 'Buttons', 'buttons', array( 'type' => 'popout' ) ),
				),
				array( 'type' => 'section' ),
					false,
					false,
					array()
				),
				getPresetSection( 'EssentialElements\\AtomV1ButtonDesign', 'Buttons', 'buttons', array( 'type' => 'popout' ) ),
				c(
					'responsive_tables',
					'Responsive Tables',
					array(
						c(
							'enable_table_scroll',
							'Enable Horizontal Scroll',
							array(),
							array( 'type' => 'toggle', 'layout' => 'inline' ),
							false,
							false,
							array()
						),
						c(
							'scroll_at',
							'Enable At',
							array(),
							array(
								'type'              => 'breakpoint_dropdown',
								'layout'            => 'inline',
								'breakpointOptions' => array(
									'multiple'   => false,
									'enableNever' => true,
								),
							),
							false,
							false,
							array()
						),
						c(
							'min_table_width',
							'Minimum Table Width',
							array(),
							array( 'type' => 'unit', 'layout' => 'inline' ),
							true,
							false,
							array()
						),
					),
					array( 'type' => 'section' ),
					false,
					false,
					array()
				),
				c(
					'table',
					'Table',
				array(
					c( 'header_background', 'Header Background', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'row_border_color', 'Row Border Color', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'cell_padding_y', 'Cell Vertical Padding', array(), array( 'type' => 'unit', 'layout' => 'inline' ), true, false, array() ),
					c( 'cell_padding_x', 'Cell Horizontal Padding', array(), array( 'type' => 'unit', 'layout' => 'inline' ), true, false, array() ),
				),
				array( 'type' => 'section' ),
				false,
				false,
				array()
			),
			c(
				'forms',
				'Forms',
				array(
					c( 'input_background', 'Input Background', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'input_text_color', 'Input Text Color', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'input_border_color', 'Input Border Color', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'input_border_radius', 'Input Border Radius', array(), array( 'type' => 'unit', 'layout' => 'inline' ), true, false, array() ),
				),
				array( 'type' => 'section' ),
				false,
				false,
				array()
			),
			c(
				'notices',
				'Notices',
				array(
					c( 'info_background', 'Info Background', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'info_text_color', 'Info Text Color', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'error_background', 'Error Background', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
					c( 'error_text_color', 'Error Text Color', array(), array( 'type' => 'color', 'layout' => 'inline' ), false, false, array() ),
				),
				array( 'type' => 'section' ),
				false,
				false,
				array()
			),
		);
	}

	static function contentControls() {
		return array(
			c(
				'content',
				'Content',
				array(
					c(
						'shortcode',
						'Shortcode',
						array(),
						array(
							'type'        => 'text',
							'layout'      => 'vertical',
							'placeholder' => '[iconic_ww_wishlists]',
							'textOptions' => array(
								'format'    => 'plain',
								'multiline' => false,
							),
						),
						false,
						false,
						array()
					),
				),
				array( 'type' => 'section', 'layout' => 'vertical' ),
				false,
				false,
				array()
			),
		);
	}

	static function settingsControls() {
		return array();
	}

	static function dependencies() {
		return false;
	}

	static function settings() {
		return false;
	}

	static function addPanelRules() {
		return false;
	}

	static public function actions() {
		return false;
	}

	static function nestingRule() {
		return array( 'type' => 'final' );
	}

	static function spacingBars() {
		return false;
	}

	static function attributes() {
		return false;
	}

	static function experimental() {
		return false;
	}

	static function order() {
		return 0;
	}

	static function dynamicPropertyPaths() {
		return false;
	}

	static function additionalClasses() {
		return false;
	}

	static function projectManagement() {
		return false;
	}

	static function propertyPathsToWhitelistInFlatProps() {
		return false;
	}

	static function propertyPathsToSsrElementWhenValueChanges() {
		return array( 'content.content.shortcode' );
	}
}
