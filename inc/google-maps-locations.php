<?php
/**
 *  Register the meta REST route
 *  
 */
namespace BricBreakdance\GoogleMapsLocations;

add_action( 'breakdance_loaded', 'BricBreakdance\GoogleMapsLocations\register_breakdance_ajax_handlers');
add_action( 'wp_ajax_bric_maps_locations_cache_get', 'BricBreakdance\GoogleMapsLocations\ajax_geocode_cache_get' );
add_action( 'wp_ajax_nopriv_bric_maps_locations_cache_get', 'BricBreakdance\GoogleMapsLocations\ajax_geocode_cache_get' );
add_action( 'wp_ajax_bric_maps_locations_cache_set', 'BricBreakdance\GoogleMapsLocations\ajax_geocode_cache_set' );

const GEOCODE_CACHE_POSTMETA_KEY = '_bric_maps_locations_geo_cache_v2';
const GEOCODE_CACHE_BATCH_LIMIT = 100;
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
                        $val_arr = [
                            'name' => $sub_field['name'],
                            'type' => $sub_field['type'],
                            'is_post' => true,
                            'posttypes' => $post_types
                        ];
                        $value = json_encode( $val_arr ); 
                        //$value = $sub_field['name'] . '__is_post__posttypes__' . implode( '_', $post_types );
                        
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

                $context = isset( $_POST['requestData']['context'] ) ? stripslashes( $_POST['requestData']['context'] ) : false;
             
                if ( $context === false ) {
                    return [];
                }

                // Decode once
                $decoded = json_decode($context, true);

                if (is_string($decoded)) {
                    // Decode again if it's still a string (double-encoded)
                    $decoded = json_decode($decoded, true);
                }

                $context_arr = $decoded;

                $post_types = $decoded['posttypes'];

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

/**
 * Normalize an address for stable cache keys.
 *
 * @param string $address
 * @return string
 */
function normalize_geocode_address( $address ) {
	$normalized = trim( (string) $address );

	if ( $normalized === '' ) {
		return '';
	}

	$normalized = preg_replace( '/\s+/', ' ', $normalized );

	if ( function_exists( 'mb_strtolower' ) ) {
		$normalized = mb_strtolower( $normalized, 'UTF-8' );
	} else {
		$normalized = strtolower( $normalized );
	}

	return $normalized;
}

/**
 * Get the geocode cache map for a specific post.
 *
 * @param int $post_id
 * @return array
 */
function get_post_geocode_cache( $post_id ) {
	$cache = get_post_meta( $post_id, GEOCODE_CACHE_POSTMETA_KEY, true );
	if ( ! is_array( $cache ) ) {
		return [];
	}

	$sanitized_cache = [];
	foreach ( $cache as $normalized_address => $entry ) {
		$normalized_key = normalize_geocode_address( (string) $normalized_address );
		if ( $normalized_key === '' || ! is_array( $entry ) ) {
			continue;
		}

		$lat = filter_var( $entry['lat'] ?? null, FILTER_VALIDATE_FLOAT );
		$lng = filter_var( $entry['lng'] ?? null, FILTER_VALIDATE_FLOAT );
		$updated_at = isset( $entry['updated_at'] ) ? (int) $entry['updated_at'] : time();

		if ( $lat === false || $lng === false ) {
			continue;
		}

		$sanitized_cache[ $normalized_key ] = [
			'lat' => (float) $lat,
			'lng' => (float) $lng,
			'updated_at' => $updated_at,
		];
	}

	return $sanitized_cache;
}

/**
 * Save the geocode cache map for a specific post.
 *
 * @param int $post_id
 * @param array $cache
 * @return void
 */
function save_post_geocode_cache( $post_id, $cache ) {
	update_post_meta( $post_id, GEOCODE_CACHE_POSTMETA_KEY, $cache );
}

/**
 * Read and sanitize addresses from request input.
 *
 * @param mixed $raw_addresses
 * @return array
 */
function sanitize_address_list( $raw_addresses ) {
	if ( is_string( $raw_addresses ) ) {
		$decoded = json_decode( wp_unslash( $raw_addresses ), true );
		if ( is_array( $decoded ) ) {
			$raw_addresses = $decoded;
		}
	}

	if ( ! is_array( $raw_addresses ) ) {
		return [];
	}

	$addresses = [];
	foreach ( $raw_addresses as $raw_address ) {
		$address = trim( sanitize_text_field( (string) $raw_address ) );
		if ( $address === '' ) {
			continue;
		}
		$addresses[] = $address;
	}

	return array_values( array_slice( $addresses, 0, GEOCODE_CACHE_BATCH_LIMIT ) );
}

/**
 * AJAX: fetch geocode cache entries by address list.
 *
 * @return void
 */
function ajax_geocode_cache_get() {
	$post_id = absint( $_POST['post_id'] ?? 0 );
	$addresses = sanitize_address_list( $_POST['addresses'] ?? [] );

	if ( $post_id <= 0 || empty( $addresses ) ) {
		wp_send_json_success(
			[
				'cache' => [],
			]
		);
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		wp_send_json_success(
			[
				'cache' => [],
			]
		);
	}

	if ( ! is_user_logged_in() && $post->post_status !== 'publish' ) {
		wp_send_json_success(
			[
				'cache' => [],
			]
		);
	}

	$post_cache = get_post_geocode_cache( $post_id );
	$cache = [];

	foreach ( $addresses as $address ) {
		$normalized_address = normalize_geocode_address( $address );

		if ( $normalized_address === '' ) {
			continue;
		}

		$entry = $post_cache[ $normalized_address ] ?? null;
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$lat = filter_var( $entry['lat'] ?? null, FILTER_VALIDATE_FLOAT );
		$lng = filter_var( $entry['lng'] ?? null, FILTER_VALIDATE_FLOAT );

		if ( $lat === false || $lng === false ) {
			continue;
		}

		$cache[ $normalized_address ] = [
			'lat' => (float) $lat,
			'lng' => (float) $lng,
		];
	}

	wp_send_json_success(
		[
			'cache' => $cache,
		]
	);
}

/**
 * AJAX: set/update geocode cache entries.
 *
 * @return void
 */
function ajax_geocode_cache_set() {
	$post_id = absint( $_POST['post_id'] ?? 0 );
	$can_edit_target_post = current_user_can( 'edit_post', $post_id );
	$can_edit_generic_posts = current_user_can( 'edit_posts' );
	$can_manage_options = current_user_can( 'manage_options' );

	if ( $post_id <= 0 || ( ! $can_edit_target_post && ! $can_edit_generic_posts && ! $can_manage_options ) ) {
		wp_send_json_error(
			[
				'message' => 'Unauthorized.',
			],
			403
		);
	}

	$raw_entries = $_POST['entries'] ?? [];

	if ( is_string( $raw_entries ) ) {
		$raw_entries = json_decode( wp_unslash( $raw_entries ), true );
	}

	if ( ! is_array( $raw_entries ) ) {
		wp_send_json_error(
			[
				'message' => 'Invalid entries payload.',
			],
			400
		);
	}

	$entries = array_slice( $raw_entries, 0, GEOCODE_CACHE_BATCH_LIMIT );
	$post_cache = get_post_geocode_cache( $post_id );
	$updated = 0;

	foreach ( $entries as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$address = trim( sanitize_text_field( (string) ( $entry['address'] ?? '' ) ) );
		$normalized_address = normalize_geocode_address( $address );

		if ( $normalized_address === '' ) {
			continue;
		}

		$lat = filter_var( $entry['lat'] ?? null, FILTER_VALIDATE_FLOAT );
		$lng = filter_var( $entry['lng'] ?? null, FILTER_VALIDATE_FLOAT );

		if ( $lat === false || $lng === false ) {
			continue;
		}

		$lat = (float) $lat;
		$lng = (float) $lng;

		if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
			continue;
		}

		$post_cache[ $normalized_address ] = [
			'lat' => $lat,
			'lng' => $lng,
			'updated_at' => time(),
		];

		$updated++;
	}

	save_post_geocode_cache( $post_id, $post_cache );

	wp_send_json_success(
		[
			'updated' => $updated,
		]
	);
}
