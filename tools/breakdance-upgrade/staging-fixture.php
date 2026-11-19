<?php
/** Exercise native controls and the editor's save pipeline on an isolated site. */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(), ['local', 'staging'], true)) {
    throw new RuntimeException('This fixture only runs through WP-CLI on local/staging.');
}
if (!defined('CREARE_BD_DISPOSABLE_FIXTURE') || CREARE_BD_DISPOSABLE_FIXTURE !== true) {
    throw new RuntimeException('Explicitly enable this only on a disposable fixture installation; seed changes globals and the front page.');
}
if (!current_user_can('manage_options') || !function_exists('Breakdance\\Data\\save_document')) {
    throw new RuntimeException('Select an administrator and load Breakdance first.');
}
if (!preg_match('/^[23]\\./', __BREAKDANCE_VERSION)) {
    throw new RuntimeException('This fixture covers the v2/v3 native document contract.');
}

$action = $args[0] ?? 'inspect';
$option = 'creare_breakdance_upgrade_fixture';
$id = (int) get_option($option);

if ($action === 'seed') {
    if ($id) {
        throw new RuntimeException('The fixture already exists; inspect it instead of overwriting it.');
    }
    $stock = unserialize(file_get_contents(dirname(__BREAKDANCE_PLUGIN_FILE__) . '/plugin/themeless/fallbacks/serialized_fallbacks.dat'), ['allowed_classes' => false]);
    $stockSignatures = array_map('serialize', $stock);
    $allowed = [];
    foreach (\Breakdance\Themeless\Fallbacks\get_all_fallback_defaults() as $fallback) {
        $record = [
            'title' => $fallback->post_title,
            '_breakdance_template_settings' => \Breakdance\Data\get_meta($fallback->ID, '_breakdance_template_settings'),
            '_breakdance_data' => \Breakdance\Data\get_meta($fallback->ID, '_breakdance_data'),
        ];
        if (in_array(serialize($record), $stockSignatures, true)) {
            $allowed[] = $fallback->ID;
        }
    }
    $documents = get_posts(['post_type' => array_values(get_post_types()), 'post_status' => array_values(get_post_stati()),
                           'meta_key' => '_breakdance_data', 'numberposts' => -1, 'fields' => 'ids']);
    if (array_diff($documents, $allowed) || \Breakdance\Data\get_global_option('global_settings_json_string')) {
        throw new RuntimeException('Use a clean fixture installation with only stock fallbacks and no saved global design settings.');
    }
    $unit = static fn($number, $unit = 'px') => ['number' => $number, 'unit' => $unit, 'style' => $number . $unit];
    $node = static fn($id, $type, $properties, $children = [], $parent = 100) => [
        'id' => $id, 'data' => ['type' => 'EssentialElements\\' . $type, 'properties' => $properties], 'children' => $children, '_parentId' => $parent,
    ];
    $heading = static fn($text) => ['content' => ['content' => ['text' => $text, 'tags' => 'h2']],
                                  'design' => ['typography' => ['typography' => ['custom' => ['customTypography' => [
                                      'fontSize' => ['breakpoint_base' => $unit(35), 'breakpoint_phone_portrait' => $unit(28)],
                                      'fontFamily' => 'Arial',
                                  ]]]]]];
    $override = $heading('Native heading override');
    $override['design']['spacing']['margin_bottom'] = [
        'breakpoint_base' => $unit(1, 'em'), 'breakpoint_phone_portrait' => $unit(1.25, 'em'),
    ];
    $override['settings']['advanced']['id'] = 'fixture-heading-override';
    $default = $heading('Shared heading default');
    $default['settings']['advanced']['id'] = 'fixture-heading-default';
    $button = ['content' => ['content' => ['text' => 'Native button override', 'link' => ['url' => '#']]],
               'design' => ['spacing' => ['margin_bottom' => [
                   'breakpoint_base' => $unit(32), 'breakpoint_phone_portrait' => $unit(24),
               ]]],
               'settings' => ['advanced' => ['id' => 'fixture-button-override']]];
    $richText = ['content' => ['content' => ['text' => '<p>Shared paragraph spacing.</p><p>Last paragraph keeps its existing behavior.</p>']],
                 'settings' => ['advanced' => ['id' => 'fixture-paragraphs']]];
    $tree = ['root' => ['id' => 1, 'data' => ['type' => 'root', 'properties' => []], 'children' => [
        $node(100, 'Section', [], [
            $node(101, 'Heading', $override), $node(102, 'Button', $button),
            $node(103, 'Heading', $default), $node(104, 'RichText', $richText),
        ], 1),
    ]], '_nextNodeId' => 105, 'status' => 'exported'];
    $globals = ['settings' => ['bric_global_spacing' => ['spacing' => [
        'headings' => ['margin_bottom' => $unit(0.5, 'em')],
        'buttons' => ['margin_bottom' => $unit(8)],
        'paragraphs' => ['margin_bottom' => $unit(9)],
    ]]]];
    $id = wp_insert_post(['post_type' => 'page', 'post_title' => 'Breakdance Upgrade Fixture', 'post_status' => 'publish'], true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    \Breakdance\Data\save_document(wp_json_encode($tree), '', '', wp_json_encode($globals), '', '', '', '', '', $id);
    update_option($option, $id, false);
    update_option('page_on_front', $id);
    update_option('show_on_front', 'page');
} elseif (!$id) {
    throw new RuntimeException('Seed this disposable fixture first.');
}

$before = \Breakdance\Data\get_tree($id);
if ($action === 'resave') {
    // Same handler used by the builder's breakdance_save request. This preserves
    // the stored tree/IDs and exercises native revision/cache hooks.
    $futureLayer = \Breakdance\Data\get_meta($id, '_breakdance_futurelayer_meta');
    $futureLayer = is_string($futureLayer) ? $futureLayer : ($futureLayer ? wp_json_encode($futureLayer) : '');
    \Breakdance\Data\save_document(wp_json_encode($before), '', $futureLayer, '', '', '', '', '', '', $id);
} elseif ($action === 'clear-cache') {
    \Breakdance\Render\clearAllCssCachesAndDeleteCachedFiles();
    \Breakdance\Render\generateCacheForGlobalSettings();
    \Breakdance\Render\generateCacheForPost($id);
} elseif (!in_array($action, ['inspect', 'seed'], true)) {
    throw new RuntimeException('Unknown fixture action.');
}
$after = \Breakdance\Data\get_tree($id);
if ($before !== $after) {
    throw new RuntimeException('The native no-op re-save changed the fixture tree.');
}
echo wp_json_encode([
    'post_id' => $id,
    'version' => __BREAKDANCE_VERSION,
    'action' => $action,
    'tree_preserved' => true,
    'tree_hash' => hash('sha256', wp_json_encode($after)),
    'editor_url' => home_url('/?breakdance=builder&id=' . $id),
    'frontend_url' => get_permalink($id),
    'scope' => 'Native isolated fixture, not a copy of a production database.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
