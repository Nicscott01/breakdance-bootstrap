<?php  

$locations_field = $propertiesData['content']['data']['locations'] ?? [];

$name_field = $propertiesData['content']['data']['field_for_name'] ?? '';
$location_field = $propertiesData['content']['data']['field_for_location'] ?? '';
$field_for_nested_location = $propertiesData['content']['data']['field_for_nested_location'] ?? '';

if ( strpos( $location_field, 'is_post__posttypes__' ) === 0 ) {
    $post_types = str_replace( 'is_post__posttypes__', '', $location_field );
}


// Use preg_match to extract from "field_" onward
if ( preg_match( '/(field_[a-zA-Z0-9_]+)/', $locations_field, $matches ) ) {
    $field_key = $matches[1];
}

$locations = get_field( $field_key );

echo $location_field;
//Try to json decode the location field
if ( is_string( $location_field ) ) {
    $location_field = json_decode( $location_field, true );
}
if ( ! is_array( $location_field ) ) {
    $location_field = [];
}
var_dump( $location_field );

echo $name_field;

echo '<pre>';
print_r( $locations );
echo '</pre>';
?>
<p>V2 ssr</p>
<div class="map-embed">
<div class="google-map"></div>
</div>
<div id="locations-%%ID%%" class="data-locations locations-icons">
  <p>Post ID: %%POSTID%% </p>  
  
<?php foreach ( $locations as $location ) : 
    
    //Get the map data from the post
    $map = get_field( $field_for_nested_location, $location[$location_field['name']][0]->ID );
    

    
?>
  <div id="icon-{{key}}" class="location" data-name="<?php echo $location[$name_field]; ?>" data-address="<?php echo $map['address']; ?>" data-coordinates="<?php echo $map['lat'] . ',' . $map['lng']; ?>" data-icon-color="{{ location.icon_color }}" data-icon-size="{{ location.icon_size.number }}">{{location.icon.svgCode | raw}}</div>
{% endfor %}
  <div class="custom-global-icon">{{ content.data.custom_icon.svgCode | raw }}</div>
  <?php endforeach; ?>
</div>