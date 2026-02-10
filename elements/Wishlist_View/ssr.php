<?php
/**
 * Render Iconic Woo Wishlists via shortcode.
 *
 * @var array $propertiesData
 */

$shortcode = '';

if ( isset( $propertiesData['content']['content']['shortcode'] ) ) {
	$shortcode = trim( (string) $propertiesData['content']['content']['shortcode'] );
}

if ( '' === $shortcode ) {
	$shortcode = '[iconic_ww_wishlists]';
}

if ( ! shortcode_exists( 'iconic_ww_wishlists' ) ) {
	echo '<p class="bric-wishlist-view__missing">Iconic Wishlists shortcode is unavailable.</p>';
	return true;
}

$button_design = isset( $propertiesData['design']['buttons'] ) && is_array( $propertiesData['design']['buttons'] )
	? $propertiesData['design']['buttons']
	: array();

$button_style = isset( $button_design['style'] ) && '' !== $button_design['style']
	? $button_design['style']
	: 'primary';

$button_classes = array(
	'button-atom',
	'bde-button__button',
	'button-atom--' . $button_style,
);

if (
	'custom' === $button_style &&
	isset( $button_design['custom_type'] ) &&
	'preset' === $button_design['custom_type'] &&
	! empty( $button_design['preset'] )
) {
	$preset_slug = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $button_design['preset'] );
	$button_classes[] = 'button-atom--preset-' . $preset_slug;
}

$output = '<div class="bric-wishlist-view__content">' . do_shortcode( $shortcode ) . '</div>';

if ( false === strpos( $output, 'iconic-ww' ) || ! class_exists( 'DOMDocument' ) ) {
	echo $output;
	return true;
}

$dom    = new DOMDocument( '1.0', 'UTF-8' );
$errors = libxml_use_internal_errors( true );

$loaded = $dom->loadHTML(
	'<?xml encoding="utf-8" ?><div id="bric-wishlist-root">' . $output . '</div>',
	LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
);

libxml_clear_errors();
libxml_use_internal_errors( $errors );

if ( ! $loaded ) {
	echo $output;
	return true;
}

$xpath = new DOMXPath( $dom );
$root  = $xpath->query( '//*[local-name()="div" and @id="bric-wishlist-root"]' )->item( 0 );

if ( ! $root instanceof DOMElement ) {
	echo $output;
	return true;
}

$class_tokens = static function( DOMElement $element ) {
	$class_name = $element->getAttribute( 'class' );

	if ( '' === trim( $class_name ) ) {
		return array();
	}

	$tokens = preg_split( '/\s+/', trim( $class_name ) );
	return array_values( array_unique( array_filter( $tokens ) ) );
};

$set_tokens = static function( DOMElement $element, array $tokens ) {
	$element->setAttribute( 'class', implode( ' ', array_values( array_unique( array_filter( $tokens ) ) ) ) );
};

$has_token = static function( array $tokens, $token ) {
	return in_array( $token, $tokens, true );
};

$table_nodes = $xpath->query( './/*[local-name()="table" and contains(concat(" ", normalize-space(@class), " "), " iconic-ww-table ")]', $root );
$tables      = array();

if ( $table_nodes instanceof DOMNodeList ) {
	foreach ( $table_nodes as $table ) {
		if ( $table instanceof DOMElement ) {
			$tables[] = $table;
		}
	}
}

foreach ( $tables as $table ) {
	$wrapper = $dom->createElement( 'div' );
	$wrapper->setAttribute( 'class', 'bric-wishlist-view__table-scroll' );

	$parent = $table->parentNode;
	if ( ! $parent ) {
		continue;
	}

	$parent->insertBefore( $wrapper, $table );
	$wrapper->appendChild( $table );
}

$button_nodes = $xpath->query(
	'.//*[(local-name()="a" or local-name()="button") and (contains(concat(" ", normalize-space(@class), " "), " iconic-ww-button ") or contains(concat(" ", normalize-space(@class), " "), " wc-backward "))]',
	$root
);
$buttons      = array();

if ( $button_nodes instanceof DOMNodeList ) {
	foreach ( $button_nodes as $button ) {
		if ( $button instanceof DOMElement ) {
			$buttons[] = $button;
		}
	}
}

foreach ( $buttons as $button ) {
	$tokens = $class_tokens( $button );

	if ( $has_token( $tokens, 'iconic-ww-button--delete' ) || $has_token( $tokens, 'iconic-ww-button--social' ) ) {
		continue;
	}

	$tokens = array_merge( $tokens, $button_classes );
	$set_tokens( $button, $tokens );
}

$root->removeAttribute( 'id' );

echo $dom->saveHTML( $root );
