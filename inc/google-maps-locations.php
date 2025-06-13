<?php
/**
 *  Register the meta REST route
 *  
 */
namespace BricBreakdance\GoogleMapsLocations;

add_action( 'breakdance_loaded', 'BricBreakdance\GoogleMapsLocations\register_breakdance_ajax_handlers');
/*
 * NOT SURE IF THIS IS NEEDED
add_action('init', function() {

    register_meta('post', 'bric_maps_locations', array(
        'show_in_rest' => true,
        'type' => 'object', // Adjust the type accordingly
        'single' => true,
    ));

});



add_action('wp_ajax_bric_maps_locations_update', function() {
    // Check user permissions
    if (!current_user_can('edit_post', $_POST['post_id'])) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    // Sanitize and validate input
    $post_id = intval($_POST['post_id']);
    $meta_key = sanitize_text_field($_POST['meta_key']);
    $meta_value = sanitize_text_field($_POST['meta_value']); // Use sanitize_text_field for basic strings; adjust if storing complex data

    // Update the meta value
    if (update_post_meta($post_id, $meta_key, $meta_value)) {
        wp_send_json_success(['message' => 'Meta updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to update meta']);
    }
});

*/





 function register_breakdance_ajax_handlers() {

    \Breakdance\AJAX\register_handler(
            'get_acf_field_names_for_repeater',
            function() {

                $context = isset( $_POST['requestData']['context'] ) ? $_POST['requestData']['context'] : false;
             
                if ( $context === false ) {
                    return [];
                }

                //Get the ACF field names for a repeater field
                $field = str_replace( 'acf_repeater_', '', $context['field']);
                $field_object = get_field_object($field);

                if ( ! $field_object ) {
                    return [];
                }

                // Prefer sub field definitions from the field's settings if available, fallback to the direct sub_fields key.
                if ( isset( $field_object['settings'] ) && isset( $field_object['settings']['sub_fields'] ) && is_array( $field_object['settings']['sub_fields'] ) ) {
                    $sub_fields = $field_object['settings']['sub_fields'];
                } elseif ( isset( $field_object['sub_fields'] ) && is_array( $field_object['sub_fields'] ) ) {
                    $sub_fields = $field_object['sub_fields'];
                } else {
                    return [];
                }

                $choices = [];
                foreach ( $sub_fields as $sub_field ) {
                    // Use the label from the settings (if defined) or fallback to the standard label.
                    $label = isset( $sub_field['label'] ) ? $sub_field['label'] : $sub_field['name'];
                    
                    if ( $sub_field['type'] == 'relationship' || $sub_field['type'] == 'post_object' ) {
                        
                        //Make a list of post types that this relationship field can pull from
                        $post_types = isset( $sub_field['post_type'] ) ? $sub_field['post_type'] : [];
                        $value = 'is_post__posttypes__' . implode( '_', $post_types );
                        
                    } else {
                        $value = $sub_field['name'];
                    }

                    $choices[] = [
                        'value' => $value,
                        'text'  => $label,
                    ];
                }

                return $choices;

               
            },
            'edit'
        );
 
 
    \Breakdance\AJAX\register_handler(
            'get_acf_field_names_for_nested_relationship',
            function() {

                $context = isset( $_POST['requestData']['context'] ) ? $_POST['requestData']['context'] : false;
             
                if ( $context === false ) {
                    return [];
                }


                //Get the context of what post types we need to get the ACF field names for
                if ( strpos( $context, 'is_post__posttypes__' ) === 0 ) {
                    $post_types = explode( '_', str_replace( 'is_post__posttypes__', '', $context ) );
                } else {
                    $post_types = [];
                }

                //Build a list of all availalbe ACF fields for the post types in the context
                $acf_fields = [];
                foreach ( $post_types as $post_type ) {
                    $fields = \BricBreakdance\GoogleMapsLocations\get_acf_fields_for_post_type( $post_type );


                    if ( $fields ) {
                        foreach ( $fields as $field ) {
                            if ( isset( $field['name'] ) && isset( $field['label'] ) ) {
                                $acf_fields[] = [
                                    'value' => $field['name'],
                                    'text'  => $field['label'],
                                ];
                            }
                        }
                    }
                }

                // If no fields found, return an empty array
                if ( empty( $acf_fields ) ) {
                    return [];
                }

                $array = [
                    [
                        'value' => 'test_1',
                        'text' => 'Test 1'
                    ],[
                        'value' => 'test_2',
                        'text' => 'Test 2'
                    ]
                ];



                return $acf_fields;


            },
            'edit'
        );
 
 
    }



/**
 * Get ACF fields for a specific post type.
 *
 * @param string $post_type The post type to get fields for.
 * @return array An associative array of field names and their definitions.
 */
function get_acf_fields_for_post_type( $post_type ) {
    if ( ! function_exists( 'acf_get_field_groups' ) ) {
        return [];
    }

    $fields = [];

    // Get all field groups for the given post type
    $field_groups = acf_get_field_groups( [ 'post_type' => $post_type ] );

    foreach ( $field_groups as $group ) {
        $group_fields = acf_get_fields( $group['key'] );

        if ( ! $group_fields ) {
            continue;
        }

        foreach ( $group_fields as $field ) {
            $fields[ $field['name'] ] = $field;
        }
    }

    return $fields;
}

