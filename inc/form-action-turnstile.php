<?php

namespace BricBreakdance;

class BreakdanceTurnstile extends \Breakdance\Forms\Actions\Action {

    public $siteKey;
    public $secretKey;

    private $verified_submission = null;

    public function __construct() {
        $this->siteKey = \Breakdance\APIKeys\getKey('cf_turnstile_site_key');
        $this->secretKey = \Breakdance\APIKeys\getKey('cf_turnstile_secret_key');

        add_action('breakdance_form_before_footer', [$this, 'output_turnstile_field']);
        // Run before Breakdance's priority-10 handlers, including older versions
        // that do not have the breakdance_form_validate_field filter.
        add_action('wp_ajax_nopriv_breakdance_form_custom', [$this, 'validate_submission'], 5);
        add_action('wp_ajax_breakdance_form_custom', [$this, 'validate_submission'], 5);
    }

    public static function name() {
        return 'CF Turnstile';
    }

    public static function slug() {
        return 'cf-turnstile';
    }

    public function output_turnstile_field($form) {
        if (!$this->has_keys() || !$this->is_enabled_for_form($form)) {
            return;
        }

        printf('<div class="breakdance-form-field"><div class="cf-turnstile" data-sitekey="%s"></div></div>', esc_attr($this->siteKey));
        echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    public function validate_submission(): void {
        $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        $form_id = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);

        if (!$post_id || !$form_id || !function_exists('Breakdance\\Forms\\getFormSettings')) {
            return;
        }

        $settings = \Breakdance\Forms\getFormSettings($post_id, $form_id);
        if (!$this->is_enabled_for_form($settings)) {
            return;
        }

        if (!$this->verify_token()) {
            wp_send_json_error($this->verification_error(), 400);
        }

        $this->verified_submission = [$post_id, $form_id];
    }

    public function run($form, $settings, $extras) {
        $submission = [(int) ($extras['postId'] ?? 0), (int) ($extras['formId'] ?? 0)];

        // The normal AJAX path already redeemed this single-use token.
        if ($this->verified_submission === $submission) {
            $this->verified_submission = null;
        } elseif (!$this->verify_token()) {
            return $this->verification_error();
        }

        return ['type' => 'success', 'message' => __('Turnstile verification successful.', 'breakdance-bootstrap')];
    }

    private function is_enabled_for_form($form): bool {
        if (is_object($form)) {
            $actions = $form->settings['actions'] ?? null;
            if ($actions === null && method_exists($form, 'get_setting')) {
                $actions = $form->get_setting('actions');
            }
        } else {
            $actions = $form['actions'] ?? null;
        }

        $actions = is_array($actions) ? ($actions['actions'] ?? $actions) : $actions;
        foreach ((array) $actions as $action) {
            if ($action === self::slug() || (is_array($action) && in_array(self::slug(), [
                $action['type'] ?? null,
                $action['action'] ?? null,
                $action['slug'] ?? null,
            ], true))) {
                return true;
            }
        }

        return false;
    }

    private function has_keys(): bool {
        return is_string($this->siteKey) && $this->siteKey !== ''
            && is_string($this->secretKey) && $this->secretKey !== '';
    }

    private function verify_token(): bool {
        $token = $_POST['cf-turnstile-response'] ?? '';
        $hostname = wp_parse_url(home_url('/'), PHP_URL_HOST);

        if (!is_string($token) || $token === '' || strlen($token) > 2048 || !$hostname || !$this->has_keys()) {
            return false;
        }

        $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'timeout'     => 5,
            'redirection' => 0,
            'body'        => [
                'secret'   => $this->secretKey,
                'response' => wp_unslash($token),
            ],
        ]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return ($result['success'] ?? false) === true
            && is_string($result['hostname'] ?? null)
            && strcasecmp($result['hostname'], $hostname) === 0;
    }

    private function verification_error(): array {
        return [
            'type'    => 'error',
            'message' => __('Please refresh the page and complete the security check before submitting.', 'breakdance-bootstrap'),
        ];
    }
}
