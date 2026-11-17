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
add_action( 'wp_ajax_bric_maps_location_coords_set', __NAMESPACE__ . '\\ajax_location_coords_set' );

const GEOCODE_CACHE_POSTMETA_KEY = '_bric_maps_locations_geo_cache_v2';
const GEOCODE_CACHE_BATCH_LIMIT = 100;
const LOCATION_COORDS_POSTMETA_KEY = '_bric_maps_geocode';
const LOCATION_COORDS_NONCE_ACTION = 'bric_maps_location_coords_set';
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
        'bric_maps_get_public_post_types',
        __NAMESPACE__ . '\\get_public_post_type_choices',
        'edit'
    );

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
 * Public post types for the Related Posts control (resolved after CPT registration).
 *
 * @return array
 */
function get_public_post_type_choices() {
    $choices = [];
    foreach ( get_post_types( [], 'objects' ) as $post_type ) {
        if ( is_related_post_type_allowed( $post_type->name ) ) {
            $choices[] = [ 'value' => $post_type->name, 'text' => $post_type->labels->name ];
        }
    }
    return $choices;
}

/**
 * Location-style CPTs are often registered with public => false (no single
 * pages) but show_ui => true. Accept either; only published posts are queried.
 * Breakdance documents (templates, global blocks, ...) are never valid sources.
 *
 * @param string $post_type
 * @return bool
 */
function is_related_post_type_allowed( $post_type ) {
    $object = get_post_type_object( $post_type );
    if ( ! $object || ( ! $object->public && ! $object->show_ui ) ) {
        return false;
    }
    $document_types = defined( 'BREAKDANCE_ALL_EDITABLE_POST_TYPES' ) ? BREAKDANCE_ALL_EDITABLE_POST_TYPES : [];
    return ! in_array( $post_type, $document_types, true )
        && strpos( $post_type, 'breakdance_' ) !== 0
        && (bool) apply_filters( 'bric_google_maps_related_post_type_allowed', true, $post_type );
}

/**
 * Resolve the content post without confusing it with the rendered document ID.
 *
 * Breakdance\DynamicData's post fields use get_the_ID()/global $post, and
 * Breakdance\LoopBuilder\loopBlockPosts() calls the_post() before rendering a
 * block. Breakdance\Render\renderGlobalBlock() and render() keep that global
 * context; %%POSTID%% instead identifies the document being rendered.
 *
 * Builder SSR (Breakdance\Render\serverSideRender()) uses an any-URL AJAX
 * handler. The builder sends it to previewedItem.url, just like dynamic data,
 * so WordPress has already queried the selected preview post. That live query
 * takes precedence over template_last_previewed_item, which may be stale.
 *
 * Only for SSR through admin-ajax without a preview URL, fall back to the
 * triggering document's saved preview using Breakdance\Data\get_meta(), the
 * same accessor/key used by load_document(). Never use saved previews publicly.
 *
 * @return int
 */
function get_context_post_id() {
    $post_id = (int) get_the_ID();
    $document_types = defined( 'BREAKDANCE_ALL_EDITABLE_POST_TYPES' )
        ? BREAKDANCE_ALL_EDITABLE_POST_TYPES
        : [ 'breakdance_template', 'breakdance_block', 'breakdance_header', 'breakdance_footer', 'breakdance_popup', 'breakdance_acf_block', 'breakdance_part' ];

    if ( $post_id <= 0 || in_array( get_post_type( $post_id ), $document_types, true ) ) {
        $queried_post = get_queried_object();
        $post_id = $queried_post instanceof \WP_Post ? (int) $queried_post->ID : 0;
    }

    if ( $post_id > 0 && in_array( get_post_type( $post_id ), $document_types, true ) ) {
        $post_id = 0;
    }

    if (
        $post_id === 0 && wp_doing_ajax() &&
        empty( $_POST['breakdance_ajax_at_any_url'] ) &&
        function_exists( '\\Breakdance\\isRequestFromBuilderSsr' ) && \Breakdance\isRequestFromBuilderSsr() &&
        function_exists( '\\Breakdance\\Data\\get_meta' )
    ) {
        $document_id = absint( $_POST['triggeringDocument'] ?? 0 );
        if ( $document_id > 0 && current_user_can( 'edit_post', $document_id ) ) {
            $preview = \Breakdance\Data\get_meta( $document_id, '_breakdance_template_last_previewed_item' );
            $preview = is_object( $preview ) ? get_object_vars( $preview ) : $preview;
            if ( is_array( $preview ) && ! empty( $preview['url'] ) && is_string( $preview['url'] ) ) {
                $preview_id = url_to_postid( $preview['url'] );
                if ( $preview_id > 0 && ! in_array( get_post_type( $preview_id ), $document_types, true ) ) {
                    $post_id = (int) $preview_id;
                }
            }
        }
    }

    return absint( apply_filters( 'bric_google_maps_locations_context_post_id', $post_id ) );
}

/**
 * Normalize Related Posts addresses for display, hashing and geocoding. Keep the
 * legacy normalize_geocode_address() unchanged for existing host-post caches.
 *
 * @param mixed $address
 * @return string
 */
function normalize_location_address( $address ) {
    if ( ! is_scalar( $address ) ) {
        return '';
    }

    $address = html_entity_decode( (string) $address, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $address = preg_replace( '/<br\s*\/?\s*>|\r\n|\r|\n/i', ', ', $address );
    $address = wp_strip_all_tags( $address );
    $address = preg_replace( '/\s+/', ' ', str_replace( "\xc2\xa0", ' ', $address ) );
    $address = preg_replace( '/\s*,(?:\s*,)*\s*/', ', ', $address );
    return trim( $address, " \t\n\r\0\x0B," );
}

/**
 * Accept raw relationship arrays, serialized arrays, and single post-object IDs.
 * Exact integer comparisons prevent ID 12 matching ID 123.
 *
 * @param mixed $value
 * @return int[]
 */
function relationship_post_ids( $value ) {
    $value = maybe_unserialize( $value );
    if ( is_object( $value ) && isset( $value->ID ) ) {
        $value = $value->ID;
    } elseif ( is_array( $value ) && isset( $value['ID'] ) ) {
        $value = $value['ID'];
    }

    $ids = [];
    foreach ( is_array( $value ) ? $value : [ $value ] as $item ) {
        if ( is_object( $item ) && isset( $item->ID ) ) {
            $item = $item->ID;
        }
        if ( ( is_int( $item ) || is_string( $item ) ) && preg_match( '/^[1-9][0-9]*$/D', (string) $item ) ) {
            $ids[] = (int) $item;
        }
    }
    return array_values( array_unique( $ids ) );
}

function relationship_contains_post( $value, $post_id ) {
    return $post_id > 0 && in_array( (int) $post_id, relationship_post_ids( $value ), true );
}

/** Read unformatted ACF data; raw post meta also works when ACF is inactive. */
function get_location_field( $field, $post_id ) {
    if ( ! is_string( $field ) || trim( $field ) === '' ) {
        return '';
    }
    $field = trim( $field );
    return function_exists( 'get_field' ) ? get_field( $field, $post_id, false ) : get_post_meta( $post_id, $field, true );
}

/**
 * Query without changing global $post. WP_Query primes the posts' meta in bulk.
 *
 * @param array $settings
 * @param int $anchor
 * @return \WP_Post[]
 */
function query_related_posts( $settings, $anchor ) {
    $post_type = sanitize_key( $settings['post_type'] ?? 'location' );
    $relationship = $settings['relationship'] ?? 'points_to_current';
    $field = is_string( $settings['relationship_field'] ?? null ) ? trim( $settings['relationship_field'] ) : '';

    if ( ! is_related_post_type_allowed( $post_type ) || ! in_array( $relationship, [ 'points_to_current', 'listed_on_current', 'all' ], true ) ) {
        return [];
    }
    if ( $relationship !== 'all' && ( $anchor <= 0 || $field === '' ) ) {
        return [];
    }

    $limit = (int) ( $settings['limit'] ?? 0 );
    $limit = $limit > 0 ? min( 200, $limit ) : 200;
    $order_by = $settings['order_by'] ?? 'relationship_order';
    $order_by = in_array( $order_by, [ 'menu_order', 'title', 'date', 'relationship_order' ], true ) ? $order_by : 'menu_order';
    $order = strtoupper( $settings['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';
    $args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => true,
        'ignore_sticky_posts' => true,
        'orderby' => $order_by === 'relationship_order' ? 'menu_order' : $order_by,
        'order' => $order,
    ];

    if ( $relationship === 'points_to_current' ) {
        $args['meta_query'] = [
            'relation' => 'OR',
            [ 'key' => $field, 'value' => '"' . (int) $anchor . '"', 'compare' => 'LIKE' ],
            [ 'key' => $field, 'value' => (string) (int) $anchor, 'compare' => '=' ],
        ];
    } elseif ( $relationship === 'listed_on_current' ) {
        $ids = relationship_post_ids( get_location_field( $field, $anchor ) );
        if ( ! $ids ) {
            return []; // An empty post__in would otherwise return every post.
        }
        if ( $order_by === 'relationship_order' ) {
            // WordPress forces ASC for post__in; reverse the IDs explicitly.
            $ids = $order === 'DESC' ? array_reverse( $ids ) : $ids;
            $args['orderby'] = 'post__in';
        }
        $args['post__in'] = $ids;
    }

    $query = new \WP_Query( $args );
    $posts = [];
    foreach ( $query->posts as $post ) {
        if ( $relationship !== 'points_to_current' || relationship_contains_post( get_post_meta( $post->ID, $field, true ), $anchor ) ) {
            $posts[] = $post;
        }
    }
    return $posts;
}

/** @return array|null Valid, finite coordinates, including 0,0. */
function validate_location_coordinates( $value ) {
    if ( ! is_array( $value ) ) {
        return null;
    }
    $lat = filter_var( $value['lat'] ?? null, FILTER_VALIDATE_FLOAT );
    $lng = filter_var( $value['lng'] ?? null, FILTER_VALIDATE_FLOAT );
    if ( $lat === false || $lng === false || ! is_finite( $lat ) || ! is_finite( $lng ) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
        return null;
    }
    return [ 'lat' => (float) $lat, 'lng' => (float) $lng ];
}

function get_stored_location_coordinates( $post_id, $address ) {
    $address = normalize_location_address( $address );
    $stored = get_post_meta( $post_id, LOCATION_COORDS_POSTMETA_KEY, true );
    if ( $address === '' || ! is_array( $stored ) || ( $stored['address_hash'] ?? '' ) !== md5( $address ) ) {
        return null;
    }
    return validate_location_coordinates( $stored );
}

function location_geocode_failure_key( $post_id, $address ) {
    return 'bric_maps_geo_fail_' . (int) $post_id . '_' . md5( normalize_location_address( $address ) );
}

function save_location_coordinates( $post_id, $address, $coordinates, $source ) {
    $coordinates = validate_location_coordinates( $coordinates );
    $address = normalize_location_address( $address );
    if ( ! $coordinates || $address === '' ) {
        return;
    }
    update_post_meta( $post_id, LOCATION_COORDS_POSTMETA_KEY, array_merge( $coordinates, [
        'address_hash' => md5( $address ),
        'source' => $source === 'builder' ? 'builder' : 'server',
        'updated_at' => time(),
    ] ) );
    delete_transient( location_geocode_failure_key( $post_id, $address ) );
}

/** Server credentials never enter the element's HTML or JavaScript options. */
function get_server_api_key() {
    $key = apply_filters( 'bric_google_maps_server_api_key', '' );
    if ( is_string( $key ) && trim( $key ) !== '' ) {
        return trim( $key );
    }
    if ( defined( 'BRIC_GOOGLE_MAPS_SERVER_API_KEY' ) && trim( BRIC_GOOGLE_MAPS_SERVER_API_KEY ) !== '' ) {
        return trim( BRIC_GOOGLE_MAPS_SERVER_API_KEY );
    }
    if ( function_exists( '\\Breakdance\\APIKeys\\getKey' ) && defined( 'BREAKDANCE_GOOGLE_MAPS_API_KEY_NAME' ) ) {
        // getKey reads Breakdance\Data\get_global_option('api_keys').
        $key = \Breakdance\APIKeys\getKey( BREAKDANCE_GOOGLE_MAPS_API_KEY_NAME );
        if ( is_string( $key ) && trim( $key ) !== '' ) {
            return trim( $key );
        }
    }
    $api = apply_filters( 'acf/fields/google_map/api', [
        'key' => function_exists( 'acf_get_setting' ) ? acf_get_setting( 'google_api_key' ) : '',
        'client' => '',
        'libraries' => 'places',
        'ver' => 3,
        'callback' => 'Function.prototype',
        'language' => function_exists( 'acf_get_locale' ) ? acf_get_locale() : '',
    ] );
    return is_array( $api ) && is_string( $api['key'] ?? null ) ? trim( $api['key'] ) : '';
}

function record_location_geocode_failure( $post_id, $address, $reason ) {
    $key = location_geocode_failure_key( $post_id, $address );
    if ( get_transient( $key ) !== false ) {
        return;
    }
    set_transient( $key, $reason, max( 1, (int) apply_filters( 'bric_google_maps_geocode_negative_ttl', 12 * HOUR_IN_SECONDS ) ) );
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // Do not log the request URL: it contains the API key.
        error_log( '[Bric Google Maps] Geocoding location ' . (int) $post_id . ' failed: ' . $reason );
    }
}

/** Bounded across all map instances in this PHP request, not once per element. */
function geocode_location_address( $post_id, $address ) {
    static $requests = 0;
    $address = normalize_location_address( $address );
    if ( $address === '' || get_transient( location_geocode_failure_key( $post_id, $address ) ) !== false ) {
        return null;
    }
    $limit = max( 0, (int) apply_filters( 'bric_google_maps_geocode_limit', 5 ) );
    if ( $requests >= $limit ) {
        return null; // No negative marker: these locations can try next render.
    }
    $api_key = get_server_api_key();
    if ( $api_key === '' ) {
        record_location_geocode_failure( $post_id, $address, 'missing_api_key' );
        return null;
    }

    $requests++;
    $response = wp_remote_get( add_query_arg( [ 'address' => $address, 'key' => $api_key ], 'https://maps.googleapis.com/maps/api/geocode/json' ), [ 'timeout' => 4 ] );
    $coordinates = null;
    $reason = 'network_error';
    if ( ! is_wp_error( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $status = is_array( $body ) && is_string( $body['status'] ?? null ) ? $body['status'] : 'invalid_response';
        $reason = sanitize_key( $status );
        if ( wp_remote_retrieve_response_code( $response ) === 200 && $status === 'OK' ) {
            $coordinates = validate_location_coordinates( $body['results'][0]['geometry']['location'] ?? null );
            $reason = 'invalid_coordinates';
        }
    }
    if ( ! $coordinates ) {
        record_location_geocode_failure( $post_id, $address, $reason );
        return null;
    }
    save_location_coordinates( $post_id, $address, $coordinates, 'server' );
    return $coordinates;
}

/** Map field > address-matched post meta > bounded server geocoding. */
function resolve_location_coordinates( $post_id, $address, $map = [] ) {
    return validate_location_coordinates( $map )
        ?? get_stored_location_coordinates( $post_id, $address )
        ?? geocode_location_address( $post_id, $address );
}

/** Assemble the existing .location data contract from published location posts. */
function get_related_post_locations( $settings ) {
    $locations = [];
    foreach ( query_related_posts( $settings, get_context_post_id() ) as $post ) {
        $map = get_location_field( $settings['map_field'] ?? '', $post->ID );
        $address = normalize_location_address( get_location_field( $settings['address_field'] ?? 'address', $post->ID ) );
        if ( $address === '' && is_array( $map ) ) {
            $address = normalize_location_address( $map['address'] ?? '' );
        }
        $name = get_location_field( $settings['name_field'] ?? '', $post->ID );
        $name = is_scalar( $name ) ? trim( wp_strip_all_tags( (string) $name ) ) : '';
        $coordinates = resolve_location_coordinates( $post->ID, $address, $map );
        $locations[] = [
            'post_id' => (int) $post->ID,
            'name' => $name !== '' ? $name : get_the_title( $post->ID ),
            'address' => $address,
            'address_hash' => md5( $address ),
            'coordinates' => $coordinates ? $coordinates['lat'] . ',' . $coordinates['lng'] : '',
        ];
    }
    return $locations;
}

/** Authenticated builder fallback; editing a template alone grants no access. */
function ajax_location_coords_set() {
    check_ajax_referer( LOCATION_COORDS_NONCE_ACTION, 'nonce' );
    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( $post_id <= 0 || ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        return;
    }

    $address = is_string( $_POST['address'] ?? null ) ? normalize_location_address( wp_unslash( $_POST['address'] ) ) : '';
    $hash = is_string( $_POST['address_hash'] ?? null ) ? wp_unslash( $_POST['address_hash'] ) : '';
    $coordinates = validate_location_coordinates( [ 'lat' => $_POST['lat'] ?? null, 'lng' => $_POST['lng'] ?? null ] );
    if ( $address === '' || $hash !== md5( $address ) || ! $coordinates ) {
        wp_send_json_error( [ 'message' => 'Invalid address hash or coordinates.' ], 400 );
        return;
    }

    // Retain the rendered address hash, even if the address changed meanwhile;
    // such a stale result will fail get_stored_location_coordinates() next time.
    save_location_coordinates( $post_id, $address, $coordinates, 'builder' );
    wp_send_json_success( [ 'updated' => true ] );
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
