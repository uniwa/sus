/**
 * Interactive coordinate picker for the Unit admin form (replaces the abandoned
 * oh_google_maps widget). Leaflet + OpenStreetMap tiles, no API key.
 *
 * Binds a map to the Latitude/Longitude inputs of a `sus_latlng` form widget:
 *  - click on the map, or drag the marker, writes lat/lng into the inputs;
 *  - editing an input moves the marker.
 *
 * Wiring comes from data-* attributes set by admin/form/latlng_map.html.twig.
 */
(function () {
    'use strict';

    function buildIcon(base) {
        return L.icon({
            iconUrl: base + '/marker-icon.png',
            iconRetinaUrl: base + '/marker-icon-2x.png',
            shadowUrl: base + '/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
    }

    function initMap(container) {
        if (container.dataset.susInit || typeof L === 'undefined') {
            return;
        }
        var latInput = document.getElementById(container.dataset.latId);
        var lngInput = document.getElementById(container.dataset.lngId);
        if (!latInput || !lngInput) {
            return;
        }
        container.dataset.susInit = '1';

        var dLat = parseFloat(container.dataset.defaultLat) || 37.984042;
        var dLng = parseFloat(container.dataset.defaultLng) || 23.728179;
        var lat = parseFloat(latInput.value);
        var lng = parseFloat(lngInput.value);
        var hasCoords = !isNaN(lat) && !isNaN(lng) && (lat !== 0 || lng !== 0);
        var center = hasCoords ? [lat, lng] : [dLat, dLng];

        // scrollWheelZoom off by default so scrolling the page over the map doesn't
        // hijack the wheel; it's enabled only while the map has focus (after a click).
        var map = L.map(container, { scrollWheelZoom: false }).setView(center, hasCoords ? 15 : 6);
        map.on('focus', function () { map.scrollWheelZoom.enable(); });
        map.on('blur', function () { map.scrollWheelZoom.disable(); });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker = L.marker(center, {
            draggable: true,
            icon: buildIcon(container.dataset.imgBase)
        }).addTo(map);

        function writeInputs(latlng) {
            latInput.value = latlng.lat.toFixed(7);
            lngInput.value = latlng.lng.toFixed(7);
            // notify any listeners (validation, dirty-tracking)
            latInput.dispatchEvent(new Event('change', { bubbles: true }));
            lngInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        marker.on('dragend', function () { writeInputs(marker.getLatLng()); });
        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            writeInputs(e.latlng);
        });

        function syncFromInputs() {
            var la = parseFloat(latInput.value);
            var ln = parseFloat(lngInput.value);
            if (!isNaN(la) && !isNaN(ln)) {
                var ll = L.latLng(la, ln);
                marker.setLatLng(ll);
                map.panTo(ll);
            }
        }
        latInput.addEventListener('input', syncFromInputs);
        lngInput.addEventListener('input', syncFromInputs);

        // The form field may live in a collapsed Sonata group/tab; fix the size once visible.
        setTimeout(function () { map.invalidateSize(); }, 300);
    }

    function initAll() {
        var maps = document.querySelectorAll('.sus-latlng-map');
        for (var i = 0; i < maps.length; i++) {
            initMap(maps[i]);
        }
    }

    if (document.readyState !== 'loading') {
        initAll();
    } else {
        document.addEventListener('DOMContentLoaded', initAll);
    }
})();
