<?php
/**
 *  Breakdance form handler for WP USER
 * 
 *
 * 
 */

namespace BricBreakdance;

use function Breakdance\Elements\control;
use function Breakdance\Elements\controlSection;
use function Breakdance\Elements\repeaterControl;
use function Breakdance\Elements\c;


class WPUserFormHandler extends \Breakdance\Forms\Actions\Action {




    public function __construct()
    {
        $this->registerAjaxHandlers();
    }


    /**
     * @return string
     */
    public static function name() {
        return 'WP User';
    }


    public static function slug()
    {
        return 'wp_user';
    }

    


    /**
     * @return void
     */
    public static function registerAjaxHandlers()
    {


    

    }






    /**
     * @return array
     */
    public function controls()     
    {
        return [c(
        "info",
        "Info",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'info', 'content' => '<p>The only field necessary to create a user account is the email field.</p>']],
        false,
        false,
        [],
      ), c(
        "email",
        "Email",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id', 'condition' => [[['path' => 'type', 'operand' => 'is one of', 'value' => ['email']]]]]]],
        false,
        false,
        [],
      ), c(
        "first_name",
        "First Name",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id', 'condition' => [[['path' => 'type', 'operand' => 'is none of', 'value' => ['email', 'phone', 'phone_number']]]]]]],
        false,
        false,
        [],
      ), c(
        "last_name",
        "Last Name",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id', 'condition' => [[['path' => 'type', 'operand' => 'is none of', 'value' => ['email', 'phone', 'phone_number']]]]]]],
        false,
        false,
        [],
      ), c(
        "user_login",
        "User Login",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id', 'condition' => [[['path' => 'type', 'operand' => 'is none of', 'value' => ['email', 'phone', 'phone_number']]]]]]],
        false,
        false,
        [],
      ), c(
        "website",
        "Website",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id', 'condition' => [[['path' => 'type', 'operand' => 'is none of', 'value' => ['email', 'phone', 'phone_number']]]]]]],
        false,
        false,
        [],
      ), c(
        "password",
        "Password",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id']], 'placeholder' => 'leave blank to generate'],
        false,
        false,
        [],
      ), c(
        "role",
        "Role",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id']], 'placeholder' => 'administrator, editor, subscriber, etc.'],
        false,
        false,
        [],
      ), c(
        "user_meta",
        "User Meta",
        [c(
        "meta_key",
        "Meta Key",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "meta_value",
        "Meta Value",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id']]],
        false,
        false,
        [],
      )],
        ['type' => 'repeater', 'layout' => 'vertical', 'repeaterOptions' => ['titleTemplate' => '{meta_key}', 'defaultTitle' => 'User Meta', 'buttonName' => 'Add Meta']],
        false,
        false,
        [],
      ), c(
        "send_notification",
        "Send Notification",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'textOptions' => ['format' => 'plain'], 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id']], 'placeholder' => 'true/false'],
        false,
        false,
        [],
      )];
    }




    public function replace_placeholders($value, $extra_fields) {
        // If value contains a placeholder
        if (preg_match('/\{(.+?)\}/', $value, $matches)) {
            $field_key = $matches[1]; // Get the field key inside the curly braces
            return $extra_fields[$field_key] ?? ''; // Return the corresponding value or empty string
        }
        // Return the value as is (for hardcoded values)
        return $value;
    }




    /**
    * Do the thing
    *
    * @param array $form
    * @param array $settings
    * @param array $extra
    * @return array success or error message
    */
    public function run($form, $settings, $extra)
    {
        try {
           //
ob_start();
var_dump( $form );
var_dump( $settings );
var_dump( $extra );
$log = ob_get_clean();
//error_log( $log );


            // Capture the required fields for user account creation directly from the settings
            $user_data = [
                'user_email'  => $this->replace_placeholders( $settings['actions']['wp_user']['email'] ?? null, $extra['fields'] ),
                'first_name'  => $this->replace_placeholders( $settings['actions']['wp_user']['first_name'] ?? '', $extra['fields'] ),
                'last_name'   => $this->replace_placeholders( $settings['actions']['wp_user']['last_name'] ?? '', $extra['fields'] ),                
                'user_login'  => isset($settings['actions']['wp_user']['user_login'])
                        ? $this->replace_placeholders($settings['actions']['wp_user']['user_login'], $extra['fields'])
                        : $this->replace_placeholders($settings['actions']['wp_user']['email'], $extra['fields']),
                'role'        => $this->replace_placeholders( $settings['actions']['wp_user']['role'] ?? 'subscriber', $extra['fields'] ),
                'meta_input'  => [] // Prepare meta_input array for user meta data,
            ];

            // Ensure 'user_email' exists
            if (empty($user_data['user_email'])) {
                error_log('User email is missing.');
                return;
            }

            // Capture the user meta data from the repeater
            if (!empty($settings['actions']['wp_user']['user_meta'])) {
                foreach ($settings['actions']['wp_user']['user_meta'] as $meta_field) {
                    $meta_key = $meta_field['meta_key'];
                    $meta_value = $this->replace_placeholders($meta_field['meta_value'], $extra['fields']);

                    // Only add meta if the form field value exists
                    if ( !empty($meta_value ) ) {
                        $user_data['meta_input'][$meta_key] = $meta_value;
                    }
                }
            }


            // Insert the user into WordPress with meta data included
            $user_id = wp_insert_user($user_data);

            // Check if the user was created successfully
            if (!is_wp_error($user_id)) {
                
                if ( isset( $settings['actions']['wp_user']['send_notification'] ) && in_array( $settings['actions']['wp_user']['send_notification'], ['true','yes','1'] ) ) {
                    // Optionally send a notification email to the new user
                    wp_new_user_notification($user_id, null, 'both');
                }

            } else {
                // Log error details if user creation failed
                error_log('Error creating user: ' . $user_id->get_error_message());
            }

        } catch(\Exception $e) {
            error_log( $e->getMessage() );
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        return ['type' => 'success', 'message' => 'Please check your email!'];
    }



    

}