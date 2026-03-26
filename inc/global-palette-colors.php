<?php

namespace BricBreakdance\ColorPalette;

/**
 * Return Breakdance palette colors as stored in global settings.
 */
function get_breakdance_palette_colors() {

    if ( ! function_exists( '\Breakdance\Data\get_global_settings_array' ) ) {
        return [];
    }

    $global_breakdance_settings = \Breakdance\Data\get_global_settings_array();

    $palette_colors = $global_breakdance_settings['settings']['colors']['palette']['colors'] ?? null;

    if ( ! is_array( $palette_colors ) ) {
        return [];
    }

    return $palette_colors;
}

/**
 * Return Breakdance palette in Gutenberg's expected shape.
 */
function get_breakdance_editor_palette() {

    $palette = [];

    foreach ( get_breakdance_palette_colors() as $color ) {
        $label = isset( $color['label'] ) ? (string) $color['label'] : '';
        $value = isset( $color['value'] ) ? trim( (string) $color['value'] ) : '';

        if ( $label === '' || $value === '' ) {
            continue;
        }

        $palette[] = [
            'name'  => $label,
            'slug'  => 'bd-palette-' . sanitize_title( $label ),
            'color' => $value,
        ];
    }

    return $palette;
}

/**
 * Return unique Breakdance palette hex/rgb values for Fluent Forms.
 */
function get_breakdance_palette_values() {

    $values = [];

    foreach ( get_breakdance_palette_colors() as $color ) {
        $value = isset( $color['value'] ) ? trim( (string) $color['value'] ) : '';

        if ( $value !== '' ) {
            $values[] = $value;
        }
    }

    return array_values( array_unique( $values ) );
}

/**
 * Inject Breakdance palette colors into Element UI color pickers used by Fluent Forms.
 */
function add_fluentform_palette_inline_script( $script_handle ) {

    $palette_values = get_breakdance_palette_values();

    if ( empty( $palette_values ) ) {
        return;
    }

    $palette_json = wp_json_encode( array_values( $palette_values ) );

    $script = <<<JS
(function () {
    var palette = {$palette_json};

    if (!Array.isArray(palette) || !palette.length) {
        return;
    }

    function samePalette(candidate) {
        if (!Array.isArray(candidate) || candidate.length !== palette.length) {
            return false;
        }

        for (var i = 0; i < palette.length; i++) {
            if (candidate[i] !== palette[i]) {
                return false;
            }
        }

        return true;
    }

    function updatePalette(vm, property) {
        if (!Object.prototype.hasOwnProperty.call(vm, property) || samePalette(vm[property])) {
            return false;
        }

        if (typeof vm.\$set === 'function') {
            vm.\$set(vm, property, palette.slice());
        } else {
            vm[property] = palette.slice();
        }

        return true;
    }

    function applyPalette(vm) {
        if (!vm || typeof vm !== 'object') {
            return;
        }

        var updated = false;

        if (updatePalette(vm, 'predefine')) {
            updated = true;
        }

        if (updatePalette(vm, 'predefinedColors')) {
            updated = true;
        }

        if (updated && typeof vm.\$forceUpdate === 'function') {
            vm.\$forceUpdate();
        }

        if (Array.isArray(vm.\$children)) {
            vm.\$children.forEach(applyPalette);
        }
    }

    function patchNode(node) {
        if (!node || node.nodeType !== 1) {
            return;
        }

        if (node.__vue__) {
            applyPalette(node.__vue__);
        }

        if (typeof node.querySelectorAll !== 'function') {
            return;
        }

        node.querySelectorAll('*').forEach(function (element) {
            if (element.__vue__) {
                applyPalette(element.__vue__);
            }
        });
    }

    function patchAll() {
        patchNode(document.body);
    }

    function queuePatch() {
        window.requestAnimationFrame(patchAll);
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('.el-color-picker, .el-color-picker__trigger, .el-color-picker__color')) {
            queuePatch();
        }
    }, true);

    document.addEventListener('focusin', function (event) {
        if (event.target.closest('.el-color-picker, .el-color-picker__trigger, .el-color-picker__color')) {
            queuePatch();
        }
    }, true);

    if ('MutationObserver' in window && document.body) {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(patchNode);
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', patchAll, { once: true });
    } else {
        patchAll();
    }
})();
JS;

    wp_add_inline_script( $script_handle, $script, 'after' );
}


/**
 * 
 * Register Breakdance Global Colors for Gutenberg.
 * TODO: Breakdance doesn't require you to put colors into the palette first. So Primary and heading, text, etc.
 * can be additional values. Right now were' only looking at the palette.
 */
add_action( 'init', function() {
    $palette = get_breakdance_editor_palette();

    if ( empty( $palette ) ) {
        return;
    }

    \add_theme_support( 'editor-color-palette', $palette );

}, 0 );

add_filter( 'fluentform/editor_vars', function( $data ) {

    $data['breakdance_palette_colors'] = get_breakdance_palette_values();

    return $data;

}, 10, 1 );

add_action( 'fluentform/editor_script_loaded', function() {
    add_fluentform_palette_inline_script( 'fluentform_editor_script' );
}, 20 );

add_action( 'fluentform/form_styler', function() {
    add_fluentform_palette_inline_script( 'fluentform_styler' );
}, 20 );

add_action( 'fluentform/form_application_view_conversational_design', function() {
    add_fluentform_palette_inline_script( 'fluent_forms_conversational_design' );
}, 20 );



add_filter( 'fluent_crm/theme_pref', function( $array ) {

    
    return $array;

}, 10, 1 );
