    
    
    function initMap( lat = 44.9778, lng = -93.2650, zoomLevel = 8 ) {
        // Map options
        var mapOptions = {
            zoom: zoomLevel,
            center: {lat: lat, lng: lng} // Example coordinates
        };

        // Create the map
        var map = new google.maps.Map(document.getElementById('map'), mapOptions);

        // Marker locations
        var locations = [
            {lat: 44.9778, lng: -93.2650},
            {lat: 45.0000, lng: -93.2500}
        ];

        // Loop through locations and place markers
        locations.forEach(function(location) {
            new google.maps.Marker({
                position: location,
                map: map
            });
        });
    }

    // Load the map
    google.maps.event.addDomListener(window, 'load', initMap(  ) );

