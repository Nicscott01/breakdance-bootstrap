<?php

namespace BricBreakdance;


add_action( 'breakdance_loaded', function() {


    \Breakdance\AJAX\register_handler(
        'bdbs_fetch_taxonomies',
        function() {
    

            //get taxonomies
            $taxonomies = get_taxonomies( [], 'objects' );
    
    
            $taxs_array = [];
    
            if ( $taxonomies ) {
    
                foreach ( $taxonomies as $tax ) {
    
                    $taxs_array[] = [
                        'value' => $tax->name,
                        'text' => $tax->label
                    ];
                    
                    
                }
            }
    
    
    
    
            return $taxs_array;
        },
        'edit'
    );
    



    \Breakdance\AJAX\register_handler(
        'bdbs_fetch_terms',
        function( $args ) {
    
            //error_log( 'input_post: ' . print_r( filter_input_array( INPUT_POST, ['args' => 'context'] ), 1 ));
            //error_log( 'post: ' . print_r( $_POST, 1 ));
            //error_log( 'args: ' . print_r( $args, 1 ));


            $taxonomy = $args['context'];
            
            //get terms
            $terms = get_terms( [
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ] );

   
    
            $terms_array = [];
    
            if ( $terms ) {
    
                foreach ( $terms as $term ) {

                    //error_log( json_encode( $term ) );
    
                    $terms_array[] = [
                        'value' => (string) $term->term_id,
                        'text' => $term->name
                    ];
                    
                    
                }
            }
    

            return $terms_array;
        },
        'edit',
        false,
        [
            'args' => [
                // 'requestData' is treated as an array; you can extract nested elements manually
                'requestData' => [
                    'filter' => FILTER_DEFAULT,
                    'flags'  => FILTER_REQUIRE_ARRAY
                ],
            ]
        ]
        
    );
    


});

