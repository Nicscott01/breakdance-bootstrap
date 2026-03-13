<?php

$to_assoc = static function ( $value ) use ( &$to_assoc ) {
    if ( is_array( $value ) ) {
        $normalized = [];
        foreach ( $value as $key => $item ) {
            $normalized[ $key ] = $to_assoc( $item );
        }
        return $normalized;
    }

    if ( is_object( $value ) ) {
        return $to_assoc( get_object_vars( $value ) );
    }

    return $value;
};

$data = $to_assoc( $propertiesData['content']['data'] ?? [] );
if ( ! is_array( $data ) ) {
    $data = [];
}

$data_source = isset( $data['data_source'] ) && is_string( $data['data_source'] )
    ? $data['data_source']
    : '';

$name_field = '';
if ( isset( $data['name_field'] ) && is_string( $data['name_field'] ) ) {
    $name_field = $data['name_field'];
} elseif ( isset( $data['field_for_name'] ) && is_string( $data['field_for_name'] ) ) {
    $name_field = $data['field_for_name'];
}

$map_field_type = isset( $data['map_field_type'] ) && is_string( $data['map_field_type'] )
    ? $data['map_field_type']
    : '';
$map_field = isset( $data['map_field'] ) && is_string( $data['map_field'] )
    ? $data['map_field']
    : '';
$post_field = isset( $data['post_field'] ) && is_string( $data['post_field'] )
    ? $data['post_field']
    : '';
$post_map_field = isset( $data['post_map_field'] ) && is_string( $data['post_map_field'] )
    ? $data['post_map_field']
    : '';
$field_for_nested_location = isset( $data['field_for_nested_location'] ) && is_string( $data['field_for_nested_location'] )
    ? $data['field_for_nested_location']
    : '';

$locations_dynamic_meta = $to_assoc( $data['locations_dynamic_meta'] ?? [] );
$locations_field_slug = '';
if (
    is_array( $locations_dynamic_meta ) &&
    isset( $locations_dynamic_meta['field']['slug'] ) &&
    is_string( $locations_dynamic_meta['field']['slug'] )
) {
    $locations_field_slug = $locations_dynamic_meta['field']['slug'];
}

$locations_field_name = '';
if ( $locations_field_slug !== '' ) {
    if ( strpos( $locations_field_slug, 'acf_repeater_' ) === 0 ) {
        $locations_field_name = substr( $locations_field_slug, strlen( 'acf_repeater_' ) );
    } elseif ( strpos( $locations_field_slug, 'acf_field_' ) === 0 ) {
        $locations_field_name = substr( $locations_field_slug, strlen( 'acf_field_' ) );
    } elseif ( preg_match( '/(field_[a-zA-Z0-9_]+)/', $locations_field_slug, $matches ) === 1 ) {
        $locations_field_name = $matches[1];
    }
}

$parse_map_value = static function ( $value ) use ( $to_assoc ) {
    $value = $to_assoc( $value );

    if ( is_array( $value ) ) {
        return $value;
    }

    if ( is_string( $value ) && $value !== '' ) {
        $decoded = json_decode( $value, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            return $decoded;
        }
    }

    return [];
};

$extract_post_id = static function ( $value ) use ( $to_assoc ) {
    $value = $to_assoc( $value );

    if ( is_numeric( $value ) ) {
        return (int) $value;
    }

    if ( is_array( $value ) && isset( $value['ID'] ) && is_numeric( $value['ID'] ) ) {
        return (int) $value['ID'];
    }

    if ( is_array( $value ) ) {
        $first = reset( $value );

        if ( is_numeric( $first ) ) {
            return (int) $first;
        }

        if ( is_array( $first ) && isset( $first['ID'] ) && is_numeric( $first['ID'] ) ) {
            return (int) $first['ID'];
        }
    }

    return 0;
};

$extract_coordinates_string = static function ( $value ) use ( $to_assoc ) {
    $value = $to_assoc( $value );

    if ( is_array( $value ) && isset( $value['lat'], $value['lng'] ) ) {
        $lat = filter_var( $value['lat'], FILTER_VALIDATE_FLOAT );
        $lng = filter_var( $value['lng'], FILTER_VALIDATE_FLOAT );

        if ( $lat !== false && $lng !== false ) {
            return (string) $lat . ',' . (string) $lng;
        }

        return '';
    }

    if ( is_string( $value ) ) {
        $trimmed = trim( $value );
        if ( $trimmed === '' ) {
            return '';
        }

        $parts = array_map( 'trim', explode( ',', $trimmed ) );
        if ( count( $parts ) >= 2 ) {
            $lat = filter_var( $parts[0], FILTER_VALIDATE_FLOAT );
            $lng = filter_var( $parts[1], FILTER_VALIDATE_FLOAT );

            if ( $lat !== false && $lng !== false ) {
                return (string) $lat . ',' . (string) $lng;
            }
        }

        return $trimmed;
    }

    return '';
};

$locations = [];
$builder_locations = $to_assoc( $data['locations'] ?? [] );
$acf_locations = $to_assoc( $data['locations_acf'] ?? [] );

if ( $data_source === 'builder' && is_array( $builder_locations ) ) {
    $locations = $builder_locations;
}

if ( empty( $locations ) && $data_source === 'acf' && is_array( $acf_locations ) ) {
    $locations = $acf_locations;
}

if ( empty( $locations ) && is_array( $builder_locations ) && ! empty( $builder_locations ) ) {
    $locations = $builder_locations;
}

if ( empty( $locations ) && is_array( $acf_locations ) && ! empty( $acf_locations ) ) {
    $locations = $acf_locations;
}

if ( empty( $locations ) && $locations_field_name !== '' && function_exists( 'get_field' ) ) {
    $loaded_locations = $to_assoc( get_field( $locations_field_name ) );
    if ( is_array( $loaded_locations ) ) {
        $locations = $loaded_locations;
    }
}

$location_field_config = $to_assoc( $data['field_for_location'] ?? '' );
if ( is_string( $location_field_config ) && $location_field_config !== '' ) {
    $decoded_location_field_config = json_decode( $location_field_config, true );
    if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_location_field_config ) ) {
        $location_field_config = $decoded_location_field_config;
    }
}

$location_field_name = '';
$location_field_is_post = false;
if ( is_array( $location_field_config ) ) {
    if ( isset( $location_field_config['name'] ) && is_string( $location_field_config['name'] ) ) {
        $location_field_name = $location_field_config['name'];
    }
    $location_field_is_post = ! empty( $location_field_config['is_post'] );
} elseif ( is_string( $location_field_config ) ) {
    $location_field_name = $location_field_config;
}

$custom_icon_svg = '';
$custom_icon = $to_assoc( $data['custom_icon'] ?? [] );
if ( is_array( $custom_icon ) && isset( $custom_icon['svgCode'] ) && is_string( $custom_icon['svgCode'] ) ) {
    $custom_icon_svg = $custom_icon['svgCode'];
}

$current_post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;
$geocode_cache = [];
$cache_loader = '\\BricBreakdance\\GoogleMapsLocations\\get_post_geocode_cache';
if ( $current_post_id > 0 && function_exists( $cache_loader ) ) {
    $loaded_geocode_cache = $to_assoc( $cache_loader( $current_post_id ) );
    if ( is_array( $loaded_geocode_cache ) ) {
        $geocode_cache = $loaded_geocode_cache;
    }
}

$normalize_geocode_address = static function ( $address ) {
    $normalize_fn = '\\BricBreakdance\\GoogleMapsLocations\\normalize_geocode_address';
    if ( function_exists( $normalize_fn ) ) {
        return (string) $normalize_fn( $address );
    }

    $normalized = trim( (string) $address );
    if ( $normalized === '' ) {
        return '';
    }

    $normalized = preg_replace( '/\\s+/', ' ', $normalized );

    if ( function_exists( 'mb_strtolower' ) ) {
        return mb_strtolower( $normalized, 'UTF-8' );
    }

    return strtolower( $normalized );
};

$sanitize_svg_markup = static function ( $svg_markup ) {
    if ( ! is_string( $svg_markup ) || trim( $svg_markup ) === '' ) {
        return '';
    }

    $allowed_svg_tags = [
        'svg' => [
            'xmlns' => true,
            'viewbox' => true,
            'viewBox' => true,
            'width' => true,
            'height' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'role' => true,
            'aria-hidden' => true,
            'focusable' => true,
        ],
        'g' => [
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'transform' => true,
            'class' => true,
            'id' => true,
        ],
        'path' => [
            'd' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'transform' => true,
            'class' => true,
            'id' => true,
        ],
        'circle' => [
            'cx' => true,
            'cy' => true,
            'r' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
        ],
        'rect' => [
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'rx' => true,
            'ry' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
        ],
        'line' => [
            'x1' => true,
            'y1' => true,
            'x2' => true,
            'y2' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
        ],
        'polyline' => [
            'points' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
        ],
        'polygon' => [
            'points' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
        ],
        'defs' => [],
        'clipPath' => [
            'id' => true,
        ],
        'clippath' => [
            'id' => true,
        ],
        'title' => [],
        'desc' => [],
    ];

    return wp_kses( $svg_markup, $allowed_svg_tags );
};
?>
<div class="map-embed">
  <div class="google-map"></div>
</div>
<div id="locations-%%ID%%" class="data-locations locations-icons">
  <p>Post ID: %%POSTID%% </p>
  <?php foreach ( $locations as $index => $location_row ) : ?>
    <?php
    $location = $to_assoc( $location_row );
    if ( ! is_array( $location ) ) {
        continue;
    }

    $name = '';
    if ( $name_field !== '' && isset( $location[ $name_field ] ) ) {
        $name = (string) $location[ $name_field ];
    } elseif ( isset( $location['name'] ) ) {
        $name = (string) $location['name'];
    }

    $map = [];

    if ( $map_field_type === 'post' && $post_field !== '' && $post_map_field !== '' && isset( $location[ $post_field ] ) && function_exists( 'get_field' ) ) {
        $related_post_id = $extract_post_id( $location[ $post_field ] );
        if ( $related_post_id > 0 ) {
            $map = $parse_map_value( get_field( $post_map_field, $related_post_id ) );
        }
    } elseif ( $map_field_type === 'acf_map' && $map_field !== '' && isset( $location[ $map_field ] ) ) {
        $map = $parse_map_value( $location[ $map_field ] );
    }

    if ( empty( $map ) && $location_field_name !== '' && isset( $location[ $location_field_name ] ) ) {
        $location_field_value = $location[ $location_field_name ];

        if ( $location_field_is_post && $field_for_nested_location !== '' && function_exists( 'get_field' ) ) {
            $related_post_id = $extract_post_id( $location_field_value );
            if ( $related_post_id > 0 ) {
                $map = $parse_map_value( get_field( $field_for_nested_location, $related_post_id ) );
            }
        } else {
            $map = $parse_map_value( $location_field_value );
        }
    }

    $address = '';
    if ( isset( $map['address'] ) ) {
        $address = (string) $map['address'];
    } elseif ( isset( $location['address'] ) ) {
        $address = (string) $location['address'];
    }

    $coordinates = '';
    if ( isset( $map['lat'], $map['lng'] ) ) {
        $coordinates = $extract_coordinates_string(
            [
                'lat' => $map['lat'],
                'lng' => $map['lng'],
            ]
        );
    } elseif ( isset( $location['coordinates'] ) ) {
        $coordinates = $extract_coordinates_string( $location['coordinates'] );
    }

    if ( $coordinates === '' && $address !== '' && ! empty( $geocode_cache ) ) {
        $normalized_address = $normalize_geocode_address( $address );
        $cached_coordinates = $normalized_address !== '' ? ( $geocode_cache[ $normalized_address ] ?? null ) : null;

        if ( is_array( $cached_coordinates ) ) {
            $coordinates = $extract_coordinates_string(
                [
                    'lat' => $cached_coordinates['lat'] ?? null,
                    'lng' => $cached_coordinates['lng'] ?? null,
                ]
            );
        }
    }

    $icon_color = isset( $location['icon_color'] ) ? (string) $location['icon_color'] : '';
    $icon_size = '';
    if ( isset( $location['icon_size'] ) && is_array( $location['icon_size'] ) && isset( $location['icon_size']['number'] ) ) {
        $icon_size = (string) $location['icon_size']['number'];
    } elseif ( isset( $location['icon_size'] ) ) {
        $icon_size = (string) $location['icon_size'];
    }

    $icon_svg = '';
    $icon_value = $to_assoc( $location['icon'] ?? null );
    if ( is_array( $icon_value ) && isset( $icon_value['svgCode'] ) && is_string( $icon_value['svgCode'] ) ) {
        $icon_svg = $icon_value['svgCode'];
    } elseif ( is_string( $icon_value ) ) {
        $icon_svg = $icon_value;
    }
    ?>
    <div
      id="icon-<?php echo esc_attr( (string) $index ); ?>"
      class="location"
      data-name="<?php echo esc_attr( $name ); ?>"
      data-address="<?php echo esc_attr( $address ); ?>"
      data-coordinates="<?php echo esc_attr( $coordinates ); ?>"
      data-icon-color="<?php echo esc_attr( $icon_color ); ?>"
      data-icon-size="<?php echo esc_attr( $icon_size ); ?>"
    ><?php echo $sanitize_svg_markup( $icon_svg ); ?></div>
  <?php endforeach; ?>
  <div class="custom-global-icon"><?php echo $sanitize_svg_markup( $custom_icon_svg ); ?></div>
</div>
