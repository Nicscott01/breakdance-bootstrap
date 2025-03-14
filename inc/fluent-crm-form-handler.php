<?php
/**
 *  Breakdance form handler for Fluent CRM
 * 
 * 
 *  Todo: use Mailchimp action as a model
 *  - Check if FluentCRM is loaded
 *  - Get lists and add as option
 *  - Get tags and add as option
 *  - Get availabled FluentCRM form fields
 *  - Map to our form fields
 * 
 */

namespace BricBreakdance;

use function Breakdance\Elements\control;
use function Breakdance\Elements\controlSection;
use function Breakdance\Elements\repeaterControl;
use function Breakdance\Elements\c;
use function \BricBreakdance\Forms\getMappedFieldValuesFromFormData;

class FluentCrmFormHandler extends \Breakdance\Forms\Actions\Action {

    public $variable_items;
    public $download_id;
    public $user_email;


    public function __construct()
    {
        $this->registerAjaxHandlers();
    }


    /**
     * @return string
     */
    public static function name() {
        return 'Fluent CRM';
    }


    public static function slug()
    {
        return 'fluent_crm';
    }

    


    /**
     * @return void
     */
    public static function registerAjaxHandlers()
    {

        \Breakdance\AJAX\register_handler(
            'bdbs_fetch_fluentcrm_tags',
            function() {

                $tagApi = FluentCrmApi('tags');

                $allTags = $tagApi->all();


                $tags_array = [];

                if ( $allTags ) {

                    foreach ( $allTags as $tag ) {

                        $tags_array[] = [
                            'value' => (string) $tag->id,
                            'text' => $tag->title
                        ];
                        
                        
                    }
                }




                return $tags_array;
            },
            'edit'
        );



        \Breakdance\AJAX\register_handler(
            'bdbs_fetch_fluentcrm_lists',
            function() {

                $tagApi = FluentCrmApi('lists');

                $allTags = $tagApi->all();


                $tags_array = [];

                if ( $allTags ) {

                    foreach ( $allTags as $tag ) {

                        $tags_array[] = [
                            'value' => (string) $tag->id,
                            'text' => $tag->title
                        ];
                        
                        
                    }
                }




                return $tags_array;
            },
            'edit'
        );



        \Breakdance\AJAX\register_handler(
            'bdbs_fetch_fluentcrm_fields',
            /**
             * @return array
             */
            function () {
                /** @var FormRequestContext $requestdata */
              
               
                $fields = [
                    [
                        'value' => 'user_id',
                        'text' => 'User ID'
                    ], [
                        'value' => 'company_id',
                        'text' => 'Company ID'
                    ], [
                        'value' => 'prefix',
                        'text' => 'Prefix'
                    ], [
                        'value' => 'first_name',
                        'text' => 'First Name'
                    ], [
                        'value' => 'last_name',
                        'text' => 'Last Name'
                    ], [
                        'value' => 'email',
                        'text' => 'Email'
                    ], [
                        'value' => 'timezone',
                        'text' => 'Timezone'
                    ], [
                        'value' => 'address_line_1',
                        'text' => 'Address Line 1'
                    ], [
                        'value' => 'address_line_2',
                        'text' => 'Address Line 2'
                    ], [
                        'value' => 'postal_code',
                        'text' => 'Postal Code'
                    ], [
                        'value' => 'city',
                        'text' => 'City'
                    ], [
                        'value' => 'state',
                        'text' => 'State'
                    ], [
                        'value' => 'country',
                        'text' => 'Country'
                    ], [
                        'value' => 'ip',
                        'text' => 'IP Address'
                    ], [
                        'value' => 'latitude',
                        'text' => 'Latitude'
                    ], [
                        'value' => 'longitude',
                        'text' => 'Longitude'
                    ], [
                        'value' => 'phone',
                        'text' => 'Phone'
                    ], [
                        'value' => 'status',
                        'text' => 'Status (pending/subscribed/bounced/unsubscribed)'
                    ], [
                        'value' => 'contact_tye',
                        'text' => 'lead/customer'
                    ], [
                        'value' => 'source',
                        'text' => 'Source'
                    ], [
                        'value' => 'avatar',
                        'text' => 'Custom Contact Photo URL'
                    ], [
                        'value' => 'date_of_birth',
                        'text' => 'Date of Birth in Y-m-d format'
                    ], [
                        'value' => 'last_activity',
                        'text' => 'Last Activity'
                    ], [
                        'value' => 'updated_at',
                        'text' => 'Updated At'
                    ]
                ];


                //Get custom fields
                $custom_contact_fields = fluentcrm_get_custom_contact_fields();  


                if ( !empty( $custom_contact_fields ) ) {
                    
                    foreach( $custom_contact_fields as $custom_field ) {
                        $fields[] = [
                            'value' => 'custom_field_' . $custom_field['slug'],
                            'text' => $custom_field['label'],
                        ];
                    }


                }



                return $fields;

            },
            'edit'
        );
    

    }






    /**
     * @return array
     * 
     * Note: Important to use formField as the slug for mapped repeaters 
     * so we can use the breakdance function \Breakdance\Forms\getMappedFieldValuesFromFormData()
     */
    public function controls()
    {
        return [c(
        "lists",
        "Lists",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [], 'dropdownOptions' => ['populate' => ['path' => '', 'text' => '', 'value' => '', 'fetchDataAction' => 'bdbs_fetch_fluentcrm_lists', 'fetchContextPath' => 'content.controls.lists', 'refetchPaths' => []]]],
        false,
        false,
        [],
      ), c(
        "tags",
        "Tags",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [], 'dropdownOptions' => ['populate' => ['path' => '', 'text' => '', 'value' => '', 'fetchDataAction' => 'bdbs_fetch_fluentcrm_tags', 'fetchContextPath' => 'content.controls.lists', 'refetchPaths' => []]]],
        false,
        false,
        [],
      ), c(
        "field_map",
        "Field Map",
        [c(
        "crm_field",
        "CRM Field",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'dropdownOptions' => ['populate' => ['path' => 'content.controls.field_map', 'text' => '', 'value' => '', 'fetchDataAction' => 'bdbs_fetch_fluentcrm_fields', 'fetchContextPath' => 'content.controls.field_map.crm_field', 'refetchPaths' => []]]],
        false,
        false,
        [],
      ), c(
        "formfield",
        "Form Field",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'variableOptions' => ['enabled' => true, 'populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id']], 'dropdownOptions' => ['populate' => ['path' => 'content.form.fields', 'text' => 'label', 'value' => 'advanced.id', 'fetchDataAction' => '', 'fetchContextPath' => '', 'refetchPaths' => []]]],
        false,
        false,
        [],
      )],
        ['type' => 'repeater', 'layout' => 'vertical', 'repeaterOptions' => ['titleTemplate' => '{crm_field}', 'defaultTitle' => '', 'buttonName' => '']],
        false,
        false,
        [],
      ), c(
        "about_double_opt_in",
        "About Double Opt In",
        [],
        ['type' => 'alert_box', 'layout' => 'vertical', 'alertBoxOptions' => ['style' => 'warning', 'content' => '<p>Only disable double opt-in if you know what you\'re doing. This could lead to a very dirty list and blacklisting by email providers.</p>']],
        false,
        false,
        [],
      ), c(
        "disable_double_opt_in",
        "Disable Double Opt-In",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      )];
    }





    /**
     *  Helper: put fields with key prefix custom_field_ 
     *  into an array custom_values
     * 
     */

    public function prepare_custom_fields( $merged_data ) {

        if ( empty( $merged_data ) ) {
            return $merged_data;
        }

        $custom_values = [];

        foreach( $merged_data as $key => $val ) {

            if( strpos( $key, 'custom_field_' ) === 0 ) {

                //Custom values park in their own subarray 
                /*'custom_values' => [
                'custom_field_slug_1' => 'custom_field_value_1',
                'custom_field_slug_2' => 'custom_field_value_2',
                ]*/

                $slug = str_replace( 'custom_field_', '', $key );
                
                $custom_values[ $slug ] = $val;


            }

        }

        if ( !empty( $custom_values ) ) {
            $merged_data['custom_values'] = $custom_values;
        }

        return $merged_data;

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
           
            //Add or update email in Fluent, and tag them with the form they filled out
            /*$data = [
                'first_name' => $extra['fields']['fname'],
                'last_name' => $extra['fields']['lname'],
                'email' => $extra['fields']['email'], // requied
                'status' => 'pending',
                'tags' => [1,2,3, 'Dynamic Tag'], // tag ids/slugs/title as an array
                'lists' => [4, 'Dynamic List'] // list ids/slugs/title as an array,
                'detach_tags' => [6, 'another_tag'] // tag ids/slugs/title as an array,
                'detach_lists' => [10, 'list_slug'] // list ids/slugs/title as an array,
                'custom_values' => [
                    'custom_field_slug_1' => 'custom_field_value_1',
                    'custom_field_slug_2' => 'custom_field_value_2',
                ]
            ];*/


            if ( function_exists( 'FluentCrmApi' ) ) {

                $fieldsRepeater = $settings['actions']['fluent_crm']['field_map'];

                $mapped_values = \BricBreakdance\Forms\getMappedFieldValuesFromFormData($fieldsRepeater, $form, 'crm_field', 'formfield' );
                //error_log( 'Mapped values: ' . print_r( $mapped_values, 1 ) );



                $contactApi = FluentCrmApi('contacts');
            
                //Bail if no email:
                if ( !isset( $mapped_values['email']) || empty( $mapped_values['email'] ) ) {
                    return ['type' => 'error', 'message' => 'The CRM action has been incorrectly configured. No Email present.'];
                }

                //Re-jigger custom fields
                $mapped_values = $this->prepare_custom_fields( $mapped_values );


                //Set lists
                $mapped_values['lists'] = [ $settings['actions']['fluent_crm']['lists'] ] ?? [];

                //Set tags                
                $mapped_values['tags'] = [ $settings['actions']['fluent_crm']['tags'] ] ?? [];
                

                //error_log( 'Mapped values: ' . print_r( $mapped_values, 1 ) );

                $disable_double_opt_in = $settings['actions']['fluent_crm']['disable_double_opt_in'] ?? false;

                
                
                //Set status to unsubscribed if not set in form
                if ( ( !isset( $mapped_values['status'] ) || $mapped_values['status'] == '' ) && !$disable_double_opt_in  ) {

                    //See if the contact exists
                    $existing_contact = $contactApi->getContact( $mapped_values['email'] );

                    if( empty( $existing_contact ) ) {
                        $mapped_values['status'] = 'transactional';
                    }
                } elseif ( $disable_double_opt_in ) {

                    $mapped_values['status'] = 'subscribed';

                }

                /*
                * Update/Insert a contact
                * You can create or update a contact in a single call
                */

                $contact = $contactApi->createOrUpdate( $mapped_values );
                
                /**
                 * Double Opt In
                 */

                $disable_double_opt_in = $settings['actions']['fluent_crm']['disable_double_opt_in'] ?? false;

                // send a double opt-in email if the status is pending
                if($contact && $contact->status == 'pending' && !$disable_double_opt_in ) {
                    $contact->sendDoubleOptinEmail();
                }
                

            } 

        } catch(\Exception $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        return ['type' => 'success', 'message' => 'Please check your email!'];
    }



    

}