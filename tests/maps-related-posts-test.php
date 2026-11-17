<?php
/** Standalone: php tests/maps-related-posts-test.php (no WordPress or network). */
namespace Breakdance {
    function isRequestFromBuilderSsr() { return $GLOBALS['builder_ssr']; }
}
namespace Breakdance\Data {
    function get_meta( $id, $key ) { return \get_post_meta( $id, $key, true ); }
}
namespace Breakdance\APIKeys {
    function getKey( $key ) { return $GLOBALS['breakdance_key']; }
}
namespace {
    use function BricBreakdance\GoogleMapsLocations\relationship_contains_post;
    use function BricBreakdance\GoogleMapsLocations\relationship_post_ids;
    use function BricBreakdance\GoogleMapsLocations\normalize_location_address;
    use function BricBreakdance\GoogleMapsLocations\get_stored_location_coordinates;
    use function BricBreakdance\GoogleMapsLocations\resolve_location_coordinates;
    use function BricBreakdance\GoogleMapsLocations\validate_location_coordinates;
    use function BricBreakdance\GoogleMapsLocations\query_related_posts;
    use function BricBreakdance\GoogleMapsLocations\get_related_post_locations;
    use function BricBreakdance\GoogleMapsLocations\get_context_post_id;
    use function BricBreakdance\GoogleMapsLocations\get_server_api_key;
    use function BricBreakdance\GoogleMapsLocations\geocode_location_address;
    use function BricBreakdance\GoogleMapsLocations\location_geocode_failure_key;
    use function BricBreakdance\GoogleMapsLocations\ajax_location_coords_set;
    use const BricBreakdance\GoogleMapsLocations\LOCATION_COORDS_POSTMETA_KEY;

    define( 'HOUR_IN_SECONDS', 3600 );
    define( 'WP_DEBUG', false );
    define( 'BREAKDANCE_GOOGLE_MAPS_API_KEY_NAME', 'google_maps_api_key' );
    $meta = $posts = $transients = $filters = $http_calls = $field_reads = [];
    $post = $queried_post = null;
    $builder_ssr = $doing_ajax = false;
    $can_edit = true;
    $breakdance_key = $acf_key = '';
    $url_ids = [];
    $assertions = 0;

    set_error_handler( function ( $severity, $message, $file, $line ) {
        if ( error_reporting() & $severity ) {
            throw new \ErrorException( $message, 0, $severity, $file, $line );
        }
        return false;
    } );

    class WP_Post {
        public $ID, $post_type, $post_status, $post_title, $menu_order, $post_date;
        function __construct( $id, $title, $type = 'location', $status = 'publish', $menu_order = 0 ) {
            $this->ID = $id;
            $this->post_title = $title;
            $this->post_type = $type;
            $this->post_status = $status;
            $this->menu_order = $menu_order;
            $this->post_date = '2026-01-01';
        }
    }
    class WP_Error {}
    class TestAjaxResponse extends \Exception {
        public $success, $data;
        function __construct( $success, $data, $status ) {
            parent::__construct( 'AJAX response', $status );
            $this->success = $success;
            $this->data = $data;
        }
    }
    class WP_Query {
        public $posts;
        function __construct( $args ) {
            $GLOBALS['last_query'] = $args;
            $posts = array_filter( $GLOBALS['posts'], function ( $post ) use ( $args ) {
                if ( $post->post_type !== $args['post_type'] || $post->post_status !== $args['post_status'] ) { return false; }
                if ( isset( $args['post__in'] ) && ! in_array( $post->ID, $args['post__in'], true ) ) { return false; }
                if ( isset( $args['meta_query'] ) ) {
                    foreach ( $args['meta_query'] as $clause ) {
                        if ( ! is_array( $clause ) ) { continue; }
                        $raw = $GLOBALS['meta'][ $post->ID ][ $clause['key'] ] ?? '';
                        $raw = is_array( $raw ) ? serialize( $raw ) : (string) $raw;
                        if ( $clause['compare'] === 'LIKE' ? strpos( $raw, $clause['value'] ) !== false : $raw === $clause['value'] ) { return true; }
                    }
                    return false;
                }
                return true;
            } );
            usort( $posts, function ( $a, $b ) use ( $args ) {
                if ( $args['orderby'] === 'post__in' ) {
                    // WP ignores DESC for post__in, so the production helper must reverse IDs.
                    return array_search( $a->ID, $args['post__in'], true ) <=> array_search( $b->ID, $args['post__in'], true );
                }
                $key = [ 'title' => 'post_title', 'date' => 'post_date', 'menu_order' => 'menu_order' ][ $args['orderby'] ];
                return ( $a->$key <=> $b->$key ) * ( $args['order'] === 'DESC' ? -1 : 1 );
            } );
            $this->posts = array_slice( $posts, 0, $args['posts_per_page'] );
        }
    }
    function add_action( $hook, $callback ) {}
    function apply_filters( $hook, $value ) { return isset( $GLOBALS['filters'][ $hook ] ) ? $GLOBALS['filters'][ $hook ]( $value ) : $value; }
    function absint( $value ) { return abs( (int) $value ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
    function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
    function wp_unslash( $value ) { return stripslashes( $value ); }
    function maybe_unserialize( $value ) {
        return is_string( $value ) && preg_match( '/^(?:a|s|i|b):/', $value ) ? unserialize( $value, [ 'allowed_classes' => false ] ) : $value;
    }
    function get_post_meta( $id, $key, $single = true ) { return maybe_unserialize( $GLOBALS['meta'][ $id ][ $key ] ?? '' ); }
    function update_post_meta( $id, $key, $value ) { $GLOBALS['meta'][ $id ][ $key ] = $value; return true; }
    function get_field( $key, $id = false, $format = true ) {
        $GLOBALS['field_reads'][] = [ $key, $id, $format ];
        return get_post_meta( $id ?: get_the_ID(), $key, true );
    }
    function acf_get_setting( $key ) { return $GLOBALS['acf_key']; }
    function get_post( $id = null ) { return $id === null ? $GLOBALS['post'] : ( $GLOBALS['posts'][ $id ] ?? null ); }
    function get_post_type( $id ) { $post = get_post( $id ); return $post ? $post->post_type : false; }
    function get_post_type_object( $type ) {
        // 'location' mirrors a real site: no public single pages, but editable in wp-admin.
        $types = [
            'location' => (object) [ 'public' => false, 'show_ui' => true ],
            'trents_tree' => (object) [ 'public' => true, 'show_ui' => true ],
            'internal_type' => (object) [ 'public' => false, 'show_ui' => false ],
        ];
        return $types[ $type ] ?? null;
    }
    function get_the_title( $id ) { return $GLOBALS['posts'][ $id ]->post_title; }
    function get_the_ID() { return $GLOBALS['post']->ID ?? 0; }
    function get_queried_object() { return $GLOBALS['queried_post']; }
    function wp_doing_ajax() { return $GLOBALS['doing_ajax']; }
    function current_user_can( $capability, $id ) { return $GLOBALS['can_edit']; }
    function url_to_postid( $url ) { return $GLOBALS['url_ids'][ $url ] ?? 0; }
    function get_transient( $key ) { return $GLOBALS['transients'][ $key ]['value'] ?? false; }
    function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = [ 'value' => $value, 'ttl' => $ttl ]; }
    function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); }
    function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
    function wp_remote_get( $url, $args ) { $GLOBALS['http_calls'][] = [ $url, $args ]; return $GLOBALS['http_response']; }
    function is_wp_error( $response ) { return $response instanceof WP_Error; }
    function wp_remote_retrieve_body( $response ) { return $response['body']; }
    function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
    function wp_send_json_error( $data, $status = 200 ) { throw new TestAjaxResponse( false, $data, $status ); }
    function wp_send_json_success( $data ) { throw new TestAjaxResponse( true, $data, 200 ); }
    function check_ajax_referer( $action, $field ) {
        if ( ( $_POST[ $field ] ?? '' ) !== 'test-nonce' ) { wp_send_json_error( [], 403 ); }
    }
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
    function wp_kses( $markup, $allowed ) { return $markup; }

    require __DIR__ . '/../inc/google-maps-locations.php';

    function check( $condition, $label ) {
        $GLOBALS['assertions']++;
        if ( ! $condition ) { throw new \RuntimeException( $label ); }
    }
    function ids( $posts ) { return array_map( function ( $post ) { return $post->ID; }, $posts ); }
    function render_map( $data ) {
        $propertiesData = [ 'content' => [ 'data' => $data ] ];
        ob_start();
        include __DIR__ . '/../elements/Google_Maps_Locations_v2/ssr.php';
        return ob_get_clean();
    }
    function ajax_result( $input ) {
        $_POST = $input;
        try { ajax_location_coords_set(); } catch ( TestAjaxResponse $response ) { return $response; }
        throw new \RuntimeException( 'AJAX did not return a response' );
    }

    try {
        check( relationship_contains_post( serialize( [ '12', '123' ] ), 12 ), 'Serialized relationship matches 12' );
        check( relationship_contains_post( [ '12', '123' ], 123 ), 'Unserialized relationship matches 123' );
        check( relationship_contains_post( '12', 12 ) && relationship_contains_post( 12, 12 ), 'Single post-object IDs match' );
        check( ! relationship_contains_post( serialize( [ '123' ] ), 12 ), '12 never matches serialized 123' );
        check( ! relationship_contains_post( '123', 12 ), '12 never matches single 123' );
        check( ! relationship_contains_post( '12 garbage', 12 ) && ! relationship_contains_post( '', 0 ), 'Invalid IDs fail closed' );
        check( relationship_post_ids( [ '123', '12', '123' ] ) === [ 123, 12 ], 'Deduplication retains order' );
        check( normalize_location_address( " , <b>869 Bragdon Road</b><br />\n Wells,   ME, , " ) === '869 Bragdon Road, Wells, ME', 'HTML, newlines, spaces and stray commas normalize' );
        check( normalize_location_address( "869 Bragdon Road\r\nWells, ME" ) === '869 Bragdon Road, Wells, ME', 'CRLF becomes a comma' );
        check( normalize_location_address( 'A&nbsp;Street&lt;br&gt;Town' ) === 'A Street, Town', 'HTML entities normalize' );
        check( normalize_location_address( [] ) === '', 'Non-scalar address is empty' );
        $address = '869 Bragdon Road, Wells, ME';
        $meta[101][ LOCATION_COORDS_POSTMETA_KEY ] = [ 'address_hash' => md5( $address ), 'lat' => '43.3', 'lng' => '-70.6', 'source' => 'builder' ];
        $stored = [ 'lat' => 43.3, 'lng' => -70.6 ];
        check( get_stored_location_coordinates( 101, $address ) === $stored, 'Stored hash matches normalized address' );
        check( get_stored_location_coordinates( 101, "869 Bragdon Road\nWells, ME" ) === $stored, 'Equivalent multiline address keeps coordinates' );
        check( get_stored_location_coordinates( 101, 'Different Street, Wells, ME' ) === null, 'Changed address invalidates coordinates' );
        check( get_stored_location_coordinates( 101, '' ) === null, 'Empty address cannot reuse stored coordinates' );
        check( validate_location_coordinates( [ 'lat' => 0, 'lng' => 0 ] ) === [ 'lat' => 0.0, 'lng' => 0.0 ], '0,0 is valid' );
        check( validate_location_coordinates( [ 'lat' => 91, 'lng' => 0 ] ) === null && validate_location_coordinates( [ 'lat' => 0, 'lng' => -181 ] ) === null, 'Coordinate ranges enforced' );
        check( validate_location_coordinates( [ 'lat' => '', 'lng' => 0 ] ) === null, 'Empty coordinate is not zero' );
        check( resolve_location_coordinates( 101, $address, [ 'lat' => 44, 'lng' => -71 ] ) === [ 'lat' => 44.0, 'lng' => -71.0 ], 'Map field takes priority over stored coordinates' );
        check( resolve_location_coordinates( 101, $address, [ 'lat' => 95, 'lng' => 0 ] ) === $stored, 'Invalid map falls through to stored coordinates' );
        check( count( $http_calls ) === 0, 'Map and stored coordinates require no HTTP calls' );
        check( resolve_location_coordinates( 101, '', [] ) === null && count( $http_calls ) === 0, 'No address never geocodes a title' );

        $posts = [
            101 => new WP_Post( 101, 'B Location', 'location', 'publish', 2 ),
            102 => new WP_Post( 102, 'C Location', 'location', 'publish', 1 ),
            103 => new WP_Post( 103, 'A Location', 'location', 'publish', 3 ),
            104 => new WP_Post( 104, 'Draft', 'location', 'draft' ),
            105 => new WP_Post( 105, 'False positive' ),
            12 => new WP_Post( 12, '2026', 'trents_tree' ),
            3973 => new WP_Post( 3973, '2025', 'trents_tree' ),
            2465 => new WP_Post( 2465, 'Map block', 'breakdance_block' ),
        ];
        $meta[101]['trents_tree'] = serialize( [ '12', '123' ] );
        $meta[102]['trents_tree'] = '12';
        $meta[103]['trents_tree'] = serialize( [ '123' ] );
        $meta[104]['trents_tree'] = '12';
        $meta[105]['trents_tree'] = 'garbage "12"';
        $settings = [ 'post_type' => 'location', 'relationship' => 'points_to_current', 'relationship_field' => 'trents_tree', 'order_by' => 'title' ];
        check( ids( query_related_posts( $settings, 12 ) ) === [ 101, 102 ], 'Query verifies serialized/single candidates and excludes drafts and false positives' );
        check( $last_query['no_found_rows'] && ! $last_query['update_post_term_cache'] && $last_query['update_post_meta_cache'], 'Query avoids count/term work and primes meta' );
        check( query_related_posts( $settings, 0 ) === [], 'Relationship query without anchor is empty' );
        $meta[12]['places'] = [ '103', '101', '102' ];
        $settings = [ 'relationship' => 'listed_on_current', 'relationship_field' => 'places' ];
        check( ids( query_related_posts( $settings, 12 ) ) === [ 103, 101, 102 ], 'Listed relationship preserves field order by default' );
        check( ids( query_related_posts( $settings + [ 'order' => 'DESC' ], 12 ) ) === [ 102, 101, 103 ], 'Listed relationship DESC reverses IDs' );
        check( ids( query_related_posts( $settings + [ 'order_by' => 'menu_order' ], 12 ) ) === [ 102, 101, 103 ], 'Explicit menu order overrides relationship order' );
        check( ids( query_related_posts( $settings + [ 'order_by' => 'title', 'limit' => 2 ], 12 ) ) === [ 103, 101 ], 'Explicit title order and limit apply' );
        $meta[12]['places'] = [];
        check( query_related_posts( $settings, 12 ) === [], 'Empty listed relationship never becomes all posts' );
        check( count( query_related_posts( [ 'relationship' => 'all', 'limit' => 500 ], 0 ) ) === 4 && $last_query['posts_per_page'] === 200, 'All needs no anchor and limit is capped' );
        check( query_related_posts( [ 'post_type' => 'breakdance_block', 'relationship' => 'all' ], 0 ) === [], 'Nonpublic/unknown post types are rejected' );
        check( query_related_posts( [ 'post_type' => 'internal_type', 'relationship' => 'all' ], 0 ) === [], 'Post types without public or show_ui are rejected' );
        check( ! in_array( true, array_column( $field_reads, 2 ), true ), 'Related ACF fields are always read unformatted' );

        $post = $posts[2465]; $queried_post = $posts[12];
        check( get_context_post_id() === 12, 'Global Block document does not replace viewed year' );
        $post = $posts[3973];
        check( get_context_post_id() === 3973, 'Current loop/preview post follows Breakdance dynamic data' );
        $post = $posts[2465]; $queried_post = null;
        check( get_context_post_id() === 0, 'No content context never uses a document ID' );
        $doing_ajax = $builder_ssr = true;
        $_POST = [ 'triggeringDocument' => 2465 ];
        $meta[2465]['_breakdance_template_last_previewed_item'] = [ 'url' => 'https://example.test/2025/' ];
        $url_ids['https://example.test/2025/'] = 3973;
        check( get_context_post_id() === 3973, 'Admin-ajax SSR can use saved preview' );
        $post = $posts[12];
        check( get_context_post_id() === 12, 'Live preview wins over saved preview' );
        $post = $posts[2465]; $_POST['breakdance_ajax_at_any_url'] = true;
        check( get_context_post_id() === 0, 'Any-URL request with no content context never uses stale saved preview' );
        $doing_ajax = $builder_ssr = false; $_POST = [];
        $filters['bric_google_maps_locations_context_post_id'] = function () { return 12; };
        check( get_context_post_id() === 12, 'Context filter can override an unresolved anchor' );
        unset( $filters['bric_google_maps_locations_context_post_id'] );

        $meta[103]['address'] = '';
        $meta[103]['location'] = [ 'address' => "1 Main Street\nWells, ME", 'lat' => 42, 'lng' => -70 ];
        $location = get_related_post_locations( [ 'relationship' => 'all', 'order_by' => 'title', 'limit' => 1, 'map_field' => 'location' ] )[0];
        check( $location['post_id'] === 103 && $location['address'] === '1 Main Street, Wells, ME' && $location['coordinates'] === '42,-70', 'Map field supplies address and coordinates' );
        $meta[103]['address'] = "2 Main Street\nWells, ME";
        $location = get_related_post_locations( [ 'relationship' => 'all', 'order_by' => 'title', 'limit' => 1, 'map_field' => 'location' ] )[0];
        check( $location['address'] === '2 Main Street, Wells, ME' && $location['name'] === 'A Location', 'Address field wins for display; empty Name Field uses title' );
        $html = render_map( [ 'data_source' => 'posts', 'posts' => [ 'relationship' => 'all', 'order_by' => 'title', 'limit' => 1, 'map_field' => 'location' ], 'locations' => [ [ 'name' => 'Old repeater' ] ], 'map_field_type' => 'post', 'post_field' => 'post_id', 'post_map_field' => 'address' ] );
        check( strpos( $html, 'data-post-id="103"' ) !== false && strpos( $html, 'data-coordinates="42,-70"' ) !== false && strpos( $html, 'Old repeater' ) === false, 'SSR emits post-owned coordinates and isolates stale legacy settings' );
        $html = render_map( [ 'data_source' => 'posts', 'locations' => [ [ 'name' => 'Old repeater' ] ] ] );
        check( strpos( $html, 'class="location"' ) === false, 'Empty related source never falls back to the repeater' );
        $html = render_map( [ 'data_source' => 'builder', 'locations' => [ [ 'name' => 'Builder "Name"', 'address' => 'Old Address', 'coordinates' => '43,-70' ] ] ] );
        check( strpos( $html, 'data-name="Builder &quot;Name&quot;"' ) !== false && strpos( $html, 'data-coordinates="43,-70"' ) !== false && strpos( $html, 'data-post-id' ) === false && strpos( $html, 'Post ID:' ) === false, 'Builder data contract and escaping retained; debug removed' );
        $html = render_map( [ 'data_source' => 'acf', 'name_field' => 'label', 'map_field_type' => 'acf_map', 'map_field' => 'map', 'locations_acf' => [ [ 'label' => 'ACF location', 'map' => [ 'lat' => 41, 'lng' => -71, 'address' => 'ACF Address' ] ] ] ] );
        check( strpos( $html, 'data-name="ACF location"' ) !== false && strpos( $html, 'data-coordinates="41,-71"' ) !== false && strpos( $html, 'data-address="ACF Address"' ) !== false, 'ACF repeater contract retained' );

        $acf_key = 'acf-setting';
        check( get_server_api_key() === 'acf-setting', 'ACF setting fallback' );
        $filters['acf/fields/google_map/api'] = function ( $api ) { $api['key'] = 'acf-filter'; return $api; };
        check( get_server_api_key() === 'acf-filter', 'ACF API filter applies' );
        $breakdance_key = 'breakdance-setting';
        check( get_server_api_key() === 'breakdance-setting', 'Breakdance key precedes ACF' );
        define( 'BRIC_GOOGLE_MAPS_SERVER_API_KEY', 'test-server-key' );
        check( get_server_api_key() === 'test-server-key', 'Server constant precedes Breakdance' );
        $filters['bric_google_maps_server_api_key'] = function () { return ' test-filter-key '; };
        check( get_server_api_key() === 'test-filter-key', 'Dedicated filter precedes constant' );
        $filters['bric_google_maps_geocode_limit'] = function () { return 0; };
        check( geocode_location_address( 102, $address ) === null && count( $http_calls ) === 0, 'Geocode limit can disable HTTP' );
        unset( $filters['bric_google_maps_geocode_limit'] );
        $http_response = [ 'code' => 200, 'body' => json_encode( [ 'status' => 'OK', 'results' => [ [ 'geometry' => [ 'location' => [ 'lat' => 43.5, 'lng' => -70.7 ] ] ] ] ] ) ];
        check( resolve_location_coordinates( 102, $address ) === [ 'lat' => 43.5, 'lng' => -70.7 ], 'Server geocoding follows missing map and stored coordinates' );
        check( $meta[102][ LOCATION_COORDS_POSTMETA_KEY ]['source'] === 'server' && $meta[102][ LOCATION_COORDS_POSTMETA_KEY ]['address_hash'] === md5( $address ), 'Server coordinates stored on location with hash/source' );
        check( $http_calls[0][1]['timeout'] === 4 && strpos( $http_calls[0][0], 'address=869+Bragdon+Road' ) !== false, 'Server request uses address and four-second timeout' );
        resolve_location_coordinates( 102, $address );
        check( count( $http_calls ) === 1, 'Stored server coordinates prevent another request' );
        foreach ( [ 'REQUEST_DENIED', 'ZERO_RESULTS', 'network', 'invalid_coordinates' ] as $offset => $failure ) {
            $id = 200 + $offset;
            $http_response = $failure === 'network' ? new WP_Error() : [ 'code' => 200, 'body' => json_encode( [ 'status' => $failure === 'invalid_coordinates' ? 'OK' : $failure, 'results' => [ [ 'geometry' => [ 'location' => [ 'lat' => 999, 'lng' => 0 ] ] ] ] ] ) ];
            check( geocode_location_address( $id, $address ) === null, $failure . ' is handled' );
            $request_count = count( $http_calls );
            check( geocode_location_address( $id, $address ) === null && count( $http_calls ) === $request_count, $failure . ' is negatively cached' );
            check( $transients[ location_geocode_failure_key( $id, $address ) ]['ttl'] === 43200, 'Negative marker lasts twelve hours' );
        }
        check( count( $http_calls ) === 5, 'Five requests across all locations in one render' );
        check( geocode_location_address( 300, $address ) === null && count( $http_calls ) === 5 && get_transient( location_geocode_failure_key( 300, $address ) ) === false, 'Budget defers more locations without a negative marker' );

        $input = [ 'nonce' => 'test-nonce', 'post_id' => 101, 'address' => $address, 'address_hash' => md5( $address ), 'lat' => '44', 'lng' => '-71' ];
        set_transient( location_geocode_failure_key( 101, $address ), 'request_denied', 43200 );
        check( ajax_result( $input )->success && $meta[101][ LOCATION_COORDS_POSTMETA_KEY ]['source'] === 'builder', 'Authorized builder fallback stores coordinates on location' );
        check( get_transient( location_geocode_failure_key( 101, $address ) ) === false, 'Builder success clears negative marker' );
        $invalid = $input; $invalid['nonce'] = 'invalid';
        check( ajax_result( $invalid )->getCode() === 403, 'Invalid nonce rejected' );
        $can_edit = false;
        check( ajax_result( $input )->getCode() === 403, 'Editing the individual location is required' );
        $can_edit = true; $invalid = $input; $invalid['lat'] = '91';
        check( ajax_result( $invalid )->getCode() === 400, 'Out-of-range builder coordinates rejected' );
        $invalid = $input; $invalid['address_hash'] = md5( 'old address' );
        check( ajax_result( $invalid )->getCode() === 400, 'Mismatched rendered address hash rejected' );
        echo 'OK: ' . $assertions . " assertions passed.\n";
    } catch ( \Throwable $error ) {
        fwrite( STDERR, 'FAIL: ' . $error->getMessage() . "\n" );
        exit( 1 );
    }
}
