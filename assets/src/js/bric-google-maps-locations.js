(function () {
  function BricGoogleMapsLocations() {
    const PROPERTY_CHANGE_SKIP_TTL_MS = 6000;
    const BUILDER_CONTROL_IDS = {
      center: "control-content-data-center",
      zoom: "control-content-data-zoom",
    };

    let hasShownBuilderSyncWarning = false;

    function getGlobalMapStore(key, fallback) {
      if (!window[key]) {
        window[key] = fallback;
      }
      return window[key];
    }

    function markPropertyChangeUpdateToSkip(id, increments = 1) {
      if (!id) {
        return;
      }

      const skipStore = getGlobalMapStore("googleMapsPropertyChangeSkip", {});
      const now = Date.now();
      const current = skipStore[id];
      const currentCount = current && current.expiresAt > now ? Number(current.count) || 0 : 0;

      skipStore[id] = {
        count: Math.max(0, currentCount + increments),
        expiresAt: now + PROPERTY_CHANGE_SKIP_TTL_MS,
      };
    }

    function shouldSkipPropertyChangeUpdate(id) {
      if (!id) {
        return false;
      }

      const skipStore = getGlobalMapStore("googleMapsPropertyChangeSkip", {});
      const entry = skipStore[id];
      if (!entry) {
        return false;
      }

      const now = Date.now();
      if (!entry.expiresAt || entry.expiresAt <= now || !entry.count) {
        delete skipStore[id];
        return false;
      }

      entry.count -= 1;
      if (entry.count <= 0) {
        delete skipStore[id];
      } else {
        entry.expiresAt = now + PROPERTY_CHANGE_SKIP_TTL_MS;
      }

      return true;
    }

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

    function parsePostId(postIdValue) {
      const parsed = Number(postIdValue);
      return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getAjaxUrl() {
      return window.BreakdanceFrontend?.data?.ajaxUrl || null;
    }

    function queueServerCacheWrite(address, coordinates, postId) {
      const ajaxUrl = getAjaxUrl();
      const normalizedAddress = normalizeAddress(address);
      if (!normalizedAddress || !postId || !ajaxUrl) {
        return;
      }

      const lat = Number(coordinates?.lat);
      const lng = Number(coordinates?.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
      }

      const body = new URLSearchParams();
      body.append("action", "bric_maps_locations_cache_set");
      body.append("post_id", String(postId));
      body.append(
        "entries",
        JSON.stringify([
          {
            address: address.trim(),
            lat,
            lng,
          },
        ])
      );

      fetch(ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
      })
        .then(async (response) => {
          const json = await response.json().catch(() => null);
          if (json?.success) {
            return;
          }

          if (isBuilderMode()) {
            console.warn("[BricGoogleMapsLocations] Failed to persist geocode cache.", {
              postId,
              response: json,
            });
          }
        })
        .catch(() => {
          if (isBuilderMode()) {
            console.warn("[BricGoogleMapsLocations] Error while persisting geocode cache.", {
              postId,
            });
          }
        });
    }

    async function fetchServerCachedCoordinates(addresses, postId) {
      const ajaxUrl = getAjaxUrl();
      if (!ajaxUrl || !postId || !addresses.length) {
        return {};
      }

      const uniqueAddresses = [];
      const seen = new Set();

      addresses.forEach((address) => {
        const normalizedAddress = normalizeAddress(address);
        if (!normalizedAddress || seen.has(normalizedAddress)) {
          return;
        }

        seen.add(normalizedAddress);
        uniqueAddresses.push(address);
      });

      if (!uniqueAddresses.length) {
        return {};
      }

      const body = new URLSearchParams();
      body.append("action", "bric_maps_locations_cache_get");
      body.append("post_id", String(postId));
      uniqueAddresses.forEach((address) => body.append("addresses[]", address));

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
        const rawCache = json?.success ? json?.data?.cache : null;

        if (!rawCache || typeof rawCache !== "object") {
          return {};
        }

        const normalizedCache = {};
        Object.entries(rawCache).forEach(([normalizedAddress, entry]) => {
          const lat = Number(entry?.lat);
          const lng = Number(entry?.lng);

          if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
          }

          normalizedCache[normalizedAddress] = { lat, lng };
        });

        return normalizedCache;
      } catch (error) {
        return {};
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
      return Boolean(
        element && (element.offsetWidth || element.offsetHeight || element.getClientRects().length)
      );
    }

    function findControlWrapperByTestId(builderDocument, testId) {
      const wrappers = Array.from(
        builderDocument.querySelectorAll(`[data-test-id="${testId}"], [data-test-id^="${testId}-"]`)
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

        const labelText = labelElement.textContent
          ? labelElement.textContent.trim().toLowerCase()
          : "";

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

      const allInputs = Array.from(wrapper.querySelectorAll("input:not([type='hidden']), textarea"));
      if (!allInputs.length) {
        return null;
      }

      return allInputs.find(isVisibleElement) || allInputs[0];
    }

    function detectRepeaterIndexOffset(locations) {
      if (!Array.isArray(locations) || !locations.length) {
        return 0;
      }

      const locationAddressIndexMap = {};
      locations.forEach((location, locationIndex) => {
        const normalizedAddress = normalizeAddress(location?.address);
        if (!normalizedAddress) {
          return;
        }

        if (!locationAddressIndexMap[normalizedAddress]) {
          locationAddressIndexMap[normalizedAddress] = [];
        }

        locationAddressIndexMap[normalizedAddress].push(locationIndex);
      });

      for (const builderDocument of getBuilderDocuments()) {
        const wrappers = Array.from(
          builderDocument.querySelectorAll(
            '[data-test-id*="control-content-data-locations"][data-test-id*="-address"]'
          )
        );

        for (const wrapper of wrappers) {
          const testId = wrapper.getAttribute("data-test-id") || "";
          const match = testId.match(/locations\[(\d+)\]-address/);
          if (!match) {
            continue;
          }

          const input = getInputFromWrapper(wrapper);
          if (!input) {
            continue;
          }

          const normalizedInputAddress = normalizeAddress(input.value);
          if (!normalizedInputAddress) {
            continue;
          }

          const matchedIndexes = locationAddressIndexMap[normalizedInputAddress] || [];
          if (matchedIndexes.length !== 1) {
            continue;
          }

          const controlIndex = Number(match[1]);
          const locationIndex = matchedIndexes[0];
          if (Number.isInteger(controlIndex) && Number.isInteger(locationIndex)) {
            return controlIndex - locationIndex;
          }
        }
      }

      return 0;
    }

    function findRepeaterCoordinateInputByIndex(index, indexOffset = 0) {
      if (!Number.isInteger(index) || index < 0) {
        return null;
      }

      const candidateIndex = index + indexOffset;
      if (!Number.isInteger(candidateIndex) || candidateIndex < 0) {
        return null;
      }

      const builderDocuments = getBuilderDocuments();

      for (const builderDocument of builderDocuments) {
        const baseTestId = `control-content-data-locations[${candidateIndex}]-coordinates`;
        const wrappers = Array.from(
          builderDocument.querySelectorAll(
            `[data-test-id="${baseTestId}"], [data-test-id^="${baseTestId}-"]`
          )
        );

        for (const wrapper of wrappers) {
          const input = getInputFromWrapper(wrapper);
          if (input) {
            return input;
          }
        }
      }

      return null;
    }

    function findActiveRepeaterAddressContext() {
      const activeElement = document.activeElement;
      if (!activeElement || typeof activeElement.closest !== "function") {
        return null;
      }

      const wrapper = activeElement.closest(
        '[data-test-id*="control-content-data-locations"][data-test-id*="-address"]'
      );
      if (!wrapper) {
        return null;
      }

      const testId = wrapper.getAttribute("data-test-id") || "";
      const match = testId.match(/locations\[(\d+)\]-address/);
      if (!match) {
        return null;
      }

      const controlIndex = Number(match[1]);
      if (!Number.isInteger(controlIndex) || controlIndex < 0) {
        return null;
      }

      const input = getInputFromWrapper(wrapper);
      if (!input) {
        return null;
      }

      const normalizedAddress = normalizeAddress(input.value);
      if (!normalizedAddress) {
        return null;
      }

      return {
        controlIndex,
        normalizedAddress,
      };
    }

    function syncBuilderLocationCoordinates(id, locations) {
      if (!isBuilderMode() || !Array.isArray(locations) || !locations.length) {
        return;
      }

      const indexOffset = detectRepeaterIndexOffset(locations);
      let changedCount = 0;

      locations.forEach((location, index) => {
        const input = findRepeaterCoordinateInputByIndex(index, indexOffset);
        if (!input) {
          return;
        }

        const parsedCoordinates = parseCoordinates(location.coordinates);
        if (!parsedCoordinates) {
          return;
        }

        const nextValue =
          parsedCoordinates.lat.toFixed(6) + "," + parsedCoordinates.lng.toFixed(6);

        if (input.value === nextValue) {
          return;
        }

        input.value = nextValue;
        dispatchInputChangeEvents(input);
        changedCount += 1;
      });

      if (changedCount === 0) {
        const activeContext = findActiveRepeaterAddressContext();
        if (activeContext) {
          const matchingLocation = locations.find((location) => {
            const locationAddress = normalizeAddress(location?.address);
            const parsedCoordinates = parseCoordinates(location?.coordinates);
            return (
              locationAddress &&
              locationAddress === activeContext.normalizedAddress &&
              Boolean(parsedCoordinates)
            );
          });

          if (matchingLocation) {
            const activeCoordinateInput = findRepeaterCoordinateInputByIndex(
              activeContext.controlIndex,
              0
            );
            const parsedCoordinates = parseCoordinates(matchingLocation.coordinates);

            if (activeCoordinateInput && parsedCoordinates) {
              const nextValue =
                parsedCoordinates.lat.toFixed(6) +
                "," +
                parsedCoordinates.lng.toFixed(6);

              if (activeCoordinateInput.value !== nextValue) {
                activeCoordinateInput.value = nextValue;
                dispatchInputChangeEvents(activeCoordinateInput);
                changedCount = 1;
              }
            }
          }
        }
      }

      if (changedCount > 0) {
        // Persisted coordinate updates trigger property-change callbacks; skip redundant redraws.
        markPropertyChangeUpdateToSkip(id, changedCount);
      }
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
          return {
            found: true,
            changed: false,
          };
        }

        input.value = nextStringValue;
        dispatchInputChangeEvents(input);
        return {
          found: true,
          changed: true,
        };
      }

      return {
        found: false,
        changed: false,
      };
    }

    function syncBuilderCenterAndZoom(id, centerString, zoomNumber) {
      if (!isBuilderMode()) {
        return;
      }

      const centerSync = updateBuilderControl(
        BUILDER_CONTROL_IDS.center,
        centerString,
        "Center"
      );
      const zoomSync = updateBuilderControl(BUILDER_CONTROL_IDS.zoom, zoomNumber);

      if (centerSync.changed || zoomSync.changed) {
        // The map is already at the latest center/zoom; skip the immediate property-change redraw cycle.
        markPropertyChangeUpdateToSkip(id, 2);
      }

      if ((!centerSync.found || !zoomSync.found) && !hasShownBuilderSyncWarning) {
        hasShownBuilderSyncWarning = true;
        console.warn("[BricGoogleMapsLocations] Builder control sync incomplete.", {
          didSyncCenter: centerSync.found,
          didSyncZoom: zoomSync.found,
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

    function registerMarker(id, marker) {
      const markersStore = getGlobalMapStore("googleMapsMarkers", {});
      if (!markersStore[id]) {
        markersStore[id] = [];
      }

      markersStore[id].push(marker);
    }

    function clearMarkers(id) {
      const markersStore = getGlobalMapStore("googleMapsMarkers", {});
      const markers = markersStore[id] || [];

      markers.forEach((marker) => {
        marker.map = null;
      });

      delete markersStore[id];
    }

    function plotMarker(map, position, location, options, id) {
      const markerOptions = {
        position,
        map,
        title: location.name,
      };

      const svgEl = getSvgElement(location, options);
      if (svgEl) {
        markerOptions.content = svgEl;
      }

      const marker = new google.maps.marker.AdvancedMarkerElement(markerOptions);
      registerMarker(id, marker);
      return marker;
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

    function getLocationsSignature(id) {
      const locationData = document.querySelectorAll("#locations-" + id + " .location");

      return Array.from(locationData)
        .map((location) => {
          const parts = [
            location.getAttribute("data-name") || "",
            location.getAttribute("data-address") || "",
            location.getAttribute("data-coordinates") || "",
            location.getAttribute("data-icon-color") || "",
            location.getAttribute("data-icon-size") || "",
            location.innerHTML || "",
          ];

          return parts.join("|");
        })
        .join("||");
    }

    function markAndPlotLocation(location, position, mapInstance, options, id) {
      if (!position || location._plotted) {
        return;
      }

      location._plotted = true;
      location.coordinates = `${position.lat},${position.lng}`;
      if (location.id) {
        const locationElement = document.getElementById(location.id);
        if (locationElement) {
          locationElement.setAttribute("data-coordinates", location.coordinates);
        }
      }
      plotMarker(mapInstance, position, location, options, id);
    }

    function shouldForceRegeocodeForDuplicateCoordinates(locations) {
      if (!Array.isArray(locations) || locations.length < 3) {
        return false;
      }

      const normalizedAddresses = [];
      const coordinateKeys = new Set();

      for (const location of locations) {
        const normalizedAddress = normalizeAddress(location?.address);
        if (!normalizedAddress) {
          continue;
        }

        const parsedCoordinates = parseCoordinates(location?.coordinates);
        if (!parsedCoordinates) {
          return false;
        }

        normalizedAddresses.push(normalizedAddress);
        coordinateKeys.add(
          parsedCoordinates.lat.toFixed(6) + "," + parsedCoordinates.lng.toFixed(6)
        );
      }

      const uniqueAddresses = new Set(normalizedAddresses);
      if (uniqueAddresses.size < 3) {
        return false;
      }

      return coordinateKeys.size === 1;
    }

    async function addLocations(
      mapInstance,
      locations = {},
      options = {},
      id,
      runtimeOptions = {}
    ) {
      const allowLiveGeocoding = Boolean(runtimeOptions.allowLiveGeocoding);
      const postId = parsePostId(runtimeOptions.postId);

      if (!locations.length) {
        locations = getLocations(id);
      }

      if (!locations || !Array.isArray(locations)) {
        return;
      }

      if (allowLiveGeocoding && shouldForceRegeocodeForDuplicateCoordinates(locations)) {
        locations.forEach((location) => {
          location.coordinates = "";
          location._plotted = false;

          if (location.id) {
            const locationElement = document.getElementById(location.id);
            if (locationElement) {
              locationElement.setAttribute("data-coordinates", "");
            }
          }
        });
      }

      const addressesNeedingLookup = [];

      locations.forEach((location) => {
        const parsedCoordinates = parseCoordinates(location.coordinates);
        if (parsedCoordinates) {
          markAndPlotLocation(location, parsedCoordinates, mapInstance, options, id);
          return;
        }

        if (!location.address) {
          return;
        }

        addressesNeedingLookup.push(location.address);
      });

      const serverCache = await fetchServerCachedCoordinates(addressesNeedingLookup, postId);

      locations.forEach((location) => {
        if (location._plotted || !location.address) {
          return;
        }

        const cachedCoordinates = serverCache[normalizeAddress(location.address)] || null;
        if (cachedCoordinates) {
          markAndPlotLocation(location, cachedCoordinates, mapInstance, options, id);
        }
      });

      if (allowLiveGeocoding) {
        syncBuilderLocationCoordinates(id, locations);
      }

      // Frontend should only consume cached coordinates; geocoding is editor-only.
      if (!allowLiveGeocoding) {
        return;
      }

      const uniqueAddressesToGeocode = [];
      const queuedAddressSet = new Set();

      locations.forEach((location) => {
        if (location._plotted || !location.address) {
          return;
        }

        const normalizedAddress = normalizeAddress(location.address);
        if (!normalizedAddress || queuedAddressSet.has(normalizedAddress)) {
          return;
        }

        queuedAddressSet.add(normalizedAddress);
        uniqueAddressesToGeocode.push(location.address);
      });

      if (!uniqueAddressesToGeocode.length) {
        syncBuilderLocationCoordinates(id, locations);
        return;
      }

      const geocoder = new google.maps.Geocoder();

      for (const address of uniqueAddressesToGeocode) {
        const normalizedAddress = normalizeAddress(address);

        try {
          const coordinates = await geocodeAddress(geocoder, address);
          serverCache[normalizedAddress] = coordinates;
          queueServerCacheWrite(address, coordinates, postId);

          locations.forEach((location) => {
            if (
              !location._plotted &&
              normalizeAddress(location.address) === normalizedAddress
            ) {
              markAndPlotLocation(location, coordinates, mapInstance, options, id);
            }
          });
        } catch (status) {
          console.error(
            "Geocode was not successful for the following reason:",
            status,
            address
          );
        }
      }

      syncBuilderLocationCoordinates(id, locations);
    }

    function getCustomGlobalIcon(selector) {
      const globalIconEl = document.querySelector(selector + " .custom-global-icon");
      if (globalIconEl) {
        return globalIconEl.innerHTML;
      }

      return null;
    }

    function destroyMap(id) {
      clearMarkers(id);

      const instancesStore = getGlobalMapStore("googleMapsInstances", {});
      const stateStore = getGlobalMapStore("googleMapsInstanceState", {});

      if (instancesStore[id]) {
        delete instancesStore[id];
      }

      if (stateStore[id]) {
        delete stateStore[id];
      }
    }

    function update({ id, selector, options, locations = {} }) {
      const mapSelector = selector + " .google-map";
      const mapElement = document.querySelector(mapSelector);

      if (!mapElement) {
        return;
      }

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
        zoomControl: typeof options.zoomControl === "boolean" ? options.zoomControl : true,
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
      const postId = parsePostId(options.postId);
      const locationsSignature = getLocationsSignature(id);

      const instancesStore = getGlobalMapStore("googleMapsInstances", {});
      const stateStore = getGlobalMapStore("googleMapsInstanceState", {});

      const existingMapInstance = instancesStore[id] || null;
      const existingState = stateStore[id] || {};
      const canReuseMap = Boolean(existingMapInstance && existingState.mapElement === mapElement);

      let mapInstance;
      let isNewInstance = false;

      if (canReuseMap) {
        mapInstance = existingMapInstance;
        mapInstance.setOptions(mapOptions);
      } else {
        if (existingMapInstance) {
          destroyMap(id);
        }

        mapInstance = new google.maps.Map(mapElement, mapOptions);
        instancesStore[id] = mapInstance;
        isNewInstance = true;
      }

      const shouldRefreshMarkers =
        isNewInstance || existingState.locationsSignature !== locationsSignature;

      const allowLiveGeocoding =
        typeof options.allowLiveGeocoding === "boolean"
          ? options.allowLiveGeocoding
          : isBuilderMode();

      if (shouldRefreshMarkers) {
        clearMarkers(id);
        addLocations(mapInstance, locations, mapOptions, id, {
          allowLiveGeocoding,
          postId,
        });
      }

      stateStore[id] = {
        ...existingState,
        mapElement,
        locationsSignature,
        postId,
      };

      if (isBuilderMode() && !stateStore[id].builderListenersAttached) {
        const syncBuilderState = debounce(() => {
          const currentCenter = mapInstance.getCenter();
          const currentZoom = mapInstance.getZoom();

          if (!currentCenter || currentZoom === null || currentZoom === undefined) {
            return;
          }

          const centerString =
            currentCenter.lat().toFixed(6) + ", " + currentCenter.lng().toFixed(6);

          updateMapDataOverlay(selector, centerString, currentZoom);
          syncBuilderCenterAndZoom(id, centerString, currentZoom);
        }, 250);

        mapInstance.addListener("dragend", syncBuilderState);
        mapInstance.addListener("zoom_changed", syncBuilderState);
        syncBuilderState();

        stateStore[id].builderListenersAttached = true;
      }
    }

    function updateMapFeatures(id) {
      const instancesStore = getGlobalMapStore("googleMapsInstances", {});
      const mapInstance = instancesStore[id];
      if (!mapInstance) {
        return;
      }
    }

    return {
      update,
      destroy: destroyMap,
      updateMapFeatures,
      shouldSkipPropertyChangeUpdate,
    };
  }

  window.BricGoogleMapsLocations = BricGoogleMapsLocations;
})();
