<?php

namespace BricBreakdance;
use function Breakdance\Elements\control;

class GatedDownloadFormHandler extends \Breakdance\Forms\Actions\Action {

    public $variable_items;
    public $download_id;
    public $user_email;


    /**
     * @return string
     */
    public static function name() {
        return 'Gated Download';
    }


    public static function slug()
    {
        return 'bric_gated_download';
    }



    public function __construct()
    {
        $this->define_search_replace();
        
    }


    public function define_search_replace() {

        $this->variable_items = [
            [
                'text' => 'Gated Download Responder Link', 
                'value' => 'bric_gated_download_responder_link',
            ],
            [   
                'text' => 'Gated Download Title', 
                'value' => 'bric_gated_download_title',
            ]
        ];


    }




    public function bric_gated_download_responder_link() {

       return GatedDownloadResponder::get_email_responder_link( $this->user_email, $this->download_id );

    }



    public function bric_gated_download_title() {

        return get_the_title( $this->download_id );
    }


    /**
    * Log the form submission to a file
    *
    * @param array $form
    * @param array $settings
    * @param array $extra
    * @return array success or error message
    */
    public function run($form, $settings, $extra)
    {
        try {
           

            //Send an email using the template with replacing the fname, lname, etc.
            //$to = $form['email'];

            $this->download_id = $extra['fields']['bric_gated_download_id'];
            $this->user_email =  $settings['actions']['bric_gated_download']['to'];



            //Do our own search_replace before sending to the breakdance emailer
            $search = [];
            $replace = [];

            foreach( $this->variable_items as $variable_item ) {
                $search[] = sprintf( '{%s}', $variable_item['value'] );
                $replace[] = call_user_func( [ $this, $variable_item['value'] ] );
            }
            
            
            $message = $settings['actions']['bric_gated_download']['message'];
            $subject = $settings['actions']['bric_gated_download']['subject'];

            $email = $settings['actions']['bric_gated_download'];
            $email['message'] = str_replace( $search, $replace, $message );
            $email['subject'] = str_replace( $search, $replace, $subject ); 

            $bdEmail = new \Breakdance\Forms\Actions\Email(); 
            $bdEmail->submit( $email, $form );
/*

            $headers = [];

            $to = $this->renderData( $form, $settings['actions']['bric_gated_download']['to'] );
            $subject = $this->renderData( $form, $settings['actions']['bric_gated_download']['subject'] );
            $message = $this->renderData( $form, $settings['actions']['bric_gated_download']['message'] );
            $from_name = isset( $settings['actions']['bric_gated_download']['from_name']) ? $this->renderData( $form, $settings['actions']['bric_gated_download']['from_name'] ) : '';
            $from_email = isset( $settings['actions']['bric_gated_download']['from_email'] ) ? $this->renderData( $form, $settings['actions']['bric_gated_download']['from_email'] ) : '';
            $reply_to = isset( $settings['actions']['bric_gated_download']['reply_to'] ) ? $this->renderData( $form, $settings['actions']['bric_gated_download']['reply_to'] ) : '';

            if ( !empty( $from_email ) ) {
                $headers[] =  "From: {$from_name} <{$from_email}>";
            }

            if ( !empty( $reply_to ) ) {
                $headers[] = "Reply-To: $reply_to";
            }

            wp_mail( $to, $subject, $message, $headers );
*/

        } catch(\Exception $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        return ['type' => 'success', 'message' => 'Please check your email!'];
    }











    public function controls()
    {
        return [
                    control('subject', 'Subject', [
                        'type' => 'text',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => true,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is none of',
                                    'value' => ['file', 'html']
                                ]
                            ]
                        ],
                        'variableItems' => [ $this->variable_items[1] ]
                    ]),
                    control('first_name', 'First Name', [
                        'type' => 'text',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => true,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is none of',
                                    'value' => ['file', 'html']
                                ]
                            ]
                        ]
                    ]),
                    control('last_name', 'Last Name', [
                        'type' => 'text',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => true,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is none of',
                                    'value' => ['file', 'html']
                                ]
                            ]
                        ]
                    ]),
                    control('to', 'To Email', [
                        'type' => 'text',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => true,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is one of',
                                    'value' => ['email']
                                ]
                            ]
                        ]
                    ]),
                    control('from', 'From Email', [
                        'type' => 'text',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => false,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is one of',
                                    'value' => ['email']
                                ]
                            ]
                        ]
                    ]),
                    control('from_name', 'From Name', [
                        'type' => 'text',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => false,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is none of',
                                    'value' => ['file', 'html']
                                ]
                            ]
                        ]
                    ]),
                    control('reply_to', 'Reply To', [
                        'type' => 'text',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => false,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is one of',
                                    'value' => ['email']
                                ]
                            ],
                        ]
                    ]),
                    control('message', 'Message', [
                        'type' => 'richtext',
                        'layout' => 'vertical',
                        'variableOptions' => [
                            'enabled' => true,
                            'populate' => [
                                'path' => 'content.form.fields',
                                'text' => 'label',
                                'value' => 'advanced.id',
                                'condition' => [
                                    'path' => 'type',
                                    'operand' => 'is none of',
                                    'value' => ['file', 'html']
                                ]
                            ]
                        ],
                        'variableItems' => $this->variable_items
                    ]),
                ];
            
    }



}




