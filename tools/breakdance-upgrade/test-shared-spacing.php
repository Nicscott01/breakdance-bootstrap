<?php
/** Check the real Bootstrap append callback without booting WordPress. */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
if (count($argv) !== 3) {
    fwrite(STDERR, "Usage: php test-shared-spacing.php /path/to/bootstrap 2.8.3|3.0.2\n");
    exit(1);
}
$root = realpath($argv[1]);
if (!$root || !is_file($root . '/custom-elements-loader.php')) {
    throw new RuntimeException('Select the shared plugin source.');
}
define('__BREAKDANCE_VERSION', $argv[2]);
$registeredFilters = [];
function add_action(...$args): void {}
function add_filter($name, $callback, ...$args): void
{
    $GLOBALS['registeredFilters'][$name][] = $callback;
}
require $root . '/custom-elements-loader.php';
$callbacks = $registeredFilters['breakdance_global_settings_css_twig_template_append'] ?? [];
if (count($callbacks) !== 1) {
    throw new RuntimeException('Review a changed global-template hook contract.');
}
$existing = '.breakdance .bde-heading { margin-bottom:99px; }' . PHP_EOL;
$result = $callbacks[0]($existing);
if (!str_starts_with($result, $existing) || str_contains($result, '%%BOOTSTRAP_DEFAULT_SCOPE%%')) {
    throw new RuntimeException('Do not rewrite another extension or emit an unresolved scope marker.');
}
$owned = substr($result, strlen($existing));
$legacy = file_get_contents(__DIR__ . '/fixtures/legacy-defaults-1.4.2.css.twig');
$isV3 = version_compare(__BREAKDANCE_VERSION, '3.0', '>=') && version_compare(__BREAKDANCE_VERSION, '4.0', '<');
if (!$isV3 && $owned !== $legacy) {
    throw new RuntimeException('V2 spacing output must remain byte-for-byte identical to the 1.4.2 baseline.');
}
if ($isV3) {
    foreach (['p,', '.bde-heading,', '.bde-rich-text h2,', '.bde-rich-text h3,', '.bde-rich-text h4,', '.bde-rich-text h5,', '.bde-rich-text h6 {', '.bde-button {'] as $selector) {
        if (!str_contains($owned, ':where(.breakdance) ' . $selector)) {
            throw new RuntimeException('V3 shared defaults must yield to native element controls.');
        }
    }
}
if (!str_contains($owned, '.breakdance .has-bd-palette-')) {
    throw new RuntimeException('The palette selector scope must stay unchanged.');
}
echo json_encode([
    'breakdance_version' => __BREAKDANCE_VERSION,
    'spacing_scope' => $isV3 ? ':where(.breakdance)' : '.breakdance',
    'legacy_template_unchanged' => !$isV3,
    'other_extension_preserved' => true,
    'palette_scope_preserved' => true,
], JSON_PRETTY_PRINT) . PHP_EOL;
