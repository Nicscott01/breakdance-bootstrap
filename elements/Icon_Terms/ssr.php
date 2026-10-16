<?php
/**
 * Server-render Icon Terms for the current loop post.
 *
 * @package BreakdanceBootstrap
 */

defined( 'ABSPATH' ) || exit;

// SVG markup is administrator-selected; the renderer escapes all text and URLs.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo \BricBreakdance\render_icon_terms( $propertiesData, get_the_ID() );
