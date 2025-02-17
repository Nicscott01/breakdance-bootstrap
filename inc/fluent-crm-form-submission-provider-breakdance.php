<?php

namespace BricBreakdance;



class FluentCrmFormSubmissionProviderBreakdance {

    /**
     *  Instance
     * 
     *  @var object null
     */
    public static $instance = null;


    public static $provider = 'breakdance_forms';
    public static $providerTitle = 'Breakdance Forms';


    /**
     *  Construct
     * 
     * 
     */
    public function __construct() {


        //Maybe setup the subscriber profile section
        add_filter( 'fluentcrm_profile_sections', [ $this, 'setup_form_submissions_section' ], 10, 1 ); 

        //Register provider
        add_filter( 'fluent_crm/form_submission_providers', [ $this, 'register_provider' ], 20, 1 );

        //Display Data
        add_filter( 'fluentcrm_get_form_submissions_' . self::$provider, [ $this, 'display_form_submissions'], 20, 2 );



    }





    /**
     *  Register this provider
     * 
     * 
     */
    public function register_provider( $providers ) {

        $providers[self::$provider] = [
            'title' => self::$providerTitle,
            'name' => self::$provider
        ];


        return $providers;

    }






    /**
     *  Maybe setup the Form Submission Section
     *  
     *  If it's already setup, we don't need to
     *  
     *  @return array
     */
    public function setup_form_submissions_section( $sections ) {

        if ( !isset( $sections['subscriber_form_submissions'] ) ) {

            $subscriber_form_submissions = [
                'name'    => 'subscriber_form_submissions',
                'title'   => __('Form Submissions', 'fluent-crm'),
                'handler' => 'route'
            ];

            //Put the form submissions after the emails
            $sections = insert_after_key( $sections, 'subscriber_emails', 'subscriber_form_submissions', $subscriber_form_submissions );

        }

        return $sections;

    }






    /**
     *  Display form submissions for Subscriber
     * 
     * 
     */
    public function display_form_submissions( $submission_data, $subscriber ) {

        //error_log( 'subscriber: ' . print_r( $subscriber, 1 ) );

        $email = isset( $subscriber->email ) ? $subscriber->email : false;

        //dlog( $email, 'subscriber email' );


        if ( $email !== false ) {

            $args = [
                'post_type'  => 'breakdance_form_res', // adjust as needed
                'meta_query' => [
                    [
                        'key'     => '_breakdance_fields',
                        'value'   => $email, // replace with the target email
                        'compare' => 'LIKE'
                    ]
                ]
            ];
            
            $query = new \WP_Query( $args );
            
            $formattedSubmissions = [];

            $total_submissions = 0;

            // Loop through the results
            if ( $query->have_posts() ) {

                //dlog($query, 'query of forms' );

                $total_submissions = $query->post_count;
               
                foreach( $query->posts as $submission ) {
                    
                    $submission_meta = \Breakdance\Forms\Submission\getMeta( $submission->ID );

                    //dlog( $submission_meta, 'submission_meta' );

                    if ($submission_meta['postId'] && $submission_meta['builderUrl']) {
                        $form_link = sprintf('<a href="%s" target="_blank">%s (%s)</a>', $submission_meta['builderUrl'], $submission_meta['formName'], $submission_meta['postId']);
                    } else {
                        $form_link = '<i>Deleted Form</i>';
                    }

                    //Prepare the data
                    $formattedSubmissions[] = [
                        'id' => '#' . $submission->ID,
                        'title' => $submission_meta['postTitle'],
                        'form' => $form_link,
                        'submitted_at' => $submission_meta['date'],
                        'status' => $submission_meta['status'],
                        'action' => sprintf( '<a href="%s" target="_blank">View Submission</a>', get_edit_post_link( $submission ) ) 
                    ];



                } 
                
            } else {
                // No posts found
            }

            //dlog( $submissions, 'submissions' );

            $submission_data = [
                'total' => $total_submissions,
                'data' => $formattedSubmissions,
                'columns_config' => [
                    'id' => [
                        'label' => 'ID',
                        'width' => '100px'
                    ],
                    'title' => [
                        'label' => 'Post Title'
                    ],
                    'form' => [
                        'label' => 'Form'
                    ],
                    'submitted_at' => [
                        'label' => 'Submitted At',
                        'width' => '180px'
                    ],
                    'status' => [
                        'label' => 'Status',
                        'width' => '100px'
                    ],
                    'action' => [
                        'label' => 'Action',
                        'width' => '200px'
                    ]
                ]
            ];
            
        }


        return $submission_data; 

    }








    /**
     *  Get instance
     * 
     *  @return object
     * 
     */
    public static function get_instance() {

        if ( self::$instance == null ) {

            self::$instance = new self;

        }

        return self::$instance;

    }
    
}


FluentCrmFormSubmissionProviderBreakdance::get_instance();