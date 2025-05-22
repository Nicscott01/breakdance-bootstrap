<?php


namespace BricBreakdance;

use function Breakdance\Elements\control;
use function Breakdance\Elements\controlSection;
use function Breakdance\Elements\repeaterControl;
use function Breakdance\Elements\c;
use function \BricBreakdance\Forms\getMappedFieldValuesFromFormData;

class BreakdanceTurnstile extends \Breakdance\Forms\Actions\Action {


    public function __construct() {

        $this->siteKey = \Breakdance\APIKeys\getKey('cf_turnstile_site_key');
        $this->secretKey = \Breakdance\APIKeys\getKey('cf_turnstile_secret_key');

        //Print the Turnstile widget in the form
        add_action( 'breakdance_form_before_footer', function ($form ) {

            printf( '<div class="breakdance-form-field"><div class="cf-turnstile" data-sitekey="%s"></div></div>', $this->siteKey );
            echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
        }, 10, 1 );

    }





    public static function name() {
        return 'CF Turnstile';
    }

    public static function slug() {
        return 'cf-turnstile';
    }

    public function run( $form, $settings, $extras ) {

        $cf_response = $_POST['cf-turnstile-response'] ?? null;


        if ( $cf_response) {
            // Retrieve the secret key from your API keys configuration
            $secret = $this->secretKey;
            $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';

            // Send the validation request to Cloudflare's Turnstile API
            $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'body' => [
                    'secret'   => $secret,
                    'response' => $cf_response,
                    'remoteip' => $remote_ip,
                ],
            ]);

            // Check for a connection error
            if ( is_wp_error( $response ) ) {
               return [ 'type' => 'error', 'message' => __('Turnstile verification failed. Please try again.', 'text-domain') ];
            }

            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);

            // Verify if the response is valid
            if ( empty($result['success']) || true !== $result['success'] ) {
                $error_codes = implode( ', ', $result['error-codes'] ?? [] );
                
                $err_message = '';
                if ( !empty( $error_codes) ) {
                    $err_message = sprintf( 'Error codes: %s', $error_codes );
                }

                return [ 'type' => 'error', 'message' => __('Turnstile verification failed. Please try again. ' . $err_message, 'text-domain') ];
            }
            // Verification successful
            return [ 'type' => 'success', 'message' => __('Turnstile verification successful.', 'text-domain') ];
        }
    }


}
