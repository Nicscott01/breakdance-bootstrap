<?php

namespace BricBreakdance;

class WebsitePolicies {

    public static $instance;



    public function __construct()
    {
        include_once( __DIR__ . '/../acf-fields/website-policies.php' );
        

        add_action( 'wp_head', [ $this, 'print_cookie_policy_script' ], 1 );



    }





    public function print_cookie_policy_script() {

        if ( get_option( 'options_enable_cookie_consent' ) ) {

            
            $excluded = function_exists('get_field') ? get_field( 'cookie_consent_exclude_urls', 'option' ) : false;
            if (empty($excluded)) {
                $excluded = get_option( 'options_cookie_consent_exclude_urls' );
            }

            $excludes = [];
            if (is_array($excluded)) {
                $excludes = $excluded;
            } elseif (is_string($excluded)) {
                $excludes = preg_split('/\r\n|\r|\n|,/', $excluded);
            }
            $excludes = array_filter(array_map('trim', $excludes));
            if (!empty($excludes)) {
                $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                $current_url = ($is_https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $current_path = parse_url($current_url, PHP_URL_PATH) ?: '/';

                foreach ($excludes as $ex) {
                    if ($ex === '') {
                        continue;
                    }

                    // Wildcard support
                    if (strpos($ex, '*') !== false) {
                        $pattern = '#^' . str_replace('\*', '.*', preg_quote($ex, '#')) . '$#i';
                        if (preg_match($pattern, $current_url) || preg_match($pattern, $current_path)) {
                            return;
                        }
                        continue;
                    }

                    $parsed = parse_url($ex);

                    // Full URL provided
                    if (!empty($parsed['scheme'])) {
                        if (rtrim($current_url, '/') === rtrim($ex, '/')) {
                            return;
                        }
                        if (strpos($current_url, rtrim($ex, '/')) === 0) {
                            return;
                        }
                        continue;
                    }

                    // Path-only (starts with '/')
                    if (strpos($ex, '/') === 0) {
                        if (rtrim($current_path, '/') === rtrim($ex, '/')) {
                            return;
                        }
                        if (strpos($current_path, $ex) === 0) {
                            return;
                        }
                        continue;
                    }

                    // Host or host+path without scheme
                    if (strpos($current_url, $ex) !== false) {
                        return;
                    }
                }
            }

            $raw = get_option( 'options_cookie_consent_scripts' );
            // Whitelist head-friendly tags/attributes using WP sanitizer
            $allowed = [
                'script' => [
                    'src' => true, 'type' => true, 'async' => true, 'defer' => true,
                    'crossorigin' => true, 'integrity' => true, 'nomodule' => true, 'nonce' => true,
                ],
                'meta'   => [ 'name' => true, 'content' => true, 'charset' => true, 'http-equiv' => true ],
                'link'   => [ 'rel' => true, 'href' => true, 'type' => true, 'media' => true, 'sizes' => true ],
                'style'  => [ 'type' => true, 'media' => true ],
                'title'  => [],
            ];

            // Remove inline <script> blocks (allow only <script src="...">)
            $raw_no_inline = preg_replace_callback(
                '#<script\b([^>]*)>(.*?)</script>#is',
                function ( $m ) {
                    // keep script only if it has a src attribute, and drop any inline content
                    if ( preg_match( '/\bsrc\s*=/i', $m[1] ) ) {
                        return '<script' . $m[1] . '></script>';
                    }
                    return '';
                },
                $raw
            );

            // Use wp_kses to strip disallowed tags/attributes and drop javascript: URIs
            $cookie_consent_script = wp_kses( $raw_no_inline, $allowed );

            echo $cookie_consent_script;

        }
    }





    public static function get_instance() {

        if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

}


function WebsitePolicies() {

    if ( apply_filters( 'bric_breakdance_website_policies', true ) ) {

        return WebsitePolicies::get_instance();

    }

}

WebsitePolicies();