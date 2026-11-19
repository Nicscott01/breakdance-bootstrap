<?php
/** Run the real package's pure selector functions, without loading WordPress. */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
if (empty($argv[1])) {
    fwrite(STDERR, "Usage: php native-selector-contract.php /path/to/trusted/breakdance\n");
    exit(1);
}

$source = realpath($argv[1]);
if (!$source || !is_file($source . '/plugin.php') || !is_file($source . '/plugin/render/renderer.php')) {
    fwrite(STDERR, "The supplied directory is not an extracted Breakdance package.\n");
    exit(1);
}

preg_match("/const __BREAKDANCE_VERSION\\s*=\\s*['\"]([^'\"]+)/", file_get_contents($source . '/plugin.php'), $matches);
$version = $matches[1] ?? '';
if (!preg_match('/^[23]\\./', $version)) {
    fwrite(STDERR, "This check only understands the reviewed v2/v3 selector contract.\n");
    exit(1);
}

define('BREAKDANCE_MODE', 'breakdance');
require $source . '/plugin/render/renderer.php';

$selector = \Breakdance\Render\getElementSelector('EssentialElements\\Icon', 'bde-icon-123-101');
$expected = str_starts_with($version, '2.') ? '.breakdance .bde-icon-123-101' : '.bde-icon-123-101';
if ($selector !== $expected) {
    throw new RuntimeException('The native selector contract changed; review the adapter before using it.');
}

$bodyRule = \Breakdance\Render\renderTags('.home%%SELECTOR%% { color: white; }', ['selector' => $selector]);
$svgRule = \Breakdance\Render\renderTags('%%SELECTOR%% svg { height: auto; }', ['selector' => $selector]);
echo json_encode([
    'package_version' => $version,
    'native_element_selector' => $selector,
    'native_body_rule' => $bodyRule,
    'native_svg_rule' => $svgRule,
    'scope' => 'Pure native selector functions only; not a WordPress/editor integration test.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
