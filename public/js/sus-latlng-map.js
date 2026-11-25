/**
 * Interactive coordinate picker for the Unit admin form (replaces the abandoned
 * oh_google_maps widget). Leaflet + OpenStreetMap tiles, no API key.
 *
 * Binds a map to the Latitude/Longitude inputs of a `sus_latlng` form widget:
 *  - click on the map, or drag the marker, writes lat/lng into the inputs;
 *  - editing an input moves the marker;
 *  - an address search box (as the old widget had) finds the point via OSM Nominatim.
 *
 * Address search: the box is filled from "Οδός, Αριθμός" until the user types in it. Free-text
 * Greek addresses geocode poorly on their own (postal codes are barely known to OSM), so the
 * search is first limited to the unit's Δήμος (or Περιφερειακή Ενότητα), whose bounds are looked
 * up by name; only if that finds nothing is the whole country searched. Nominatim allows at most
 * one request per second, hence the spacing between the requests of one search.
 *
 * "Συμπλήρωση πεδίων" copies the chosen result's address into the form: Οδός/Αριθμός, Τ.Κ. and
 * the Περιφέρεια > Περ. Ενότητα > Δήμος > Δημοτική Ενότητα dropdowns, matched to the options by
 * name (accents, case, "Δήμος "-style prefixes and punctuation ignored). Dropdowns are set top-down
 * so sus-dependent-selects.js narrows each level before the next is chosen; a level with no
 * matching option is left as it was and reported.
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

    var NOMINATIM = 'https://nominatim.openstreetmap.org/search';
    var lastRequestAt = 0;
    var areaCache = {};

    function wait(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function nominatim(params) {
        var delay = Math.max(0, lastRequestAt + 1100 - Date.now());
        return wait(delay).then(function () {
            lastRequestAt = Date.now();
            var query = Object.assign({ format: 'jsonv2', 'accept-language': 'el', limit: 5, addressdetails: 1 }, params);
            var url = NOMINATIM + '?' + Object.keys(query).map(function (k) {
                return encodeURIComponent(k) + '=' + encodeURIComponent(query[k]);
            }).join('&');
            return fetch(url, { headers: { Accept: 'application/json' } });
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    /** Bounds of a Δήμος / Περιφερειακή Ενότητα, as a Nominatim viewbox, or null. */
    function areaBounds(name, country) {
        if (!(name in areaCache)) {
            areaCache[name] = nominatim({ q: name, limit: 1, countrycodes: country }).then(function (rows) {
                if (!rows.length) {
                    return null;
                }
                var b = rows[0].boundingbox; // [south, north, west, east]
                return { viewbox: [b[2], b[1], b[3], b[0]].join(','), bounds: [[b[0], b[2]], [b[1], b[3]]] };
            }).catch(function () { return null; });
        }
        return areaCache[name];
    }

    function formField(form, suffix) {
        // Only form controls: Sonata also gives each field's wrapper div an id ending in the
        // field name (sonata-ba-field-container-…_streetAddress), and it comes first.
        var sel = '_' + suffix + '"]';
        return form ? form.querySelector('input[id$="' + sel + ', select[id$="' + sel + ', textarea[id$="' + sel) : null;
    }

    function selectedText(select) {
        if (!select || !select.options || !select.value || select.selectedIndex < 0) {
            return '';
        }
        return select.options[select.selectedIndex].text.trim();
    }

    function normalize(text) {
        return String(text || '')
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .toUpperCase()
            .replace(/^(ΠΕΡΙΦΕΡΕΙΑΚΗ ΕΝΟΤΗΤΑ|ΠΕΡΙΦΕΡΕΙΑ|ΔΗΜΟΤΙΚΗ ΕΝΟΤΗΤΑ|ΔΗΜΟΤΙΚΗ ΚΟΙΝΟΤΗΤΑ|ΚΟΙΝΟΤΗΤΑ|ΔΗΜΟΣ)\s+/, '')
            .replace(/&/g, ' ΚΑΙ ')
            .replace(/[^A-Z0-9Α-Ω.]+/g, ' ')
            .trim();
    }

    /** "ΑΝ. ΜΑΚΕΔΟΝΙΑΣ & ΘΡΑΚΗΣ" matches "Ανατολικής Μακεδονίας και Θράκης": abbreviations are prefixes. */
    function namesMatch(optionText, osmName) {
        var a = normalize(optionText);
        var b = normalize(osmName);
        if (!a || !b) {
            return false;
        }
        if (a.replace(/[\s.]/g, '') === b.replace(/[\s.]/g, '')) {
            return true;
        }
        var at = a.split(/\s+/);
        var bt = b.split(/\s+/);
        return at.length === bt.length && at.every(function (token, i) {
            return token.slice(-1) === '.' ? bt[i].indexOf(token.slice(0, -1)) === 0 : token === bt[i];
        });
    }

    function setSelectByName(select, names) {
        if (!select) {
            return false;
        }
        var option = Array.prototype.find.call(select.options, function (o) {
            return o.value !== '' && names.some(function (n) { return namesMatch(o.text, n); });
        });
        if (!option) {
            return false;
        }
        if (window.jQuery) {
            window.jQuery(select).val(option.value).trigger('change');
        } else {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return true;
    }

    function setInput(field, value) {
        if (!field || !value) {
            return false;
        }
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }

    /** Copy an address-detailed Nominatim result into the unit form; returns {filled, missed}. */
    function fillAddressFields(form, address) {
        var filled = [];
        var missed = [];
        function track(ok, label) {
            (ok ? filled : missed).push(label);
        }
        var values = Object.keys(address).map(function (k) { return address[k]; });
        var community = values.filter(function (v) { return /^(Δημοτική Ενότητα|Δημοτική Κοινότητα) /.test(v); });
        if (address.municipality) {
            community.push(address.municipality); // single-unit Δήμοι share the Δήμος name
        }

        if (address.country_code && address.country_code !== 'gr') {
            // Outside Greece the Greek Περιφέρεια/Δήμος dropdowns cannot apply.
            track(setInput(formField(form, 'postalCode'), (address.postcode || '').replace(/\s+/g, '')), 'Τ.Κ.');
            return { filled: filled, missed: missed };
        }

        var street = [address.road || address.pedestrian || address.square, address.house_number].filter(Boolean).join(' ');
        track(setInput(formField(form, 'streetAddress'), street), 'Οδός, Αριθμός');
        track(setInput(formField(form, 'postalCode'), (address.postcode || '').replace(/\s+/g, '')), 'Τ.Κ.');
        track(setSelectByName(formField(form, 'region'), [address.state].filter(Boolean)), 'Περιφέρεια');
        track(setSelectByName(formField(form, 'prefecture'), [address.county].filter(Boolean)), 'Περιφερειακή Ενότητα');
        track(setSelectByName(formField(form, 'municipality'), [address.municipality, address.city].filter(Boolean)), 'Δήμος');
        track(setSelectByName(formField(form, 'municipalityCommunity'), community), 'Δημοτική Ενότητα');

        return { filled: filled, missed: missed };
    }

    /**
     * The typed text first, then shorter versions OSM is likelier to know: the text inside
     * parentheses on its own, and leading comma-separated parts dropped (usually a building or
     * business name, e.g. "Shkolla Jo Publike "Omiros", SH75, Korçë 7023" → "SH75, Korçë 7023").
     */
    function queryVariants(q) {
        var bases = [];
        var inner = q.match(/\(([^)]+)\)/g) || [];
        inner.forEach(function (p) { bases.push(p.slice(1, -1).trim()); });
        bases.push(q.replace(/\([^)]*\)/g, ' ').replace(/\s+/g, ' ').trim());
        var out = [];
        function add(v) {
            v = v.trim().replace(/^[,\s]+|[,\s]+$/g, '');
            if (v && out.indexOf(v) < 0) {
                out.push(v);
            }
        }
        add(q);
        bases.forEach(function (base) {
            add(base);
            var parts = base.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
            // Never down to a lone trailing part of a long address: that is usually the country.
            var last = parts.length >= 4 ? parts.length - 1 : parts.length;
            for (var i = 1; i < last; i++) {
                add(parts.slice(i).join(', '));
            }
        });
        return out.slice(0, 5);
    }

    /** Runs the Nominatim queries in order until one finds something. */
    function firstHit(attempts) {
        var i = 0;
        function next() {
            if (i >= attempts.length) {
                return Promise.resolve({ rows: [], query: null });
            }
            var params = attempts[i++];
            return nominatim(params).then(function (rows) {
                return rows.length ? { rows: rows, query: params.q } : next();
            });
        }
        return next();
    }

    function buildSearch(container, map, marker, writeInputs) {
        var form = container.closest('form');
        var street = formField(form, 'streetAddress');
        var municipality = formField(form, 'municipality');
        var prefecture = formField(form, 'prefecture');
        var countryField = formField(form, 'country');

        var box = document.createElement('div');
        box.className = 'sus-latlng-search';
        box.style.marginTop = '10px';
        box.innerHTML =
            '<div class="input-group">' +
            '<input type="text" class="form-control" placeholder="Οδός και αριθμός, π.χ. Πανεπιστημίου 30">' +
            '<span class="input-group-btn"><button type="button" class="btn btn-default">' +
            '<i class="fa fa-search"></i> Εύρεση στον χάρτη</button></span>' +
            '</div>' +
            '<p class="help-block sus-latlng-status" style="margin-bottom:0"></p>' +
            '<button type="button" class="btn btn-default btn-sm sus-latlng-fill" style="display:none;margin-top:5px">' +
            '<i class="fa fa-pencil"></i> Συμπλήρωση πεδίων διεύθυνσης από το αποτέλεσμα</button>' +
            '<p class="help-block sus-latlng-fill-status" style="margin-bottom:0"></p>' +
            '<div class="list-group sus-latlng-results" style="margin:5px 0 0"></div>';
        container.parentNode.insertBefore(box, container);

        var input = box.querySelector('input');
        var button = box.querySelector('button');
        var status = box.querySelector('.sus-latlng-status');
        var results = box.querySelector('.sus-latlng-results');
        var fillButton = box.querySelector('.sus-latlng-fill');
        var fillStatus = box.querySelector('.sus-latlng-fill-status');
        var chosen = null;

        fillButton.addEventListener('click', function () {
            if (!chosen || !chosen.address) {
                return;
            }
            var result = fillAddressFields(form, chosen.address);
            var sentence = function (text) { return /\.$/.test(text) ? text : text + '.'; };
            fillStatus.textContent = (result.filled.length ? sentence('Συμπληρώθηκαν: ' + result.filled.join(', ')) + ' ' : '')
                + (result.missed.length ? sentence('Χωρίς αντιστοιχία (ελέγξτε τα): ' + result.missed.join(', ')) : '');
        });

        // Track "Οδός, Αριθμός" until the user types their own query.
        var userEdited = false;
        function fillFromStreet() {
            if (!userEdited && street) {
                input.value = (street.value || '').trim();
            }
        }
        fillFromStreet();
        if (street) {
            street.addEventListener('input', fillFromStreet);
        }
        input.addEventListener('input', function () { userEdited = input.value.trim() !== ''; });

        function setStatus(text) {
            status.textContent = text;
        }

        function choose(row, item) {
            chosen = row;
            fillButton.style.display = row.address ? '' : 'none';
            fillStatus.textContent = '';
            var ll = L.latLng(parseFloat(row.lat), parseFloat(row.lon));
            marker.setLatLng(ll);
            map.setView(ll, 17);
            writeInputs(ll);
            Array.prototype.forEach.call(results.children, function (el) { el.classList.remove('active'); });
            if (item) {
                item.classList.add('active');
            }
        }

        function showResults(rows) {
            results.innerHTML = '';
            if (rows.length < 2) {
                return;
            }
            rows.forEach(function (row, i) {
                var item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item' + (i === 0 ? ' active' : '');
                item.style.padding = '5px 10px';
                item.textContent = row.display_name;
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    choose(row, item);
                });
                results.appendChild(item);
            });
        }

        function search() {
            var q = input.value.trim();
            var country = countryField && /^[A-Za-z]{2}$/.test(countryField.value) ? countryField.value.toLowerCase() : 'gr';
            var areaName = '';
            if (selectedText(municipality)) {
                areaName = 'Δήμος ' + selectedText(municipality);
            } else if (selectedText(prefecture)) {
                areaName = 'Περιφερειακή Ενότητα ' + selectedText(prefecture);
            }
            if (!q && !areaName) {
                setStatus('Συμπληρώστε τη διεύθυνση (ή επιλέξτε Δήμο) για αναζήτηση.');
                return;
            }

            button.disabled = true;
            results.innerHTML = '';
            chosen = null;
            fillButton.style.display = 'none';
            fillStatus.textContent = '';
            setStatus('Αναζήτηση…');

            var areaPlain = areaName.replace(/^(Δήμος|Περιφερειακή Ενότητα) /, '');
            var variants = queryVariants(q);
            var area = null;
            var areaPromise = areaName && country === 'gr' ? areaBounds(areaName, country) : Promise.resolve(null);
            areaPromise.then(function (found) {
                area = found;
                if (!q) {
                    return { rows: [] };
                }
                // Most precise first: inside the Δήμος, then the country, then anywhere; each
                // with the full text before the shortened variants (building name dropped, …).
                var attempts = [];
                if (area) {
                    variants.slice(0, 2).forEach(function (v) {
                        attempts.push({ q: v, countrycodes: country, viewbox: area.viewbox, bounded: 1 });
                    });
                }
                if (areaPlain && country === 'gr') {
                    attempts.push({ q: q + ', ' + areaPlain, countrycodes: country });
                }
                variants.forEach(function (v) {
                    attempts.push({ q: v, countrycodes: country });
                });
                attempts.push({ q: variants[Math.min(1, variants.length - 1)] }); // maybe the country is wrong
                return firstHit(attempts.slice(0, 7));
            }).then(function (hit) {
                var rows = hit.rows;
                if (rows.length) {
                    var approx = hit.query && hit.query !== q
                        ? ' (η πλήρης διεύθυνση δεν βρέθηκε· αναζητήθηκε «' + hit.query + '»)'
                        : '';
                    choose(rows[0]);
                    showResults(rows);
                    setStatus((rows.length > 1
                        ? 'Βρέθηκαν ' + rows.length + ' αποτελέσματα — επιλέξτε το σωστό ή μετακινήστε την πινέζα.'
                        : 'Βρέθηκε: ' + rows[0].display_name + ' — αν δεν είναι ακριβές, μετακινήστε την πινέζα.') + approx);
                } else if (area) {
                    map.fitBounds(area.bounds);
                    setStatus('Η διεύθυνση δεν βρέθηκε· ο χάρτης δείχνει τον Δήμο — κάντε κλικ στο σωστό σημείο.');
                } else {
                    setStatus('Δεν βρέθηκε. Δοκιμάστε διαφορετική διατύπωση ή κάντε κλικ στον χάρτη.');
                }
            }).catch(function () {
                setStatus('Η αναζήτηση δεν είναι διαθέσιμη αυτή τη στιγμή — κάντε κλικ στον χάρτη.');
            }).then(function () {
                button.disabled = false;
            });
        }

        button.addEventListener('click', search);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // don't submit the unit form
                search();
            }
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

        buildSearch(container, map, marker, writeInputs);

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
