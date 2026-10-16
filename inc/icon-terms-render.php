<?php
/**
 * Accessible server rendering for the Icon Terms element.
 *
 * @package BreakdanceBootstrap
 */

namespace BricBreakdance;

defined( 'ABSPATH' ) || exit;

/**
 * Give a decorative SVG unique IDs while preserving internal references.
 *
 * @param string $svg Administrator-selected Breakdance SVG markup.
 * @return string
 */
function prepare_icon_terms_svg( $svg ) {
	if ( ! is_string( $svg ) || '' === trim( $svg ) ) {
		return '';
	}

	$processor = new \WP_HTML_Tag_Processor( $svg );
	$ids       = array();
	$has_svg   = false;

	while ( $processor->next_tag() ) {
		$id = $processor->get_attribute( 'id' );

		if ( is_string( $id ) && '' !== $id ) {
			$ids[ $id ] = wp_unique_id( 'bric-term-icon-' );
			$processor->set_attribute( 'id', $ids[ $id ] );
		}

		if ( 'SVG' === $processor->get_tag() ) {
			$has_svg = true;
			$processor->set_attribute( 'aria-hidden', 'true' );
			$processor->set_attribute( 'focusable', 'false' );
			$processor->remove_attribute( 'role' );
			$processor->remove_attribute( 'aria-label' );
			$processor->remove_attribute( 'aria-labelledby' );
			$processor->remove_attribute( 'aria-describedby' );
		}
	}

	if ( ! $has_svg ) {
		return '';
	}

	$processor = new \WP_HTML_Tag_Processor( $processor->get_updated_html() );

	while ( $processor->next_tag() ) {
		foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $attribute ) {
			$value = $processor->get_attribute( $attribute );

			if ( ! is_string( $value ) ) {
				continue;
			}

			if ( in_array( $attribute, array( 'href', 'xlink:href' ), true ) ) {
				if ( 0 === strpos( $value, '#' ) && isset( $ids[ substr( $value, 1 ) ] ) ) {
					$processor->set_attribute( $attribute, '#' . $ids[ substr( $value, 1 ) ] );
				}
				continue;
			}

			if ( in_array( $attribute, array( 'aria-labelledby', 'aria-describedby' ), true ) ) {
				$updated = preg_replace_callback(
					'/\S+/',
					static function ( $match ) use ( $ids ) {
						return $ids[ $match[0] ] ?? $match[0];
					},
					$value
				);
			} else {
				$updated = preg_replace_callback(
					'/url\(\s*(["\']?)#([^)"\'\s]+)\1\s*\)/i',
					static function ( $match ) use ( $ids ) {
						return isset( $ids[ $match[2] ] ) ? 'url(#' . $ids[ $match[2] ] . ')' : $match[0];
					},
					$value
				);
			}

			if ( $updated !== $value ) {
				$processor->set_attribute( $attribute, $updated );
			}
		}
	}

	return preg_replace_callback(
		'/(<style\b[^>]*>)(.*?)(<\/style>)/is',
		static function ( $style ) use ( $ids ) {
			$css = preg_replace_callback(
				'/#([a-zA-Z_][a-zA-Z0-9_-]*)\b/',
				static function ( $match ) use ( $ids ) {
					return isset( $ids[ $match[1] ] ) ? '#' . $ids[ $match[1] ] : $match[0];
				},
				$style[2]
			);

			return $style[1] . $css . $style[3];
		},
		$processor->get_updated_html()
	);
}

/**
 * Render configured taxonomy terms assigned to the current post.
 *
 * Missing display settings preserve existing icon-only links.
 *
 * @param array $properties Breakdance element properties.
 * @param int   $post_id    Post whose assigned terms should be displayed.
 * @return string
 */
function render_icon_terms( $properties, $post_id ) {
	$controls   = $properties['content']['controls'] ?? array();
	$taxonomy   = sanitize_key( $controls['taxonomy'] ?? '' );
	$configured = $controls['terms'] ?? array();

	if ( ! $post_id || ! taxonomy_exists( $taxonomy ) || ! is_array( $configured ) || empty( $configured ) ) {
		return '';
	}

	$post_terms = wp_get_post_terms( $post_id, $taxonomy );

	if ( is_wp_error( $post_terms ) || empty( $post_terms ) ) {
		return '';
	}

	$assigned   = array_column( $post_terms, null, 'term_id' );
	$show_names = filter_var( $controls['show_term_names'] ?? false, FILTER_VALIDATE_BOOLEAN );
	$link_terms = ! array_key_exists( 'link_terms', $controls ) || filter_var( $controls['link_terms'], FILTER_VALIDATE_BOOLEAN );
	$label      = trim( wp_strip_all_tags( $controls['label'] ?? '' ) );
	$items      = array();

	foreach ( $configured as $item ) {
		$term_id = absint( $item['term'] ?? 0 );

		if ( ! isset( $assigned[ $term_id ] ) ) {
			continue;
		}

		$term = $assigned[ $term_id ];
		$svg  = prepare_icon_terms_svg( $item['icon']['svgCode'] ?? '' );

		if ( ! $show_names && '' === $svg ) {
			continue;
		}

		$body = '' === $svg ? '' : '<span class="icon breakdance-icon-atom" aria-hidden="true">' . $svg . '</span>';

		if ( $show_names ) {
			$body .= '<span class="icon-term-name">' . esc_html( $term->name ) . '</span>';
		}

		if ( $link_terms ) {
			$url = get_term_link( $term );

			if ( is_wp_error( $url ) ) {
				continue;
			}

			/* translators: %s: Taxonomy term name. */
			$link_label = sprintf( __( 'View entries tagged %s', 'breakdance-bootstrap' ), $term->name );
			$items[]    = '<li><a class="icon-term-item" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $link_label ) . '">' . $body . '</a></li>';
		} else {
			$attributes = $show_names ? '' : ' role="img" aria-label="' . esc_attr( $term->name ) . '"';
			$items[]    = '<li><span class="icon-term-item"' . $attributes . '>' . $body . '</span></li>';
		}
	}

	if ( empty( $items ) ) {
		return '';
	}

	$html = '' === $label ? '' : '<span class="icon-terms-caption">' . esc_html( $label ) . '</span>';

	$list_class = 'icon-terms-list' . ( $show_names ? ' icon-terms-with-names' : '' );

	return $html . '<ul class="' . esc_attr( $list_class ) . '">' . implode( '', $items ) . '</ul>';
}
