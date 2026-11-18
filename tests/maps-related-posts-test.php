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
    use function BricBreakdance\GoogleMapsLocations\resolve_anchor_post_id;
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
    $singular = true; $main_in_loop = false;
    $can_edit = true;
    $breakdance_key = $acf_key = '';
    $url_ids = [];
    $assertions = 0;
    $query_count = 0;

    set_error_handler( function ( $severity, $message, $file, $line ) {
        if ( error_reporting() & $severity ) {
            throw new \ErrorException( $message, 0, $severity, $file, $line );
        }
        return false;
    } );

    class WP_Post {
        public $ID, $post_type, $post_status, $post_title, $menu_order, $post_date;
        function __construct( $id, $title, $type = 'location', $status = 'publish', $menu_order = 0, $date = '2026-01-01' ) {
            $this->ID = $id;
            $this->post_title = $title;
            $this->post_type = $type;
            $this->post_status = $status;
            $this->menu_order = $menu_order;
            $this->post_date = $date;
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
            $GLOBALS['query_count']++;
            $GLOBALS['last_query'] = $args;
            $posts = array_filter( $GLOBALS['posts'], function ( $post ) use ( $args ) {
                if ( $post->post_type !== $args['post_type'] || $post->post_status !== $args['post_status'] ) { return false; }
                if ( isset( $args['post__in'] ) && ! in_array( $post->ID, $args['post__in'], true ) ) { return false; }
                if ( isset( $args['meta_query'] ) ) {
                    $matches = [];
                    foreach ( $args['meta_query'] as $clause ) {
                        if ( ! is_array( $clause ) ) { continue; }
                        if ( $clause['compare'] === 'EXISTS' ) {
                            $matches[] = array_key_exists( $clause['key'], $GLOBALS['meta'][ $post->ID ] ?? [] );
                            continue;
                        }
                        $raw = $GLOBALS['meta'][ $post->ID ][ $clause['key'] ] ?? '';
                        $raw = is_array( $raw ) ? serialize( $raw ) : (string) $raw;
                        $matches[] = $clause['compare'] === 'LIKE' ? strpos( $raw, $clause['value'] ) !== false : $raw === $clause['value'];
                    }
                    if ( ( $args['meta_query']['relation'] ?? 'AND' ) === 'OR' ? ! in_array( true, $matches, true ) : in_array( false, $matches, true ) ) { return false; }
                }
                return true;
            } );
            usort( $posts, function ( $a, $b ) use ( $args ) {
                if ( $args['orderby'] === 'post__in' ) {
                    // WP ignores DESC for post__in, so the production helper must reverse IDs.
                    return array_search( $a->ID, $args['post__in'], true ) <=> array_search( $b->ID, $args['post__in'], true );
                }
                if ( in_array( $args['orderby'], [ 'meta_value', 'meta_value_num' ], true ) ) {
                    $field = $args['meta_query'][0]['key'];
                    $a_value = (string) get_post_meta( $a->ID, $field, true );
                    $b_value = (string) get_post_meta( $b->ID, $field, true );
                    $comparison = $args['orderby'] === 'meta_value_num' ? ( (float) $a_value <=> (float) $b_value ) : strcmp( $a_value, $b_value );
                    return $comparison * ( $args['order'] === 'DESC' ? -1 : 1 );
                }
                $key = [ 'title' => 'post_title', 'date' => 'post_date', 'menu_order' => 'menu_order' ][ $args['orderby'] ];
                return ( $a->$key <=> $b->$key ) * ( $args['order'] === 'DESC' ? -1 : 1 );
            } );
            $this->posts = array_slice( $posts, 0, $args['posts_per_page'] );
            if ( ( $args['fields'] ?? '' ) === 'ids' ) {
                $this->posts = array_map( function ( $post ) { return $post->ID; }, $this->posts );
            }
        }
    }
    function add_action( $hook, $callback ) {}
    function apply_filters( $hook, $value, ...$args ) { return isset( $GLOBALS['filters'][ $hook ] ) ? $GLOBALS['filters'][ $hook ]( $value, ...$args ) : $value; }
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
            'breakdance_block' => (object) [ 'public' => false, 'show_ui' => true ],
        ];
        return $types[ $type ] ?? null;
    }
    function get_the_title( $id ) { return $GLOBALS['posts'][ $id ]->post_title; }
    function get_the_ID() { return $GLOBALS['post']->ID ?? 0; }
    function get_queried_object() { return $GLOBALS['queried_post']; }
    function is_singular() { return $GLOBALS['singular']; }
    function in_the_loop() { return $GLOBALS['main_in_loop']; }
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
            12 => new WP_Post( 12, '2026', 'trents_tree', 'publish', 0, '2024-01-01' ),
            3973 => new WP_Post( 3973, '2025', 'trents_tree', 'publish', 0, '2026-11-01' ),
            3974 => new WP_Post( 3974, 'Draft year', 'trents_tree', 'draft', 0, '2030-01-01' ),
            3975 => new WP_Post( 3975, 'Scheduled year', 'trents_tree', 'future', 0, '2030-01-01' ),
            3976 => new WP_Post( 3976, 'Private year', 'trents_tree', 'private', 0, '2030-01-01' ),
            3977 => new WP_Post( 3977, 'Year without custom fields', 'trents_tree', 'publish', 0, '2026-11-05' ),
            3978 => new WP_Post( 3978, 'Internal post', 'internal_type' ),
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
        check( query_related_posts( [ 'post_type' => 'breakdance_block', 'relationship' => 'all' ], 0 ) === [], 'Breakdance documents are rejected even when show_ui is true' );
        check( query_related_posts( [ 'post_type' => 'internal_type', 'relationship' => 'all' ], 0 ) === [], 'Post types without public or show_ui are rejected' );
        check( ! in_array( true, array_column( $field_reads, 2 ), true ), 'Related ACF fields are always read unformatted' );

        $post = $posts[2465]; $queried_post = $posts[12];
        check( get_context_post_id() === 12, 'Global Block document does not replace viewed year' );
        check( resolve_anchor_post_id( [] ) === 12, 'Missing Anchor defaults to the existing current context' );
        $before_queries = $query_count;
        check( resolve_anchor_post_id( [ 'anchor' => 'current', 'anchor_post_id' => 3973, 'anchor_post_type' => 'trents_tree' ] ) === 12 && $query_count === $before_queries, 'Explicit current ignores stale specific/latest settings without querying' );
        $post = $posts[3973];
        check( get_context_post_id() === 3973, 'Current loop/preview post follows Breakdance dynamic data' );
        $singular = false; $queried_post = (object) [ 'name' => 'trents_tree' ];
        check( resolve_anchor_post_id( [] ) === 3973, 'Non-singular current retains documented outside-loop archive caveat' );
        $post = $posts[12];
        check( resolve_anchor_post_id( [] ) === 12, 'Breakdance secondary-loop post resolves with the main loop flag false' );
        $main_in_loop = true;
        check( resolve_anchor_post_id( [] ) === 12, 'Main-loop post still resolves on a non-singular request' );
        $singular = true; $main_in_loop = false;
        $post = $posts[2465]; $queried_post = null;
        check( get_context_post_id() === 0, 'No content context never uses a document ID' );
        $doing_ajax = $builder_ssr = true;
        $_POST = [ 'triggeringDocument' => 2465 ];
        $meta[2465]['_breakdance_template_last_previewed_item'] = [ 'url' => 'https://example.test/2025/' ];
        $url_ids['https://example.test/2025/'] = 3973;
        check( get_context_post_id() === 3973, 'Admin-ajax SSR can use saved preview' );
        check( resolve_anchor_post_id( [] ) === 3973, 'Default current retains admin-ajax preview fallback' );
        $post = $posts[12];
        check( get_context_post_id() === 12, 'Live preview wins over saved preview' );
        $_POST['breakdance_ajax_at_any_url'] = true; $queried_post = $posts[12];
        check( resolve_anchor_post_id( [] ) === 12, 'SSR at the singular preview URL keeps the live post instead of the stale saved preview' );
        $queried_post = null;
        $post = $posts[2465]; $_POST['breakdance_ajax_at_any_url'] = true;
        check( get_context_post_id() === 0, 'Any-URL request with no content context never uses stale saved preview' );
        $doing_ajax = $builder_ssr = false; $_POST = [];
        $filters['bric_google_maps_locations_context_post_id'] = function () { return 12; };
        check( get_context_post_id() === 12, 'Context filter can override an unresolved anchor' );
        check( resolve_anchor_post_id( [] ) === 12, 'Current anchor delegates to the existing context filter' );
        unset( $filters['bric_google_maps_locations_context_post_id'] );

        $meta[12]['start_date'] = '20261101'; $meta[3973]['start_date'] = '20251101';
        $meta[12]['rank'] = '9'; $meta[3973]['rank'] = '10';
        $meta[12]['starts_at'] = '2026-01-01 23:59:59'; $meta[3973]['starts_at'] = '2026-12-01 00:00:00';
        foreach ( [ 3974, 3975, 3976 ] as $id ) {
            $meta[$id]['start_date'] = '20301101'; $meta[$id]['rank'] = '99'; $meta[$id]['starts_at'] = '2030-01-01 00:00:00';
        }
        $latest = [ 'anchor' => 'latest', 'anchor_post_type' => 'trents_tree' ];
        $before_queries = $query_count; $before_post = $post;
        check( resolve_anchor_post_id( $latest ) === 3977, 'Latest defaults to publish date DESC and excludes draft, scheduled and private years' );
        check( resolve_anchor_post_id( $latest ) === 3977 && $query_count === $before_queries + 1, 'Two latest lookups run one query per request' );
        check( resolve_anchor_post_id( $latest + [ 'anchor_newest_by' => 'date', 'anchor_field_name' => 'ignored', 'anchor_field_type' => 'text' ] ) === 3977 && $query_count === $before_queries + 1, 'Date cache ignores hidden custom-field settings' );
        check( $last_query['post_status'] === 'publish' && $last_query['posts_per_page'] === 1 && $last_query['no_found_rows'] && $last_query['fields'] === 'ids' && $last_query['ignore_sticky_posts'] && ! $last_query['suppress_filters'] && $last_query['orderby'] === 'date' && $last_query['order'] === 'DESC', 'Latest query is bounded, published-only, ID-only, filterable and descending' );
        check( $post === $before_post, 'Latest lookup never changes the global post' );
        $latest_meta = $latest + [ 'anchor_newest_by' => 'meta', 'anchor_field_name' => 'start_date' ];
        check( resolve_anchor_post_id( $latest_meta ) === 12, 'Latest numeric ACF date selects the greatest stored Ymd, excluding missing fields and non-published years' );
        check( $last_query['orderby'] === 'meta_value_num' && $last_query['meta_query'] === [ [ 'key' => 'start_date', 'compare' => 'EXISTS' ] ] && $last_query['order'] === 'DESC', 'Custom-field query requires EXISTS and defaults to numeric DESC' );
        $before_queries = $query_count;
        check( resolve_anchor_post_id( $latest_meta + [ 'anchor_field_type' => 'number' ] ) === 12 && $query_count === $before_queries, 'Default and explicit numeric field types share a cached lookup' );
        $rank = array_merge( $latest_meta, [ 'anchor_field_name' => 'rank' ] );
        check( resolve_anchor_post_id( $rank ) === 3973 && $query_count === $before_queries + 1, 'A different numeric field has its own cache entry and sorts 10 above 9' );
        check( resolve_anchor_post_id( $rank + [ 'anchor_field_type' => 'text' ] ) === 12 && $last_query['orderby'] === 'meta_value' && $query_count === $before_queries + 2, 'Text uses a distinct cache entry and sorts 9 above 10 as strings' );
        check( resolve_anchor_post_id( array_merge( $latest_meta, [ 'anchor_field_name' => 'starts_at', 'anchor_field_type' => 'text' ] ) ) === 3973, 'Text ordering supports ACF date-time strings and skips missing/unpublished values' );
        $missing_field = array_merge( $latest_meta, [ 'anchor_field_name' => 'missing_anchor_field' ] );
        $before_queries = $query_count;
        check( resolve_anchor_post_id( $missing_field ) === 0 && resolve_anchor_post_id( $missing_field ) === 0 && $query_count === $before_queries + 1, 'No posts with the field means no anchor; empty results are cached too' );
        $before_queries = $query_count;
        check( resolve_anchor_post_id( [ 'anchor' => 'latest', 'anchor_post_type' => 'location' ] ) > 0 && $query_count === $before_queries + 1, 'Different allowed anchor types have separate cache entries, including show_ui-only CPTs' );
        $before_queries = $query_count;
        foreach ( [ '', 'internal_type', 'breakdance_block', 'unknown_type' ] as $type ) {
            check( resolve_anchor_post_id( [ 'anchor' => 'latest', 'anchor_post_type' => $type ] ) === 0, 'Invalid/disallowed latest type fails closed: ' . $type );
        }
        check( resolve_anchor_post_id( [ 'anchor' => 'latest' ] ) === 0 && resolve_anchor_post_id( array_merge( $latest_meta, [ 'anchor_field_name' => '  ' ] ) ) === 0 && $query_count === $before_queries, 'Missing latest type or custom-field name never runs a query' );
        $filters['bric_google_maps_related_post_type_allowed'] = function ( $allowed, $type ) { return $type !== 'trents_tree'; };
        check( resolve_anchor_post_id( $latest ) === 0, 'Allowed-type filter is checked even before a cached latest result is used' );
        check( resolve_anchor_post_id( [ 'anchor' => 'specific', 'anchor_post_id' => 12 ] ) === 0, 'Allowed-type filter also blocks specific anchors' );
        unset( $filters['bric_google_maps_related_post_type_allowed'] );

        check( resolve_anchor_post_id( [ 'anchor' => 'specific', 'anchor_post_id' => 3973 ] ) === 3973, 'Specific published integer ID resolves' );
        check( resolve_anchor_post_id( [ 'anchor' => 'specific', 'anchor_post_id' => ' 12 ' ] ) === 12, 'Specific ID accepts a trimmed resolved dynamic string' );
        check( resolve_anchor_post_id( [ 'anchor' => 'specific', 'anchor_post_id' => 101 ] ) === 101, 'Specific allows a published show_ui-only post type' );
        foreach ( [ '', 0, -12, '-12', '12 garbage', '12.5', 12.5, true, [], (object) [ 'ID' => 12 ], '9999999999999999999999999', 9999 ] as $invalid_id ) {
            check( resolve_anchor_post_id( [ 'anchor' => 'specific', 'anchor_post_id' => $invalid_id ] ) === 0, 'Malformed, nonpositive, overflowing or nonexistent specific IDs fail closed' );
        }
        foreach ( [ 3974, 3975, 3976, 3978, 2465 ] as $invalid_id ) {
            check( resolve_anchor_post_id( [ 'anchor' => 'specific', 'anchor_post_id' => $invalid_id ] ) === 0, 'Specific rejects unpublished/disallowed/document posts: ' . $invalid_id );
        }
        check( resolve_anchor_post_id( [ 'anchor' => 'specific' ] ) === 0 && resolve_anchor_post_id( [ 'anchor' => 'unknown' ] ) === 0, 'Missing specific ID and unknown anchor mode never fall back to current' );

        $post = $queried_post = $posts[12];
        $anchor_filter_calls = [];
        $filters['bric_google_maps_locations_anchor_post_id'] = function ( $id, $settings ) use ( &$anchor_filter_calls ) {
            $anchor_filter_calls[] = [ $id, $settings ];
            return 101;
        };
        foreach ( [ [ [], 12 ], [ $latest, 3977 ], [ [ 'anchor' => 'specific', 'anchor_post_id' => 12 ], 12 ], [ [ 'anchor' => 'specific', 'anchor_post_id' => 0 ], 0 ] ] as $case ) {
            check( resolve_anchor_post_id( $case[0] ) === 101 && end( $anchor_filter_calls ) === [ $case[1], $case[0] ], 'Anchor filter receives the final ID and settings in each mode, including unresolved/cached anchors' );
        }
        $before_queries = $query_count;
        check( resolve_anchor_post_id( $latest ) === 101 && count( $anchor_filter_calls ) === 5 && $query_count === $before_queries, 'Anchor filter runs again for each cached resolution without querying' );
        unset( $filters['bric_google_maps_locations_anchor_post_id'] );
        check( resolve_anchor_post_id( $latest ) === 3977, 'Filtered anchor IDs never pollute the latest cache' );

        $meta[103]['trents_tree'] = [ '123', '3973', '3977' ];
        $related = [ 'post_type' => 'location', 'relationship_field' => 'trents_tree', 'order_by' => 'title' ];
        check( array_column( get_related_post_locations( $related ), 'post_id' ) === [ 101, 102 ], 'Existing related locations use current when Anchor is missing' );
        check( array_column( get_related_post_locations( $related + $latest ), 'post_id' ) === [ 103 ], 'Related locations use the newest published anchor instead of current' );
        check( array_column( get_related_post_locations( $related + $latest_meta ), 'post_id' ) === [ 101, 102 ], 'Related locations use the custom-field anchor' );
        check( array_column( get_related_post_locations( $related + [ 'anchor' => 'specific', 'anchor_post_id' => 3973 ] ), 'post_id' ) === [ 103 ], 'Related locations use the specific anchor instead of current' );
        $meta[3973]['places'] = [ '102', '101' ];
        check( array_column( get_related_post_locations( [ 'relationship' => 'listed_on_current', 'relationship_field' => 'places', 'anchor' => 'specific', 'anchor_post_id' => 3973 ] ), 'post_id' ) === [ 102, 101 ], 'The anchor lists its locations retains relationship order with an explicit anchor' );
        check( get_related_post_locations( $related + [ 'anchor' => 'specific', 'anchor_post_id' => 0 ] ) === [], 'Invalid explicit anchor never falls back to current locations' );
        $before_queries = $query_count;
        check( count( get_related_post_locations( [ 'relationship' => 'all', 'anchor' => 'latest', 'anchor_post_type' => 'trents_tree', 'anchor_newest_by' => 'meta', 'anchor_field_name' => 'unused_hidden_field' ] ) ) === 4 && $query_count === $before_queries + 1, 'All published posts ignores hidden anchor settings and does no anchor query' );

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
