(function () {
  function BricGoogleMapsLocations() {
    const LOCAL_GEOCODE_CACHE_KEY = "bric-google-maps-geocode-cache-v2";
    const LOCAL_GEOCODE_CACHE_TTL_MS = 180 * 24 * 60 * 60 * 1000;
    const SERVER_CACHE_WRITE_DEBOUNCE_MS = 1200;
    const BUILDER_CONTROL_IDS = {
      center: "control-content-data-center",
      zoom: "control-content-data-zoom",
    };

    const geocodeMemoryCache = {};
    let localGeocodeCache = {};
    let hasLoadedLocalCache = false;
    let queuedServerCacheWrites = [];
    let serverCacheFlushTimer = null;
    let hasShownBuilderSyncWarning = false;

    function isBuilderMode() {
      return Boolean(
        window.BreakdanceFrontend &&
          window.BreakdanceFrontend.utils &&
          typeof window.BreakdanceFrontend.utils.isBuilder === "function" &&
          window.BreakdanceFrontend.utils.isBuilder()
      );
    }

    function debounce(callback, delay) {
      let timeoutId;
      return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => callback.apply(null, args), delay);
      };
    }

    function normalizeAddress(address) {
      if (!address || typeof address !== "string") {
        return "";
      }

      return address.trim().replace(/\s+/g, " ").toLowerCase();
    }

    function parseCoordinates(value) {
      if (!value) {
        return null;
      }

      if (typeof value === "object" && value.lat !== undefined && value.lng !== undefined) {
        const lat = Number(value.lat);
        const lng = Number(value.lng);
        return Number.isFinite(lat) && Number.isFinite(lng) ? { lat, lng } : null;
      }

      if (typeof value !== "string") {
        return null;
      }

      const parts = value.split(",");
      if (parts.length < 2) {
        return null;
      }

      const lat = Number(parts[0].trim());
      const lng = Number(parts[1].trim());

      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
      }

      return { lat, lng };
    }

    function getZoomNumber(zoomValue, fallback = 8) {
      if (zoomValue === null || zoomValue === undefined) {
        return fallback;
      }

      if (typeof zoomValue === "object" && zoomValue.number !== undefined) {
        const parsed = Number(zoomValue.number);
        return Number.isFinite(parsed) ? parsed : fallback;
      }

      const parsed = Number(zoomValue);
      return Number.isFinite(parsed) ? parsed : fallback;
    }

    function shouldUseLocalStorage() {
      try {
        return typeof window.localStorage !== "undefined";
      } catch (error) {
        return false;
      }
    }

    function loadLocalGeocodeCache() {
      if (hasLoadedLocalCache) {
        return;
      }

      hasLoadedLocalCache = true;

      if (!shouldUseLocalStorage()) {
        localGeocodeCache = {};
        return;
      }

      try {
        const raw = window.localStorage.getItem(LOCAL_GEOCODE_CACHE_KEY);
        const parsed = raw ? JSON.parse(raw) : {};
        const now = Date.now();
        const freshCache = {};

        if (parsed && typeof parsed === "object") {
          Object.entries(parsed).forEach(([key, entry]) => {
            const lat = Number(entry?.lat);
            const lng = Number(entry?.lng);
            const updatedAt = Number(entry?.updatedAt || 0);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
              return;
            }

            if (!updatedAt || now - updatedAt > LOCAL_GEOCODE_CACHE_TTL_MS) {
              return;
            }

            freshCache[key] = { lat, lng, updatedAt };
          });
        }

        localGeocodeCache = freshCache;
        persistLocalGeocodeCache();
      } catch (error) {
        localGeocodeCache = {};
      }
    }

    function persistLocalGeocodeCache() {
      if (!shouldUseLocalStorage()) {
        return;
      }

      try {
        window.localStorage.setItem(
          LOCAL_GEOCODE_CACHE_KEY,
          JSON.stringify(localGeocodeCache)
        );
      } catch (error) {
        // Ignore localStorage quota/security errors.
      }
    }

    function setNormalizedCacheEntry(normalizedAddress, entry) {
      if (!normalizedAddress) {
        return false;
      }

      const lat = Number(entry?.lat);
      const lng = Number(entry?.lng);

      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return false;
      }

      loadLocalGeocodeCache();

      const normalizedEntry = {
        lat,
        lng,
        updatedAt: Date.now(),
      };

      geocodeMemoryCache[normalizedAddress] = normalizedEntry;
      localGeocodeCache[normalizedAddress] = normalizedEntry;
      persistLocalGeocodeCache();
      return true;
    }

    function getCachedCoordinatesForAddress(address) {
      const normalizedAddress = normalizeAddress(address);
      if (!normalizedAddress) {
        return null;
      }

      if (geocodeMemoryCache[normalizedAddress]) {
        return geocodeMemoryCache[normalizedAddress];
      }

      loadLocalGeocodeCache();

      const localEntry = localGeocodeCache[normalizedAddress];
      if (!localEntry) {
        return null;
      }

      geocodeMemoryCache[normalizedAddress] = localEntry;
      return localEntry;
    }

    function queueServerCacheWrite(address, coordinates) {
      const normalizedAddress = normalizeAddress(address);
      if (!normalizedAddress) {
        return;
      }

      queuedServerCacheWrites.push({
        address: address.trim(),
        normalizedAddress,
        lat: Number(coordinates.lat),
        lng: Number(coordinates.lng),
      });

      if (serverCacheFlushTimer) {
        clearTimeout(serverCacheFlushTimer);
      }

      serverCacheFlushTimer = setTimeout(() => {
        flushServerCacheWrites();
      }, SERVER_CACHE_WRITE_DEBOUNCE_MS);
    }

    function getAjaxUrl() {
      return window.BreakdanceFrontend?.data?.ajaxUrl || null;
    }

    async function flushServerCacheWrites() {
      if (!queuedServerCacheWrites.length) {
        return;
      }

      const ajaxUrl = getAjaxUrl();
      if (!ajaxUrl) {
        queuedServerCacheWrites = [];
        return;
      }

      const dedupedByAddress = {};
      queuedServerCacheWrites.forEach((entry) => {
        dedupedByAddress[entry.normalizedAddress] = entry;
      });
      queuedServerCacheWrites = [];

      const entries = Object.values(dedupedByAddress).map((entry) => ({
        address: entry.address,
        lat: entry.lat,
        lng: entry.lng,
      }));

      if (!entries.length) {
        return;
      }

      try {
        const body = new URLSearchParams();
        body.append("action", "bric_maps_locations_cache_set");
        body.append("entries", JSON.stringify(entries));

        await fetch(ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: body.toString(),
        });
      } catch (error) {
        // Ignore cache write failures; geocoding still works.
      }
    }

    async function hydrateCacheFromServer(addresses) {
      const ajaxUrl = getAjaxUrl();
      if (!ajaxUrl || !addresses.length) {
        return;
      }

      const uniqueMissingAddresses = [];
      const seen = new Set();

      addresses.forEach((address) => {
        const normalizedAddress = normalizeAddress(address);
        if (!normalizedAddress || seen.has(normalizedAddress)) {
          return;
        }
        seen.add(normalizedAddress);

        if (!getCachedCoordinatesForAddress(address)) {
          uniqueMissingAddresses.push(address);
        }
      });

      if (!uniqueMissingAddresses.length) {
        return;
      }

      const body = new URLSearchParams();
      body.append("action", "bric_maps_locations_cache_get");
      uniqueMissingAddresses.forEach((address) => body.append("addresses[]", address));

      try {
        const response = await fetch(ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: body.toString(),
        });

        const json = await response.json();
        const serverCache = json?.success ? json?.data?.cache : null;

        if (!serverCache || typeof serverCache !== "object") {
          return;
        }

        Object.entries(serverCache).forEach(([normalizedAddress, entry]) => {
          setNormalizedCacheEntry(normalizedAddress, entry);
        });
      } catch (error) {
        // Ignore cache read failures; geocoding still works.
      }
    }

    async function geocodeAddress(geocoder, address) {
      return new Promise((resolve, reject) => {
        geocoder.geocode({ address }, (results, status) => {
          if (
            status === "OK" &&
            results &&
            results[0] &&
            results[0].geometry &&
            results[0].geometry.location
          ) {
            resolve({
              lat: results[0].geometry.location.lat(),
              lng: results[0].geometry.location.lng(),
            });
            return;
          }

          reject(status);
        });
      });
    }

    function getBuilderDocuments() {
      const docs = [];
      const seen = new Set();

      let currentWindow = window;
      while (currentWindow) {
        try {
          const currentDocument = currentWindow.document;
          if (currentDocument && !seen.has(currentDocument)) {
            docs.push(currentDocument);
            seen.add(currentDocument);
          }
        } catch (error) {
          // Ignore cross-frame access issues.
        }

        if (!currentWindow.parent || currentWindow.parent === currentWindow) {
          break;
        }

        currentWindow = currentWindow.parent;
      }

      if (!seen.has(document)) {
        docs.push(document);
      }

      return docs;
    }

    function dispatchInputChangeEvents(input) {
      try {
        input.dispatchEvent(new InputEvent("input", { bubbles: true }));
      } catch (error) {
        input.dispatchEvent(new Event("input", { bubbles: true }));
      }
      input.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function isVisibleElement(element) {
      return Boolean(element && (element.offsetWidth || element.offsetHeight || element.getClientRects().length));
    }

    function findControlWrapperByTestId(builderDocument, testId) {
      const wrappers = Array.from(
        builderDocument.querySelectorAll(
          `[data-test-id="${testId}"], [data-test-id^="${testId}-"]`
        )
      );

      if (!wrappers.length) {
        return null;
      }

      return wrappers.find(isVisibleElement) || wrappers[0];
    }

    function findControlWrapperByLabel(builderDocument, label) {
      const wrappers = Array.from(builderDocument.querySelectorAll(".breakdance-control-wrapper"));
      const normalizedLabel = String(label).trim().toLowerCase();

      for (const wrapper of wrappers) {
        if (!isVisibleElement(wrapper)) {
          continue;
        }

        const labelElement = wrapper.querySelector(".breakdance-control-wrapper-control-label");
        if (!labelElement) {
          continue;
        }

        const labelText = labelElement.textContent ? labelElement.textContent.trim().toLowerCase() : "";
        if (labelText === normalizedLabel) {
          return wrapper;
        }
      }

      return null;
    }

    function getInputFromWrapper(wrapper) {
      if (!wrapper) {
        return null;
      }

      const allInputs = Array.from(
        wrapper.querySelectorAll("input:not([type='hidden']), textarea")
      );

      if (!allInputs.length) {
        return null;
      }

      return allInputs.find(isVisibleElement) || allInputs[0];
    }

    function updateBuilderControl(testId, nextValue, fallbackLabel = "") {
      const builderDocuments = getBuilderDocuments();
      for (const builderDocument of builderDocuments) {
        let wrapper = findControlWrapperByTestId(builderDocument, testId);

        if (!wrapper && fallbackLabel) {
          wrapper = findControlWrapperByLabel(builderDocument, fallbackLabel);
        }

        if (!wrapper) {
          continue;
        }

        const input = getInputFromWrapper(wrapper);

        if (!input) {
          continue;
        }

        const nextStringValue = String(nextValue);
        if (input.value === nextStringValue) {
          return true;
        }

        input.value = nextStringValue;
        dispatchInputChangeEvents(input);
        return true;
      }
      return false;
    }

    function syncBuilderCenterAndZoom(centerString, zoomNumber) {
      if (!isBuilderMode()) {
        return;
      }

      const didSyncCenter = updateBuilderControl(
        BUILDER_CONTROL_IDS.center,
        centerString,
        "Center"
      );
      const didSyncZoom = updateBuilderControl(BUILDER_CONTROL_IDS.zoom, zoomNumber);

      if ((!didSyncCenter || !didSyncZoom) && !hasShownBuilderSyncWarning) {
        hasShownBuilderSyncWarning = true;
        console.warn("[BricGoogleMapsLocations] Builder control sync incomplete.", {
          didSyncCenter,
          didSyncZoom,
        });
      }
    }

    function updateMapDataOverlay(selector, centerString, zoomNumber) {
      const mapData = document.querySelector(selector + " .maps-data");
      if (!mapData) {
        return;
      }

      const centerElement = mapData.querySelector(".center-coordinates");
      const zoomElement = mapData.querySelector(".zoom");

      if (centerElement) {
        centerElement.innerText = centerString;
      }

      if (zoomElement) {
        zoomElement.innerText = String(zoomNumber);
      }
    }

    function destroyMap(id) {
      if (window.googleMapsInstances && window.googleMapsInstances[id]) {
        window.googleMapsInstances[id] = null;
        delete window.googleMapsInstances[id];
      }
    }

    function userOrGlobal(location, options, what) {
      if (what === "size") {
        if (location.icon_size) {
          return location.icon_size;
        } else if (options.iconsSize) {
          return options.iconsSize;
        }

        return false;
      } else if (what === "color") {
        if (location.icon_color) {
          return location.icon_color;
        } else if (options.iconsColor) {
          return getColorValue(options.iconsColor);
        }

        return false;
      }

      return false;
    }

    function getSvgElement(location, options) {
      let svgCode;

      if (location.icon) {
        svgCode = location.icon;
      } else if (options.customIcon) {
        svgCode = options.customIcon;
      } else {
        return false;
      }

      try {
        const svgDoc = new DOMParser().parseFromString(svgCode, "image/svg+xml");

        if (svgDoc.documentElement.nodeName === "svg") {
          const size = userOrGlobal(location, options, "size");
          const color = userOrGlobal(location, options, "color");

          const svgIconEl = svgDoc.documentElement;
          svgIconEl.setAttribute("width", size || "50");
          svgIconEl.setAttribute("height", size || "50");

          if (color) {
            svgIconEl.setAttribute("fill", color);
          }

          return svgIconEl;
        }

        console.error("SVG parsing failed:", svgDoc.documentElement.nodeName);
      } catch (error) {
        console.error("Error parsing SVG:", error);
      }

      return false;
    }

    function plotMarker(map, position, location, options) {
      const markerOptions = {
        position,
        map,
        title: location.name,
      };

      const svgEl = getSvgElement(location, options);
      if (svgEl) {
        markerOptions.content = svgEl;
      }

      new google.maps.marker.AdvancedMarkerElement(markerOptions);
    }

    function getColorValue(dataIconColor) {
      if (!dataIconColor) {
        return false;
      }

      const regex = /\(([^)]+)\)/;
      const match = dataIconColor.match(regex);
      const extractedValue = match ? match[1] : null;

      if (!extractedValue) {
        return false;
      }

      const breakdanceRoot = document.querySelector(".breakdance") || document.documentElement;
      const styles = getComputedStyle(breakdanceRoot);
      return styles.getPropertyValue(extractedValue);
    }

    function getLocations(id) {
      const locationData = document.querySelectorAll("#locations-" + id + " .location");

      return Array.from(locationData).map((location) => {
        const svgElement = location.querySelector("svg");
        const svgColor = getColorValue(location.getAttribute("data-icon-color"));

        return {
          id: location.id,
          name: location.getAttribute("data-name"),
          address: location.getAttribute("data-address"),
          coordinates: location.getAttribute("data-coordinates"),
          icon: svgElement ? svgElement.outerHTML : null,
          icon_color: svgColor,
          icon_size: location.getAttribute("data-icon-size"),
        };
      });
    }

    function markAndPlotLocation(location, position, mapInstance, options) {
      if (!position || location._plotted) {
        return;
      }

      location._plotted = true;
      location.coordinates = `${position.lat},${position.lng}`;
      plotMarker(mapInstance, position, location, options);
    }

    async function addLocations(mapInstance, locations = {}, options = {}, id) {
      if (!locations.length) {
        locations = getLocations(id);
      }

      if (!locations || !Array.isArray(locations)) {
        return;
      }

      const geocoder = new google.maps.Geocoder();
      const addressesNeedingLookup = [];

      locations.forEach((location) => {
        const parsedCoordinates = parseCoordinates(location.coordinates);
        if (parsedCoordinates) {
          markAndPlotLocation(location, parsedCoordinates, mapInstance, options);
          return;
        }

        if (!location.address) {
          return;
        }

        const cachedCoordinates = getCachedCoordinatesForAddress(location.address);
        if (cachedCoordinates) {
          markAndPlotLocation(location, cachedCoordinates, mapInstance, options);
          return;
        }

        addressesNeedingLookup.push(location.address);
      });

      await hydrateCacheFromServer(addressesNeedingLookup);

      const uniqueAddressesToGeocode = [];
      const queuedAddressSet = new Set();

      locations.forEach((location) => {
        if (location._plotted || !location.address) {
          return;
        }

        const cachedCoordinates = getCachedCoordinatesForAddress(location.address);
        if (cachedCoordinates) {
          markAndPlotLocation(location, cachedCoordinates, mapInstance, options);
          return;
        }

        const normalizedAddress = normalizeAddress(location.address);
        if (!normalizedAddress || queuedAddressSet.has(normalizedAddress)) {
          return;
        }

        queuedAddressSet.add(normalizedAddress);
        uniqueAddressesToGeocode.push(location.address);
      });

      for (const address of uniqueAddressesToGeocode) {
        try {
          const coordinates = await geocodeAddress(geocoder, address);
          setNormalizedCacheEntry(normalizeAddress(address), coordinates);
          queueServerCacheWrite(address, coordinates);
        } catch (status) {
          console.error(
            "Geocode was not successful for the following reason:",
            status,
            address
          );
        }
      }

      locations.forEach((location) => {
        if (location._plotted || !location.address) {
          return;
        }

        const cachedCoordinates = getCachedCoordinatesForAddress(location.address);
        if (cachedCoordinates) {
          markAndPlotLocation(location, cachedCoordinates, mapInstance, options);
        }
      });
    }

    function getCustomGlobalIcon(selector) {
      const globalIconEl = document.querySelector(selector + " .custom-global-icon");
      if (globalIconEl) {
        return globalIconEl.innerHTML;
      }
      return null;
    }

    function update({ id, selector, options, locations = {} }) {
      const mapSelector = selector + " .google-map";

      destroyMap(id);

      const defaultOptions = {
        center: { lat: 43.1873235, lng: -70.6156473 },
        zoom: 8,
        mapId: "53f02eb8c6c3b17b",
        mapTypeId: "roadmap",
      };

      const userOptions = {
        zoom: getZoomNumber(options.zoom, 8),
        mapTypeId: options.type || "roadmap",
        streetViewControl: options.streetViewControl || false,
        mapTypeControl: options.mapTypeControl || false,
        scaleControl: options.scaleControl || false,
        rotateControl: options.rotateControl || false,
        zoomControl:
          typeof options.zoomControl === "boolean" ? options.zoomControl : true,
        fullscreenControl: options.fullscreenControl || false,
        iconsColor: options.iconsColor || false,
        iconsSize: options.iconsSize || false,
        customIcon: getCustomGlobalIcon(selector),
      };

      const userCenter = parseCoordinates(options.center);
      if (userCenter) {
        userOptions.center = userCenter;
      }

      const mapOptions = Object.assign({}, defaultOptions, userOptions);
      const mapElement = document.querySelector(mapSelector);

      if (!mapElement) {
        return;
      }

      const mapInstance = new google.maps.Map(mapElement, mapOptions);

      window.googleMapsInstances = {
        ...window.googleMapsInstances,
        [id]: mapInstance,
      };

      addLocations(mapInstance, locations, mapOptions, id);

      if (isBuilderMode()) {
        const syncBuilderState = debounce(() => {
          const currentCenter = mapInstance.getCenter();
          const currentZoom = mapInstance.getZoom();

          if (!currentCenter || currentZoom === null || currentZoom === undefined) {
            return;
          }

          const centerString =
            currentCenter.lat().toFixed(6) + ", " + currentCenter.lng().toFixed(6);

          updateMapDataOverlay(selector, centerString, currentZoom);
          syncBuilderCenterAndZoom(centerString, currentZoom);
        }, 250);

        mapInstance.addListener("idle", syncBuilderState);
        syncBuilderState();
      }
    }

    function updateMapFeatures(id) {
      const mapInstance = window.googleMapsInstances[id];
      if (!mapInstance) return;
    }

    return {
      update,
      destroy: destroyMap,
      updateMapFeatures,
    };
  }

  window.BricGoogleMapsLocations = BricGoogleMapsLocations;
})();
