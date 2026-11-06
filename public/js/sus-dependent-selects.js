/**
 * Dependent dropdowns for the Unit admin form: choosing a parent narrows its children, all the
 * way down, so Περιφέρεια alone already narrows Δήμος even with no Περιφερειακή Ενότητα chosen.
 *
 * Wiring comes from data-* attributes set in UnitAdmin:
 *   data-sus-dep-key     this field's name
 *   data-sus-dep-parent  the key of the field it depends on
 *   data-sus-dep-map     JSON {child option value: parent option value}
 *
 * Rules:
 *  - an option missing from the map is always offered (never hide what we know nothing about);
 *  - no parent chosen and no ancestor chosen means everything is offered;
 *  - a value saved on the unit stays selected on load even if it does not fit its parent, so
 *    opening and saving a unit never blanks it; it is dropped once the user changes a parent.
 */
(function () {
    'use strict';

    function refreshWidget(field) {
        if (window.jQuery) {
            window.jQuery(field.select).trigger('change.select2');
        }
    }

    function allowedValues(field, fields) {
        var parent = fields[field.parentKey];
        if (!parent) {
            return null;
        }

        var allowedParents;
        if (parent.select.value !== '') {
            allowedParents = new Set([parent.select.value]);
        } else {
            allowedParents = allowedValues(parent, fields);
        }
        if (allowedParents === null) {
            return null;
        }

        var allowed = new Set();
        Object.keys(field.map).forEach(function (child) {
            if (allowedParents.has(String(field.map[child]))) {
                allowed.add(child);
            }
        });

        return allowed;
    }

    function applyFilter(field, fields) {
        var allowed = allowedValues(field, fields);
        var current = field.select.value;

        var visible = field.options.filter(function (option) {
            return option.value === ''
                || !(option.value in field.map)
                || allowed === null
                || allowed.has(option.value)
                || option.value === field.keptValue;
        });

        field.select.replaceChildren.apply(field.select, visible);
        var stillOffered = visible.some(function (option) {
            return option.value === current;
        });
        field.select.value = stillOffered ? current : '';
        refreshWidget(field);
    }

    function descendantsOf(key, fields) {
        var found = [];
        Object.keys(fields).forEach(function (candidate) {
            if (fields[candidate].parentKey === key) {
                found.push(fields[candidate]);
                found = found.concat(descendantsOf(candidate, fields));
            }
        });

        return found;
    }

    function onParentChanged(key, fields) {
        descendantsOf(key, fields).forEach(function (field) {
            field.keptValue = null;
            applyFilter(field, fields);
        });
    }

    function init() {
        var fields = {};
        document.querySelectorAll('select[data-sus-dep-key]').forEach(function (select) {
            fields[select.dataset.susDepKey] = {
                select: select,
                parentKey: select.dataset.susDepParent || null,
                map: select.dataset.susDepMap ? JSON.parse(select.dataset.susDepMap) : {},
                options: Array.prototype.slice.call(select.options),
                keptValue: select.value || null
            };
        });

        Object.keys(fields).forEach(function (key) {
            var field = fields[key];
            var listener = function () {
                onParentChanged(key, fields);
            };
            // select2 reports changes through jQuery only, so a native listener would miss them.
            if (window.jQuery) {
                window.jQuery(field.select).on('change', listener);
            } else {
                field.select.addEventListener('change', listener);
            }
        });

        Object.keys(fields).forEach(function (key) {
            if (fields[key].parentKey) {
                applyFilter(fields[key], fields);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
