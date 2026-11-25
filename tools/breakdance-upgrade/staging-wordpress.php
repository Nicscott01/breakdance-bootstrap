<?php
/** Guard and collect native facts for the local staging workflow. */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$target = json_decode(getenv('CREARE_BD_STAGE_TARGET') ?: '', true);
if (!defined('WP_CLI') || !WP_CLI || !is_array($target)
    || !defined('CREARE_BD_UPGRADE_STAGING') || CREARE_BD_UPGRADE_STAGING !== true
    || !in_array(wp_get_environment_type(), ['local', 'staging'], true)
    || is_multisite() || DB_NAME !== $target['db'] || DB_HOST !== 'localhost:' . $target['socket']
    || realpath(ABSPATH) !== $target['wordpress']
    || home_url() !== $target['origin'] || !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL
    || !current_user_can('manage_options')) {
    WP_CLI::error('The WordPress runtime failed the local staging guard.');
}

$site = wp_parse_url(site_url());
$origin = wp_parse_url($target['origin']);
$plugins = realpath(WP_PLUGIN_DIR);
if (!$plugins || $plugins !== WP_PLUGIN_DIR || !str_starts_with($plugins, $target['root'] . DIRECTORY_SEPARATOR)
    || !$site || array_intersect_key($site, $origin) !== $origin
    || isset($site['user']) || isset($site['pass']) || isset($site['query']) || isset($site['fragment'])) {
    WP_CLI::error('The plugin directory or WordPress site URL is outside the expected local stage.');
}

global $wpdb;
$operation = $args[0] ?? '';
if ($operation === 'target') {
    $socket = $wpdb->get_var('SELECT @@socket');
    if (!is_string($socket) || realpath($socket) !== $target['socket']) {
        WP_CLI::error('WordPress is not using the verified staging database socket.');
    }
    $tables = $wpdb->get_col('SHOW TABLES');
    if (!$tables || array_filter($tables, static fn($name) =>
        !preg_match('/^[A-Za-z0-9_]+$/', $name) || !str_starts_with($name, $wpdb->prefix))) {
        WP_CLI::error('Use a dedicated single-site database containing only the staging table prefix.');
    }
    sort($tables);
    $activePlugins = [];
    foreach (get_option('active_plugins', []) as $entry) {
        $file = realpath($plugins . DIRECTORY_SEPARATOR . $entry);
        if ($file && !str_starts_with($file, $plugins . DIRECTORY_SEPARATOR)) {
            WP_CLI::error('An active plugin file is outside the staging plugin directory.');
        }
        $activePlugins[$entry] = $file && is_readable($file) ? get_file_data($file, ['version' => 'Version'])['version'] : null;
    }
    $identity = $target + ['plugins' => $plugins, 'prefix' => $wpdb->prefix, 'site_url' => site_url()];
    echo wp_json_encode(['identity' => $identity, 'tables' => $tables, 'active_plugins' => $activePlugins]);
    return;
}

if ($operation === 'themes') {
    $theme = wp_get_theme();
    $themes = [];
    foreach (array_filter([$theme, $theme->parent()]) as $currentTheme) {
        $directory = realpath($currentTheme->get_stylesheet_directory());
        if ($directory && !str_starts_with($directory, $target['root'] . DIRECTORY_SEPARATOR)) {
            WP_CLI::error('The active theme or its parent is outside the staging directory.');
        }
        $themes[$currentTheme->get_stylesheet()] = $currentTheme->exists() && $directory ? $currentTheme->get('Version') : null;
    }
    echo wp_json_encode((object) $themes);
    return;
}

if ($operation === 'clear-css') {
    $cacheFlag = 'last_clear_css_cache_flag_value';
    if (function_exists('\\Breakdance\\Setup\\cleanCacheWhenCacheCountChanges')
        && defined('__BREAKDANCE_CLEAR_CSS_CACHE_FLAG__')
        && (int) \Breakdance\Data\get_global_option($cacheFlag) !== __BREAKDANCE_CLEAR_CSS_CACHE_FLAG__) {
        // Complete the deferred admin cache migration before warming public routes.
        require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
        $previousScreen = $GLOBALS['current_screen'] ?? null;
        $GLOBALS['current_screen'] = WP_Screen::get('dashboard');
        try {
            \Breakdance\Setup\cleanCacheWhenCacheCountChanges();
        } finally {
            $GLOBALS['current_screen'] = $previousScreen;
        }
        if ((int) \Breakdance\Data\get_global_option($cacheFlag) !== __BREAKDANCE_CLEAR_CSS_CACHE_FLAG__) {
            WP_CLI::error('The native Breakdance CSS cache migration did not complete.');
        }
    } else {
        \Breakdance\Render\clearAllCssCachesAndDeleteCachedFiles();
        \Breakdance\Render\generateCacheForGlobalSettings();
    }
    echo '{}';
    return;
}

if ($operation !== 'content') {
    WP_CLI::error('Unknown staging inspection operation.');
}

$postMetaPrefix = \Breakdance\BreakdanceOxygen\Strings\__bdox('_meta_prefix');
$optionPrefix = \Breakdance\BreakdanceOxygen\Strings\__bdox('meta_prefix');
$metaNames = [$postMetaPrefix . 'data', $postMetaPrefix . 'template_settings', $postMetaPrefix . 'futurelayer_meta'];
$options = [
    'global_settings_json_string', 'presets_json_string', 'breakdance_classes_json_string',
    'oxy_selectors', 'variables_json_string',
];
$meta = $wpdb->get_results($wpdb->prepare(
    "SELECT post_id, meta_key, SHA2(meta_value, 256) AS digest FROM $wpdb->postmeta
     WHERE meta_key IN (%s, %s, %s) ORDER BY post_id, meta_key, meta_id",
    ...$metaNames
), ARRAY_A);
if ($wpdb->last_error) {
    WP_CLI::error('Could not read saved Breakdance documents.');
}
$globals = $wpdb->get_results($wpdb->prepare(
    "SELECT option_name, SHA2(option_value, 256) AS digest FROM $wpdb->options
     WHERE option_name IN (%s, %s, %s, %s, %s) ORDER BY option_name",
    ...array_map(static fn($name) => $optionPrefix . $name, $options)
), ARRAY_A);
if ($wpdb->last_error) {
    WP_CLI::error('Could not read saved Breakdance settings.');
}
$iconTable = \Breakdance\Icons\get_icons_table_full_name();
$icons = $wpdb->get_results($wpdb->prepare(
    'SELECT id, name, icon_set_slug, slug, SHA2(svg_code, 256) AS digest FROM %i ORDER BY id',
    $iconTable
), ARRAY_A);
if ($wpdb->last_error) {
    WP_CLI::error('Could not read saved Breakdance icons.');
}
$attachments = $wpdb->get_results(
    "SELECT f.meta_value AS path, m.meta_value AS metadata
     FROM $wpdb->posts p JOIN $wpdb->postmeta f ON f.post_id=p.ID AND f.meta_key='_wp_attached_file'
     LEFT JOIN $wpdb->postmeta m ON m.post_id=p.ID AND m.meta_key='_wp_attachment_metadata'
     WHERE p.post_type='attachment' AND p.post_status IN ('inherit', 'publish')",
    ARRAY_A
);
if ($wpdb->last_error || !is_array($attachments)) {
    WP_CLI::error('Could not read the staging content inventory.');
}
$uploads = wp_upload_dir(null, false);
$paths = [];
foreach ($attachments as $attachment) {
    $path = $attachment['path'];
    $paths[$path] = true;
    $metadata = $attachment['metadata'];
    $info = is_serialized($metadata) ? unserialize(trim($metadata), ['allowed_classes' => false]) : null;
    if (!is_array($info)) {
        continue;
    }
    $files = array_column($info['sizes'] ?? [], 'file');
    if (!empty($info['original_image'])) {
        $files[] = $info['original_image'];
    }
    foreach ($files as $file) {
        $paths[dirname($path) . '/' . basename($file)] = true;
    }
}
$basedir = realpath($uploads['basedir']);
if ($paths && (!$basedir || !str_starts_with($basedir, $target['root'] . DIRECTORY_SEPARATOR))) {
    WP_CLI::error('Registered uploads are outside the local staging directory.');
}
$missing = [];
foreach (array_keys($paths) as $path) {
    $file = realpath(str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : $basedir . DIRECTORY_SEPARATOR . $path);
    if (!$file || !str_starts_with($file, $basedir . DIRECTORY_SEPARATOR) || !is_file($file)) {
        $missing[] = $path;
    }
}
echo wp_json_encode([
    'document_hash' => hash('sha256', wp_json_encode([$meta, $globals])),
    'layout_setting_records' => count($meta) + count($globals),
    'icon_payload_hash' => hash('sha256', wp_json_encode($icons)),
    'media' => ['paths' => count($paths), 'missing_count' => count($missing), 'examples' => array_slice($missing, 0, 20)],
]);
