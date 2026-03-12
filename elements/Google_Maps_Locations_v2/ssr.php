<?php

$data = $propertiesData['content']['data'] ?? [];

$locations_dynamic_meta = $data['locations_dynamic_meta'] ?? [];
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

$locations = [];
if ( $locations_field_name !== '' && function_exists( 'get_field' ) ) {
    $loaded_locations = get_field( $locations_field_name );
    if ( is_array( $loaded_locations ) ) {
        $locations = $loaded_locations;
    }
}

$name_field = isset( $data['field_for_name'] ) && is_string( $data['field_for_name'] )
    ? $data['field_for_name']
    : '';
$field_for_nested_location = isset( $data['field_for_nested_location'] ) && is_string( $data['field_for_nested_location'] )
    ? $data['field_for_nested_location']
    : '';

$location_field_config = $data['field_for_location'] ?? '';
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

$extract_post_id = static function ( $value ) {
    if ( is_numeric( $value ) ) {
        return (int) $value;
    }

    if ( is_object( $value ) && isset( $value->ID ) && is_numeric( $value->ID ) ) {
        return (int) $value->ID;
    }

    if ( is_array( $value ) ) {
        $first = reset( $value );
        if ( is_numeric( $first ) ) {
            return (int) $first;
        }
        if ( is_object( $first ) && isset( $first->ID ) && is_numeric( $first->ID ) ) {
            return (int) $first->ID;
        }
        if ( is_array( $first ) && isset( $first['ID'] ) && is_numeric( $first['ID'] ) ) {
            return (int) $first['ID'];
        }
    }

    return 0;
};

$custom_icon_svg = '';
if (
    isset( $data['custom_icon'] ) &&
    is_array( $data['custom_icon'] ) &&
    isset( $data['custom_icon']['svgCode'] ) &&
    is_string( $data['custom_icon']['svgCode'] )
) {
    $custom_icon_svg = $data['custom_icon']['svgCode'];
}
?>
<div class="map-embed">
  <div class="google-map"></div>
</div>
<div id="locations-%%ID%%" class="data-locations locations-icons">
  <p>Post ID: %%POSTID%% </p>
  <?php foreach ( $locations as $index => $location ) : ?>
    <?php
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
    if ( $location_field_name !== '' && isset( $location[ $location_field_name ] ) ) {
        $location_field_value = $location[ $location_field_name ];

        if ( $location_field_is_post ) {
            $related_post_id = $extract_post_id( $location_field_value );
            if ( $related_post_id > 0 && $field_for_nested_location !== '' && function_exists( 'get_field' ) ) {
                $map_from_post = get_field( $field_for_nested_location, $related_post_id );
                if ( is_array( $map_from_post ) ) {
                    $map = $map_from_post;
                }
            }
        } elseif ( is_array( $location_field_value ) ) {
            $map = $location_field_value;
        } elseif ( is_string( $location_field_value ) && $location_field_value !== '' ) {
            $decoded_map = json_decode( $location_field_value, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_map ) ) {
                $map = $decoded_map;
            }
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
        $coordinates = (string) $map['lat'] . ',' . (string) $map['lng'];
    } elseif ( isset( $location['coordinates'] ) ) {
        $coordinates = (string) $location['coordinates'];
    }

    $icon_color = isset( $location['icon_color'] ) ? (string) $location['icon_color'] : '';
    $icon_size = '';
    if ( isset( $location['icon_size'] ) && is_array( $location['icon_size'] ) && isset( $location['icon_size']['number'] ) ) {
        $icon_size = (string) $location['icon_size']['number'];
    } elseif ( isset( $location['icon_size'] ) ) {
        $icon_size = (string) $location['icon_size'];
    }

    $icon_svg = '';
    if ( isset( $location['icon'] ) && is_array( $location['icon'] ) && isset( $location['icon']['svgCode'] ) ) {
        $icon_svg = (string) $location['icon']['svgCode'];
    } elseif ( isset( $location['icon'] ) && is_string( $location['icon'] ) ) {
        $icon_svg = $location['icon'];
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
    ><?php echo wp_kses_post( $icon_svg ); ?></div>
  <?php endforeach; ?>
  <div class="custom-global-icon"><?php echo wp_kses_post( $custom_icon_svg ); ?></div>
</div>
