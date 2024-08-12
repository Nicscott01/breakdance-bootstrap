   
//Define the variable
window.bricGoogleMaps = {};

class BricGoogleMaps {

    constructor( selector, locations, options ) {

        this.selector = selector;
        this.setMapOptions( options );
        this.setLocations( locations );
        this.initMap();

    }



    setLocations( locations ) {

        this.locations = locations ? locations : [];

        return this;

    }


    setMapOptions( options ) {

        let center = 0;    
        const zoomLevel = options.zoom ? Number( options.zoom.number ) : 8;
        const mapType = options.type ? options.type : 'roadmap';
    
    
        if ( options.center ) {
            center = options.center.split( ',' );
        } else {
            console.error('Center data is undefined');
            // Provide a default value if center is undefined
            center = '43.1873235,-70.6156473'.split(',');
        }
    
        // Map options
        this.mapOptions = {
            zoom: zoomLevel,
            mapId: '53f02eb8c6c3b17b', //todo: pass this through the element for distribution
            center: {lat: parseFloat( center[0] ), lng: parseFloat( center[1] ) }, 
            mapTypeId: mapType,
            streetViewControl: options.streetViewControl || false,
            mapTypeControl: options.mapTypeControl || false,
            scaleControl: options.scaleControl || false,
            rotateControl: options.rotateControl || false,
            zoomControl: options.zoomControl || false,
            fullscreenControl: options.fullscreenControl || false
        };

        return this;

    }


    updateOptions( options ) {

        this.setMapOptions( options );
        console.log( "update options:", this.mapOptions );
        console.log( 'this map to update:', this.map );

        if (this.map) {
            this.map.setOptions(this.mapOptions);
        } else {
            console.error("Map instance is not initialized.");
        }
    }


    /**
     * Plot the marker using AdvancedMarkerElement
     * and custom svg if provided
     * 
     * 
     * @param {object} map 
     * @param {object} position 
     * @param {string} title 
     * @param {string} svgIcon 
     */

    plotMarker(map, position, title, svgIcon) {
        new google.maps.marker.AdvancedMarkerElement({
            position: position,
            map: map,
            title: title,
            content: svgIcon // Apply the parsed SVG icon here
        });
    }
    

    initMap() {
    
        // Create the map
        const map = new google.maps.Map(document.querySelector( this.selector + ' .google-map'), this.mapOptions);

    
        if ( this.locations.length ) {
            // Loop through locations and place markers
            this.locations.forEach( location => {
                
                var position;
                var svgIcon = null;
    
    
                if ( this.mapOptions.customIcon ) {//location.icon && location.icon.svgCode ) {
    
                    try {
                        svgDoc = new DOMParser().parseFromString( GoogleMapsOptions.customIcon, "image/svg+xml" );
                        
                        if ( svgDoc.documentElement.nodeName == 'svg' ) {
                            svgIcon = svgDoc.documentElement;
                        } else {
                            console.error("SVG parsing failed:", svgDoc.documentElement.nodeName);
                        }
                    } catch (e) {
                        console.error( 'Error parsing SVG:' , e );
                    } 
    
                }
    
                //If the coordinates were explicitly added
                if ( location.coordinates ) {
    
                    var coordinates = location.coordinates.split( ',' );
    
                    position = { lat: parseFloat( coordinates[0] ), lng: parseFloat( coordinates[1]) };
    
                    this.plotMarker( map, position, location.name, svgIcon );
    
                } else if ( location.address ) {
    
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode( { 'address': location.address }, ( results, status ) => {
    
                        if ( status == google.maps.GeocoderStatus.OK ) {
                            var latitude = results[0].geometry.location.lat();
                            var longitude = results[0].geometry.location.lng();
                            console.log('Successful Geocode:: Latitude:', latitude, 'Longitude:', longitude);
                            position = { lat: latitude, lng: longitude };
                            
                            this.plotMarker( map, position, location.name, svgIcon );
    
                        } else {
                            console.error('Geocode was not successful for the following reason:', status);
                        }
    
                    });
                }
    
            });
    
        }
    
    
        if ( document.querySelector('.maps-data') ) {
    
            console.log( 'Maps Data' );
    
            //Track the center change
            map.addListener('center_changed', () => {
                const center = map.getCenter();
                console.log('New center:', center.lat(), center.lng());
                document.querySelector( selector +' .center-coordinates').innerText = center.lat().toFixed(6) + ', ' + center.lng().toFixed(6);
                
            });
    
            //Track the Zoom Change
            map.addListener('zoom_changed', () => {
                const newZoom = map.getZoom();
                console.log("New Zoom Level:", newZoom);
                document.querySelector( selector + ' .zoom').innerText = newZoom;
    
            });
    
        }
    
        window.bricGoogleMaps[this.selector] = map;

        this.map = map;

        return map;
    }
    

}
