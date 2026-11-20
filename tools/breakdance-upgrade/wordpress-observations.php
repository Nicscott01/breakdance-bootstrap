<?php
/** Read runtime facts for audit.py; this file performs no writes. */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

if (!defined('WP_CLI') || !WP_CLI || !current_user_can('manage_options')) {
    throw new RuntimeException('Run through an authorized WP-CLI session with an administrator.');
}
if (!defined('__BREAKDANCE_VERSION') || !function_exists('Breakdance\\Data\\get_global_option')) {
    throw new RuntimeException('Breakdance must be active for this request.');
}

$readMarker = static function ($name, $valid) {
    $prefix = \Breakdance\BreakdanceOxygen\Strings\__bdox('meta_prefix');
    if (get_option($prefix . $name, null) === null) {
        return null;
    }
    $value = \Breakdance\Data\get_global_option($name);
    return $valid($value) ? $value : '<invalid>';
};
$last = $readMarker('last_migrated_version', static fn($v) => is_string($v) && preg_match('/^\d+\.\d+/', $v));
$activation = $readMarker('plugin_has_already_been_activated', static fn($v) => $v === 'yes');
$state = $last === __BREAKDANCE_VERSION && $activation === 'yes' ? 'current' : 'review-required';
if ($last === null && $activation === null) {
    $state = 'both-markers-missing-native-updater-returns-early';
}
$fallbacks = [];
foreach (\Breakdance\Themeless\Fallbacks\get_all_fallback_defaults() as $post) {
    $fallbacks[] = [
        'id' => $post->ID,
        'content_hash' => hash('sha256', serialize([
            $post->post_title,
            \Breakdance\Data\get_meta($post->ID, '_breakdance_data'),
            \Breakdance\Data\get_meta($post->ID, '_breakdance_template_settings'),
        ])),
    ];
}
$sets = \Breakdance\Icons\get_icon_sets();
global $wp_version;
$result = [
    'wordpress_version' => $wp_version,
    'breakdance_version' => __BREAKDANCE_VERSION,
    'bootstrap_version' => defined('BREAKDANCE_BS_VERSION') ? BREAKDANCE_BS_VERSION : null,
    'last_migrated_version' => $last,
    'activation_marker' => $activation,
    'initialization_state' => $state,
    'reactivation_requires_state_preservation' => $activation !== 'yes',
    'native_cache_flag' => \Breakdance\Data\get_global_option('last_clear_css_cache_flag_value'),
    'native_cache_flag_expected' => defined('__BREAKDANCE_CLEAR_CSS_CACHE_FLAG__') ? __BREAKDANCE_CLEAR_CSS_CACHE_FLAG__ : null,
    'design_switches' => [],
    'fallbacks' => $fallbacks,
    'icon_set_count' => count($sets),
    'custom_icon_set_count' => count(array_filter($sets, static fn($set) => $set['custom'])),
    'icon_sets_hash' => hash('sha256', wp_json_encode($sets)),
];
foreach (['is_theme_disabled', 'enable_skip_link', 'modern_normalize_enabled'] as $name) {
    $result['design_switches'][$name] = \Breakdance\Data\get_global_option($name);
}
if (in_array('fonts', $args ?? [], true)) {
    $result['font_slugs'] = array_keys(\Breakdance\Fonts\FontsController::getInstance()->getFonts());
    sort($result['font_slugs']);
}
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
