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
     */
    public function controls()
    {
        return [
            control('lists', 'Lists', [
                'type' => 'multiselect',
                'layout' => 'vertical',
                'placeholder' => 'No lists selected',
                'multiselectOptions' => [
                    'populate' => [
                        'fetchDataAction' => 'bdbs_fetch_fluentcrm_lists',
                    ],
                ],
            ]),

            control('tags', 'Tags', [
                'type' => 'multiselect',
                'layout' => 'vertical',
                'placeholder' => 'No tags selected',
                'multiselectOptions' => [
                    'populate' => [
                        'fetchDataAction' => 'bdbs_fetch_fluentcrm_tags',
                    ],
                ],
            ]),


            repeaterControl('fields', 'Field Map', [
                control('fluent_crm_field', 'CRM Field Name', [
                    'type' => 'dropdown',
                    'layout' => 'vertical',
                    'placeholder' => 'No field selected',
                    'dropdownOptions' => [
                        'populate' => [
                            'fetchDataAction' => 'bdbs_fetch_fluentcrm_fields',
                        ],
                    ]
                ]),

                control('formField', 'Form Field Value', [
                    'type' => 'text',
                    'layout' => 'vertical',
                    'placeholder' => '',
                    'variableOptions' => [
                        'enabled' => true,
                        'populate' => [
                            'path' => 'content.form.fields',
                            'text' => 'label',
                            'value' => 'advanced.id',
                        ]
                    ],
                ]),
            ])
            
        ];
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


        ob_start();

var_dump( $form );

echo '


';

var_dump( $settings );

echo '


';

var_dump( $extra );

$log = ob_get_clean();

error_log( $log );



        try {
           
            //Add or update email in Fluent, and tag them with the form they filled out
            if ( function_exists( 'FluentCrmApi' ) ) {
             
                $contactApi = FluentCrmApi('contacts');


                $data_for_crm = [];

                foreach( $settings['actions']['fluent_crm']['fields'] as $field ) {

                    if ( empty( $field ) ) {
                        continue;
                    }

                    if( strpos( $field['fluent_crm_field'], 'custom_field_' ) === 0 ) {

                        //Custom values park in their own subarray 
                        /*'custom_values' => [
                        'custom_field_slug_1' => 'custom_field_value_1',
                        'custom_field_slug_2' => 'custom_field_value_2',
                        ]*/

                        if ( strpos( $field['formField'], '{') === 0 ) {

                            $data_for_crm['custom_values'][ str_replace( 'custom_field_', '', $field['fluent_crm_field'] )] = $extra['fields'][ str_replace( [ '{', '}'], '',  $field['formField'] ) ];

                        } else {

                            $data_for_crm['custom_values'][ str_replace( 'custom_field_', '', $field['fluent_crm_field'] )] =  $field['formField'];

                        }


                    } else {

                        //Get the value from the extra
                        if ( strpos( $field['formField'], '{') === 0 ) {
                            
                            $data_for_crm[$field['fluent_crm_field']] = $extra['fields'][ str_replace( [ '{', '}'], '',  $field['formField'] ) ];

                        } else {

                            $data_for_crm[$field['fluent_crm_field']] = $field['formField'];

                        }


                    }

                }


                //Set tags
                $data_for_crm['tags'] = $settings['actions']['fluent_crm']['tags'];
                
                //Set lists
                $data_for_crm['lists'] = $settings['actions']['fluent_crm']['lists'];



                ob_start();
                var_dump( $data_for_crm );
                $log = ob_get_clean();
                error_log( $log );

                $contact = $contactApi->createOrUpdate( $data_for_crm );
                
                
                /*
                * Update/Insert a contact
                * You can create or update a contact in a single call
                */

                $data = [
                    'first_name' => $extra['fields']['fname'],
                    'last_name' => $extra['fields']['lname'],
                    'email' => $extra['fields']['email'], // requied
                    'status' => 'pending',
                    //'tags' => [1,2,3, 'Dynamic Tag'], // tag ids/slugs/title as an array
                    //'lists' => [4, 'Dynamic List'] // list ids/slugs/title as an array,
                    //'detach_tags' => [6, 'another_tag'] // tag ids/slugs/title as an array,
                    //'detach_lists' => [10, 'list_slug'] // list ids/slugs/title as an array,
                    /*'custom_values' => [
                        'custom_field_slug_1' => 'custom_field_value_1',
                        'custom_field_slug_2' => 'custom_field_value_2',
                    ]*/
                ];

                //$contact = $contactApi->createOrUpdate($data);

                // send a double opt-in email if the status is pending
              /*  if($contact && $contact->status == 'pending') {
                    $contact->sendDoubleOptinEmail();
                }
                */

            } 

        } catch(\Exception $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        return ['type' => 'success', 'message' => 'Please check your email!'];
    }



    

}