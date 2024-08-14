(function () {
  function BricGoogleMapsLocations() {
    // Import necessary utilities from Breakdance if needed
    const { isElementInDom } = BreakdanceFrontend.utils;

    // Helper function to check if element is in the DOM
    function isMapElementInDom(selector) {
      const isElementInDom = document.querySelector(selector);
      return !!isElementInDom;
    }

    // Helper function to initialize the map
    function initializeMap(mapInstance, options) {
      mapInstance.setOptions(options);
      // Additional logic to add markers, etc.
    }

    // Helper function to destroy map instance
    function destroyMap(id) {
      if (window.googleMapsInstances && window.googleMapsInstances[id]) {
        window.googleMapsInstances[id] = null;
        delete window.googleMapsInstances[id];
      }
    }


    function userOrGlobal( location, options, what ) {

        if ( what == 'size' ) {
            if ( location.icon_size ) {
                return location.icon_size;
            } else if ( options.iconsSize ) {
                return options.iconsSize;
            } else {
                return false;
            }    
        } else if ( what == 'color' ) {
            if ( location.icon_color ) {
                return location.icon_color;
            } else if ( options.iconsColor ) {
                return getColorValue( options.iconsColor );
            } else {
                return false;
            }
        }
    }

    
    function getSvgElement( location, options ) {


        if ( location.icon ) {
            var svgCode = location.icon;
        } else if ( options.customIcon ) {
            var svgCode = options.customIcon;
        } else {
            return false;
        }



        try {
            svgDoc = new DOMParser().parseFromString( svgCode, "image/svg+xml" );
            
            if ( svgDoc.documentElement.nodeName == 'svg' ) {
               

                //Get the 
                const size = userOrGlobal( location, options, 'size' );
                const color = userOrGlobal( location, options, 'color' );

                svgIconEl = svgDoc.documentElement;
                svgIconEl.setAttribute( 'width', size || '50' );
                svgIconEl.setAttribute( 'height', size || '50');
                
                if ( color ) {
                    svgIconEl.setAttribute( 'fill', color );
                } 

                return svgIconEl;

            } else {
                console.error("SVG parsing failed:", svgDoc.documentElement.nodeName);
            }
        } catch (e) {
            console.error( 'Error parsing SVG:' , e );
        } 

        return false;
    }





    function plotMarker(map, position, location, options ) {

        markerOptions = {
            position: position,
            map: map,
            title: location.name

        }

        var svgEl = getSvgElement( location, options );

        if ( svgEl ) {

            markerOptions.content = svgEl;

        }

      new google.maps.marker.AdvancedMarkerElement( markerOptions );
    }




    function getColorValue( data_icon_color ) {


        if ( !data_icon_color ) {

            return false;
        }

        const cssValue = data_icon_color;

        // Regular expression to match the content inside parentheses
        const regex = /\(([^)]+)\)/;

        // Execute the regex on the string to extract the content
        const match = cssValue.match(regex);

        // If there's a match, extract the content
        const extractedValue = match ? match[1] : null;

        const breakdance = document.querySelector('.breakdance');

        const styles = getComputedStyle( breakdance );

        const color_value = styles.getPropertyValue( extractedValue );

        return color_value;

    }




    function getLocations( id ) {

        const location_data = document.querySelectorAll('#locations-' + id +' .location');

        const locations = Array.from(location_data).map(location => {
    
            // Check if there is an SVG element inside the icon div
            const svgElement = location.querySelector('svg');
            
            const svgColor = getColorValue( location.getAttribute( 'data-icon-color' ) );

            return {
                id: location.id,
                name: location.getAttribute('data-name'),
                address: location.getAttribute('data-address'),
                coordinates: location.getAttribute('data-coordinates'),
                icon: svgElement ? svgElement.outerHTML : null,  // Get the SVG as a string if it exists, otherwise null
                icon_color: svgColor,
                icon_size: location.getAttribute( 'data-icon-size')
            };
        });

        return locations;

    }


    function addLocations( mapInstance, locations = {}, options = {}, id ) {

        if ( !locations.length ) {
            locations = getLocations( id );
        }


        // Add markers or other features if necessary
      if (locations && Array.isArray(locations)) {
        locations.forEach( location => {
          if (location.coordinates) {
            const coordinates = location.coordinates.split(",");

            position = {
              lat: parseFloat(coordinates[0]),
              lng: parseFloat(coordinates[1]),
            };

            plotMarker(mapInstance, position, location, options );

          } else if (location.address) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode(
              { address: location.address },
              (results, status) => {
                if (status == google.maps.GeocoderStatus.OK) {
                  var latitude = results[0].geometry.location.lat();
                  var longitude = results[0].geometry.location.lng();
                  
                  position = { lat: latitude, lng: longitude };

                  plotMarker(mapInstance, position, location, options );
                } else {
                  console.error(
                    "Geocode was not successful for the following reason:",
                    status
                  );
                }
              }
            );
          }
        });
      }

    }


    function getCustomGlobalIcon( selector ) {

        var global_icon_el = document.querySelector( selector + ' .custom-global-icon' );

        if ( global_icon_el ) {
            return global_icon_el.innerHTML;
        }
    }


    // Main update function
    function update({ id, selector, options, locations = {} }) {

        const mapSelector = selector + " .google-map";

        //console.log(isMapElementInDom(mapSelector));
        //if (!isMapElementInDom(mapSelector)) return;

      destroyMap(id);
    

      const defaultOptions = {
        "center": { lat: 43.1873235, lng: -70.6156473 },
        "zoom": 8,
        "mapId": "53f02eb8c6c3b17b", //todo: pass this through the element for distribution
        "mapTypeId": "roadmap",
      };

      const userOptions = {
        "zoom": options.zoom ? Number(options.zoom.number) : 8,
        "mapTypeId": options.type || 'roadmap',
        "streetViewControl": options.streetViewControl || false,
        "mapTypeControl": options.mapTypeControl || false,
        "scaleControl": options.scaleControl || false,
        "rotateControl": options.rotateControl || false,
        "zoomControl": options.zoomControl || true,
        "fullscreenControl": options.fullscreenControl || false,
        "iconsColor" : options.iconsColor || false,
        "iconsSize" : options.iconsSize || false,
        "customIcon": getCustomGlobalIcon(selector)
        // Any other default settings you want to apply
      };

      var center;

      if (options.center) {
    
        center = options.center.split(",");
        userOptions.center = { lat: parseFloat(center[0]), lng: parseFloat(center[1]) };
      
     } 
      

      // Merge with user settings
      var mapOptions = Object.assign({}, defaultOptions, userOptions);

     console.log( "MapOptions:", mapOptions );

      var mapElement = document.querySelector(mapSelector);


      // Initialize Google Map
      var mapInstance = new google.maps.Map(mapElement, mapOptions);

      // Store the instance for later use
      window.googleMapsInstances = {
        ...window.googleMapsInstances,
        [id]: mapInstance,
      };

      addLocations( mapInstance, locations, mapOptions, id );

      if ( document.querySelector('.maps-data') ) {
    
        //Track the center change
        mapInstance.addListener('center_changed', () => {
            const center = mapInstance.getCenter();
            console.log('New center:', center.lat(), center.lng());
            document.querySelector( selector +' .center-coordinates').innerText = center.lat().toFixed(6) + ', ' + center.lng().toFixed(6);
        });

        //Track the Zoom Change
        mapInstance.addListener('zoom_changed', () => {
            const newZoom = mapInstance.getZoom();
            console.log("New Zoom Level:", newZoom);
            document.querySelector( selector + ' .zoom').innerText = newZoom;
        });

    }

    }

    // Optional: function to update specific elements like markers or zoom
    function updateMapFeatures(id) {
      const mapInstance = window.googleMapsInstances[id];
      if (!mapInstance) return;

      // Update specific features like markers, bounds, etc.

    }

    return {
      update,
      destroy: destroyMap,
      updateMapFeatures
    };
  }

  // Expose the BricGoogleMapsLocations globally
  window.BricGoogleMapsLocations = BricGoogleMapsLocations;
})();
