(function () {
    'use strict';

    var config = window.bwSearchSurfaceConfig || {};
    var strings = config.strings || {};
    var sidebarGroups = config.sidebarGroups || {};
    var scopeOptions = config.scopeOptions || {};
    var groupIcons = config.groupIcons || {};
    var openSurface = null;

    function getSearchResultsUrl(query, scope) {
        var baseUrl = typeof config.searchResultsUrl === 'string' && config.searchResultsUrl ? config.searchResultsUrl : '/search/';
        var url = new URL(baseUrl, window.location.origin);

        url.searchParams.set('scope', scope || 'all');

        if (query) {
            url.searchParams.set('q', query);
        }

        return url.toString();
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            switch (char) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case '"':
                    return '&quot;';
                case '\'':
                    return '&#039;';
                default:
                    return char;
            }
        });
    }

    function getScopeGroups(scope) {
        return sidebarGroups[scope] || sidebarGroups.all || [];
    }

    function getScopeLabel(scope) {
        return scopeOptions[scope] || scopeOptions.all || 'All';
    }

    function moveSurfaceToBody(surface) {
        if (!surface || !surface.parentNode || surface.parentNode === document.body) {
            return;
        }

        document.body.appendChild(surface);
    }

    function syncLayoutMode(surfaceState) {
        if (!surfaceState || !surfaceState.surface) {
            return;
        }

        surfaceState.surface.classList.toggle('is-query-active', !!surfaceState.query);
    }

    function renderSidebar(surfaceState) {
        var groups = getScopeGroups(surfaceState.scope);
        var html = groups.map(function (group, index) {
            var isActive = surfaceState.activeGroup === group.key || (!surfaceState.activeGroup && index === 0);

            return (
                '<button class="bw-search-surface__nav-item' + (isActive ? ' is-active' : '') + '" type="button" data-bw-search-group="' + escapeHtml(group.key) + '">' +
                    '<span class="bw-search-surface__nav-icon" aria-hidden="true">' + (groupIcons[group.key] || '') + '</span>' +
                    '<span class="bw-search-surface__nav-label">' + escapeHtml(group.label) + '</span>' +
                '</button>'
            );
        }).join('');

        surfaceState.sidebar.innerHTML = html;
    }

    function ensureScopeIndicator(surfaceState) {
        var indicator;

        if (!surfaceState || !surfaceState.scopeRow) {
            return null;
        }

        indicator = surfaceState.scopeRow.querySelector('[data-bw-search-scope-indicator]');

        if (indicator) {
            return indicator;
        }

        indicator = document.createElement('span');
        indicator.className = 'bw-search-surface__scope-indicator';
        indicator.setAttribute('data-bw-search-scope-indicator', '');
        indicator.setAttribute('aria-hidden', 'true');

        surfaceState.scopeRow.insertBefore(indicator, surfaceState.scopeRow.firstChild);

        return indicator;
    }

    function updateScopeIndicator(surfaceState) {
        var indicator;
        var selected;
        var rowRect;
        var selectedRect;

        if (!surfaceState || !surfaceState.scopeRow) {
            return;
        }

        indicator = surfaceState.scopeIndicator || ensureScopeIndicator(surfaceState);

        if (!indicator) {
            return;
        }

        selected = surfaceState.scopeRow.querySelector('.bw-search-surface__scope-option.is-selected');

        if (!selected) {
            selected = surfaceState.scopeRow.querySelector('[data-bw-scope-option="' + surfaceState.scope + '"]');
        }

        if (!selected) {
            selected = surfaceState.scopeRow.querySelector('[data-bw-scope-option]');
        }

        if (!selected) {
            return;
        }

        rowRect = surfaceState.scopeRow.getBoundingClientRect();
        selectedRect = selected.getBoundingClientRect();

        indicator.style.width = selectedRect.width + 'px';
        indicator.style.height = selectedRect.height + 'px';
        indicator.style.transform = 'translate3d(' + (selectedRect.left - rowRect.left) + 'px, ' + (selectedRect.top - rowRect.top) + 'px, 0)';
        indicator.classList.add('is-visible');
    }

    function scheduleScopeIndicatorUpdate(surfaceState) {
        if (!surfaceState) {
            return;
        }

        if (surfaceState.scopeIndicatorFrame) {
            window.cancelAnimationFrame(surfaceState.scopeIndicatorFrame);
        }

        surfaceState.scopeIndicatorFrame = window.requestAnimationFrame(function () {
            surfaceState.scopeIndicatorFrame = null;
            updateScopeIndicator(surfaceState);
        });
    }

    function setScope(surfaceState, scope) {
        surfaceState.scope = scope in scopeOptions ? scope : 'all';
        surfaceState.filterSel = { subcategories: [], tags: [], year: { from: null, to: null }, advanced: {} };
        if (surfaceState.scopeInput) {
            surfaceState.scopeInput.value = surfaceState.scope;
        }

        if (surfaceState.scopeRow) {
            Array.prototype.forEach.call(surfaceState.scopeRow.querySelectorAll('[data-bw-scope-option]'), function (button) {
                var selected = button.getAttribute('data-bw-scope-option') === surfaceState.scope;
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
        }

        renderSidebar(surfaceState);
        scheduleScopeIndicatorUpdate(surfaceState);

        if (surfaceState.query) {
            requestSuggest(surfaceState);
            return;
        }

        requestMode(surfaceState, surfaceState.mode);
    }

    function openSurfaceDialog(surfaceState) {
        if (openSurface && openSurface !== surfaceState) {
            closeSurfaceDialog(openSurface);
        }

        surfaceState.surface.classList.add('is-open');
        document.body.classList.add('bw-search-overlay-active');
        openSurface = surfaceState;

        window.setTimeout(function () {
            surfaceState.input.focus();
        }, 40);

        if (!surfaceState.query) {
            requestMode(surfaceState, surfaceState.mode);
        }

        scheduleScopeIndicatorUpdate(surfaceState);
    }

    function closeSurfaceDialog(surfaceState) {
        if (surfaceState.abortController) {
            surfaceState.abortController.abort();
            surfaceState.abortController = null;
        }

        window.clearTimeout(surfaceState.filterCountTimer);
        if (surfaceState.filterCountAbortController) {
            surfaceState.filterCountAbortController.abort();
            surfaceState.filterCountAbortController = null;
        }

        window.clearTimeout(surfaceState.debounceTimer);
        surfaceState.surface.classList.remove('is-open');
        surfaceState.query = '';
        surfaceState.activeGroup = 'trending';
        surfaceState.mode = 'trending';
        surfaceState.input.value = '';

        if (surfaceState.filterFooter) { surfaceState.filterFooter.hidden = true; }

        syncLayoutMode(surfaceState);
        document.body.classList.remove('bw-search-overlay-active');

        if (openSurface === surfaceState) {
            openSurface = null;
        }
    }

    function setContentHeader(surfaceState, text) {
        if (!surfaceState.contentHeader) {
            return;
        }

        if (text) {
            surfaceState.contentHeader.textContent = text;
            surfaceState.contentHeader.hidden = false;
        } else {
            surfaceState.contentHeader.hidden = true;
        }
    }

    function setLoadingState(surfaceState) {
        setContentHeader(surfaceState, '');
        surfaceState.content.innerHTML = '<div class="bw-search-surface__empty">' + escapeHtml(strings.loading || 'Loading…') + '</div>';
    }

    function renderFeed(surfaceState, mode, payload) {
        var items = (payload && Array.isArray(payload.items)) ? payload.items : [];
        var modeLabels = {
            trending: strings.modeLabelTrending || 'Trending',
            new:      strings.modeLabelNew      || 'New Arrivals',
            sale:     strings.modeLabelSale     || 'On Sale',
            free:     strings.modeLabelFree     || 'Free Downloads'
        };

        surfaceState.mode = mode;
        surfaceState.activeGroup = mode;
        syncLayoutMode(surfaceState);
        renderSidebar(surfaceState);
        setContentHeader(surfaceState, modeLabels[mode] || mode);

        if (surfaceState.filterFooter) {
            surfaceState.filterFooter.hidden = true;
        }

        if (!items.length) {
            surfaceState.content.innerHTML =
                '<div class="bw-search-surface__empty">' +
                escapeHtml(strings.emptyFeed || 'No products available right now.') +
                '</div>';
            return;
        }

        var html = items.map(function (item) {
            var imageHtml = item.image_url
                ? '<div class="bw-search-surface__feed-image"><img src="' + escapeHtml(item.image_url) +
                  '" alt="' + escapeHtml(item.title) + '" loading="lazy"></div>'
                : '<div class="bw-search-surface__feed-image bw-search-surface__feed-image--empty"></div>';

            var priceHtml = item.price_html
                ? '<span class="bw-search-surface__feed-price">' + item.price_html + '</span>'
                : '';

            return (
                '<a class="bw-search-surface__feed-card" href="' + escapeHtml(item.permalink) + '">' +
                    imageHtml +
                    '<div class="bw-search-surface__feed-copy">' +
                        '<span class="bw-search-surface__feed-title">' + escapeHtml(item.title) + '</span>' +
                        priceHtml +
                    '</div>' +
                '</a>'
            );
        }).join('');

        surfaceState.content.innerHTML = '<div class="bw-search-surface__feed-grid">' + html + '</div>';
    }

    // ─── Filter helpers ──────────────────────────────────────────────────────

    function toggleInArray(arr, val) {
        var idx = arr.indexOf(val);
        if (idx !== -1) { arr.splice(idx, 1); } else { arr.push(val); }
    }

    function findInItems(items, id) {
        var strId = String(id);
        for (var i = 0; i < items.length; i++) {
            var itemId = String(items[i].id || items[i].term_id || '');
            if (itemId === strId) { return items[i]; }
            if (Array.isArray(items[i].children)) {
                var found = findInItems(items[i].children, id);
                if (found) { return found; }
            }
        }
        return null;
    }

    function getOpenFilterGroups(surfaceState) {
        var open = [];
        if (!surfaceState.content) { return open; }
        Array.prototype.forEach.call(surfaceState.content.querySelectorAll('[data-bw-filter-group]'), function (group) {
            if (group.classList.contains('is-open')) { open.push(group.getAttribute('data-bw-filter-group')); }
        });
        return open;
    }

    function restoreOpenFilterGroups(surfaceState, openGroups) {
        if (!openGroups || !openGroups.length || !surfaceState.content) { return; }
        Array.prototype.forEach.call(surfaceState.content.querySelectorAll('[data-bw-filter-group]'), function (group) {
            if (openGroups.indexOf(group.getAttribute('data-bw-filter-group')) !== -1) {
                group.classList.add('is-open');
                var panel = group.querySelector('.bw-search-surface__filter-group-panel');
                if (panel) { panel.hidden = false; }
            }
        });
    }

    function updateFilterCount(surfaceState, count) {
        if (!surfaceState.filterCount) { return; }
        var template = strings.filterResultCount || '%d results';
        surfaceState.filterCount.textContent = template.replace('%d', String(count !== undefined ? count : 0));
    }

    function parseNullableYear(value) {
        var year = parseInt(value, 10);

        return isFinite(year) ? year : null;
    }

    function normalizeYearRange(from, to, min, max) {
        var normalizedFrom = parseNullableYear(from);
        var normalizedTo   = parseNullableYear(to);

        if (min !== null && normalizedFrom !== null) {
            normalizedFrom = Math.max(normalizedFrom, min);
        }

        if (max !== null && normalizedFrom !== null) {
            normalizedFrom = Math.min(normalizedFrom, max);
        }

        if (min !== null && normalizedTo !== null) {
            normalizedTo = Math.max(normalizedTo, min);
        }

        if (max !== null && normalizedTo !== null) {
            normalizedTo = Math.min(normalizedTo, max);
        }

        if (normalizedFrom !== null && normalizedTo !== null && normalizedFrom > normalizedTo) {
            var swap = normalizedFrom;
            normalizedFrom = normalizedTo;
            normalizedTo = swap;
        }

        return {
            from: normalizedFrom,
            to: normalizedTo
        };
    }

    function getYearRangeLabel(yearState) {
        if (!yearState) {
            return '';
        }

        if (yearState.from === null && yearState.to === null) {
            return strings.filterYearAny || 'Any year';
        }

        return String(yearState.from !== null && yearState.from !== undefined ? yearState.from : (strings.filterYearAny || 'Any')) +
            '–' +
            String(yearState.to !== null && yearState.to !== undefined ? yearState.to : (strings.filterYearAny || 'Any'));
    }

    function getPopupYearBounds(surfaceState) {
        var filterUi = (surfaceState && surfaceState.filterData && surfaceState.filterData.filter_ui) ? surfaceState.filterData.filter_ui : {};
        var year = (filterUi.year && filterUi.year.supported) ? filterUi.year : {};
        var min = parseNullableYear(year.min);
        var max = parseNullableYear(year.max);

        if (min === null) {
            min = 1900;
        }

        if (max === null) {
            max = new Date().getFullYear();
        }

        return {
            min: min,
            max: max
        };
    }

    function getResolvedYearDraft(surfaceState) {
        var bounds = getPopupYearBounds(surfaceState);
        var yearSel = surfaceState && surfaceState.filterSel && surfaceState.filterSel.year ? surfaceState.filterSel.year : {};
        var normalized = normalizeYearRange(yearSel.from, yearSel.to, bounds.min, bounds.max);

        return {
            from: normalized.from !== null ? normalized.from : bounds.min,
            to: normalized.to !== null ? normalized.to : bounds.max,
            min: bounds.min,
            max: bounds.max
        };
    }

    function buildYearSliderTrackStyle(resolvedDraft) {
        if (!resolvedDraft || resolvedDraft.min === null || resolvedDraft.max === null || resolvedDraft.max <= resolvedDraft.min) {
            return '';
        }

        var span = resolvedDraft.max - resolvedDraft.min;
        var start = Math.max(0, ((resolvedDraft.from - resolvedDraft.min) / span) * 100);
        var end = Math.max(start, ((resolvedDraft.to - resolvedDraft.min) / span) * 100);

        return 'left:' + start + '%;width:' + Math.max(0, end - start) + '%;';
    }

    function updatePopupYearPresentation(surfaceState) {
        var resolvedDraft = getResolvedYearDraft(surfaceState);
        var fromSlider = surfaceState.content.querySelector('[data-bw-year-edge="from"]');
        var toSlider = surfaceState.content.querySelector('[data-bw-year-edge="to"]');
        var fromInput = surfaceState.content.querySelector('[data-bw-year-from]');
        var toInput = surfaceState.content.querySelector('[data-bw-year-to]');
        var activeTrack = surfaceState.content.querySelector('[data-bw-year-active-track]');

        if (activeTrack) {
            activeTrack.setAttribute('style', buildYearSliderTrackStyle(resolvedDraft));
        }

        if (fromSlider) {
            fromSlider.value = String(resolvedDraft.from);
        }

        if (toSlider) {
            toSlider.value = String(resolvedDraft.to);
        }

        if (fromInput) {
            fromInput.value = surfaceState.filterSel.year.from !== null && surfaceState.filterSel.year.from !== undefined
                ? String(surfaceState.filterSel.year.from)
                : '';
        }

        if (toInput) {
            toInput.value = surfaceState.filterSel.year.to !== null && surfaceState.filterSel.year.to !== undefined
                ? String(surfaceState.filterSel.year.to)
                : '';
        }
    }

    function scheduleFilterCount(surfaceState) {
        window.clearTimeout(surfaceState.filterCountTimer);
        surfaceState.filterCountTimer = window.setTimeout(function () {
            if (surfaceState.filterCountAbortController) { surfaceState.filterCountAbortController.abort(); }
            surfaceState.filterCountAbortController = new AbortController();

            var sel = surfaceState.filterSel;
            var postParams = new URLSearchParams();
            postParams.set('action', 'bw_ss_overlay_payload');
            postParams.set('nonce', config.nonce || '');
            postParams.set('mode', 'filter_count');
            postParams.set('scope', surfaceState.scope);
            if (sel.subcategories && sel.subcategories.length) { postParams.set('subcategories', sel.subcategories.join(',')); }
            if (sel.tags && sel.tags.length)                   { postParams.set('tags', sel.tags.join(',')); }
            if (sel.year && sel.year.from)                     { postParams.set('year_from', sel.year.from); }
            if (sel.year && sel.year.to)                       { postParams.set('year_to', sel.year.to); }
            if (sel.advanced) {
                Object.keys(sel.advanced).forEach(function (k) {
                    if (sel.advanced[k] && sel.advanced[k].length) { postParams.set(k, sel.advanced[k].join(',')); }
                });
            }

            window.fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: postParams.toString(),
                signal: surfaceState.filterCountAbortController.signal
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data && data.success && data.data) { updateFilterCount(surfaceState, data.data.count); }
            }).catch(function () {});
        }, 300);
    }

    function buildFilterNavUrl(surfaceState) {
        var baseUrl = typeof config.searchResultsUrl === 'string' && config.searchResultsUrl ? config.searchResultsUrl : '/search/';
        var url = new URL(baseUrl, window.location.origin);
        var sel = surfaceState.filterSel;

        url.searchParams.set('scope', surfaceState.scope || 'all');
        (sel.subcategories || []).forEach(function (id) { url.searchParams.append('subcategories[]', id); });
        (sel.tags || []).forEach(function (id) { url.searchParams.append('tag[]', id); });
        if (sel.year && sel.year.from) { url.searchParams.set('year_from', sel.year.from); }
        if (sel.year && sel.year.to)   { url.searchParams.set('year_to', sel.year.to); }
        if (sel.advanced) {
            Object.keys(sel.advanced).forEach(function (k) {
                if (sel.advanced[k] && sel.advanced[k].length) { url.searchParams.set(k, sel.advanced[k].join(',')); }
            });
        }
        return url.toString();
    }

    function renderFilterChipsHtml(surfaceState) {
        var sel      = surfaceState.filterSel;
        var filterUi = (surfaceState.filterData && surfaceState.filterData.filter_ui) ? surfaceState.filterData.filter_ui : {};
        var types    = Array.isArray(filterUi.types) ? filterUi.types : [];
        var tags     = Array.isArray(filterUi.tags)  ? filterUi.tags  : [];
        var chips    = [];
        var closeIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';

        (sel.subcategories || []).forEach(function (id) {
            var item = findInItems(types, id);
            chips.push({ type: 'subcategory', id: id, label: item ? (item.label || item.name || String(id)) : String(id) });
        });
        (sel.tags || []).forEach(function (id) {
            var item = findInItems(tags, id);
            chips.push({ type: 'tag', id: id, label: item ? (item.label || item.name || String(id)) : String(id) });
        });
        if (sel.year && (sel.year.from !== null && sel.year.from !== undefined || sel.year.to !== null && sel.year.to !== undefined)) {
            chips.push({ type: 'year', label: getYearRangeLabel(normalizeYearRange(sel.year.from, sel.year.to, null, null)) });
        }
        if (sel.advanced) {
            var advUi = (filterUi.advanced && typeof filterUi.advanced === 'object') ? filterUi.advanced : {};
            Object.keys(sel.advanced).forEach(function (key) {
                var opts = (advUi[key] && Array.isArray(advUi[key].options)) ? advUi[key].options : [];
                (sel.advanced[key] || []).forEach(function (val) {
                    var opt   = opts.filter(function (o) { return String(o.value) === String(val); })[0];
                    var label = opt ? (opt.name || String(val)) : String(val);
                    chips.push({ type: 'advanced', key: key, slug: val, label: label });
                });
            });
        }

        if (!chips.length) { return ''; }

        var html = '<div class="bw-search-surface__filter-chips">';
        chips.forEach(function (chip) {
            var attrs = '';
            if (chip.type === 'subcategory') {
                attrs = 'data-bw-chip-type="subcategory" data-bw-chip-id="' + escapeHtml(String(chip.id)) + '"';
            } else if (chip.type === 'tag') {
                attrs = 'data-bw-chip-type="tag" data-bw-chip-id="' + escapeHtml(String(chip.id)) + '"';
            } else if (chip.type === 'year') {
                attrs = 'data-bw-chip-type="year"';
            } else {
                attrs = 'data-bw-chip-type="advanced" data-bw-chip-key="' + escapeHtml(chip.key) + '" data-bw-chip-slug="' + escapeHtml(chip.slug) + '"';
            }
            html +=
                '<span class="bw-search-surface__filter-chip">' +
                    '<span class="bw-search-surface__filter-chip-label">' + escapeHtml(chip.label) + '</span>' +
                    '<button type="button" class="bw-search-surface__filter-chip-remove" aria-label="Remove filter" ' + attrs + '>' + closeIcon + '</button>' +
                '</span>';
        });
        html += '</div>';
        return html;
    }

    function updateFilterChips(surfaceState) {
        var main  = surfaceState && surfaceState.content ? surfaceState.content.parentNode : null;
        var chips = main ? main.querySelector(':scope > .bw-search-surface__filter-chips') : null;
        var html  = renderFilterChipsHtml(surfaceState);

        if (!html) {
            if (chips) { chips.parentNode.removeChild(chips); }
            if (main) { main.classList.remove('has-chips'); }
            return;
        }

        if (chips) {
            chips.outerHTML = html;
        } else if (surfaceState.content) {
            surfaceState.content.insertAdjacentHTML('beforebegin', html);
        }
        if (main) { main.classList.add('has-chips'); }
    }

    function renderFilterGroupHtml(groupType, label, items, selectedList, idField) {
        var selStrs   = (selectedList || []).map(function (v) { return String(v); });
        var chevron   = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
        var tick      = '<svg class="bw-search-surface__filter-option-tick" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 8l3.5 3.5L13 4"/></svg>';

        var optionsHtml = items.map(function (item) {
            var rawId    = idField ? (item[idField] !== undefined ? item[idField] : (item.id || item.term_id || item.slug || '')) : (item.id || item.term_id || item.slug || '');
            var itemId   = String(rawId);
            var isSelected = selStrs.indexOf(itemId) !== -1;
            var itemLabel  = item.label || item.name || itemId;
            var count      = item.count !== undefined && item.count !== null
                ? ' <span class="bw-search-surface__filter-option-count">' + escapeHtml(String(item.count)) + '</span>'
                : '';
            return (
                '<button type="button" class="bw-search-surface__filter-option' + (isSelected ? ' is-selected' : '') + '" ' +
                    'data-bw-filter-option="' + escapeHtml(groupType) + '" data-bw-option-id="' + escapeHtml(itemId) + '">' +
                    '<span class="bw-search-surface__filter-option-check" aria-hidden="true">' + tick + '</span>' +
                    '<span class="bw-search-surface__filter-option-label">' + escapeHtml(itemLabel) + '</span>' +
                    count +
                '</button>'
            );
        }).join('');

        return (
            '<div class="bw-search-surface__filter-group" data-bw-filter-group="' + escapeHtml(groupType) + '">' +
                '<button type="button" class="bw-search-surface__filter-group-toggle" data-bw-filter-toggle>' +
                    '<span class="bw-search-surface__filter-group-label">' + escapeHtml(label) + '</span>' +
                    '<span class="bw-search-surface__filter-group-chevron" aria-hidden="true">' + chevron + '</span>' +
                '</button>' +
                '<div class="bw-search-surface__filter-group-panel" hidden>' +
                    '<div class="bw-search-surface__filter-options">' + optionsHtml + '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function renderFilterYearHtml(year, yearSel) {
        var min     = parseInt(year.min, 10)  || 1900;
        var max     = parseInt(year.max, 10)  || new Date().getFullYear();
        var fromVal = yearSel && yearSel.from ? String(yearSel.from) : '';
        var toVal   = yearSel && yearSel.to   ? String(yearSel.to)   : '';
        var chevron = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
        var resolvedDraft = getResolvedYearDraft({
            filterData: { filter_ui: { year: year } },
            filterSel: { year: yearSel || { from: null, to: null } }
        });
        var selectionLabel = getYearRangeLabel(normalizeYearRange(resolvedDraft.from, resolvedDraft.to, resolvedDraft.min, resolvedDraft.max));

        return (
            '<div class="bw-search-surface__filter-group" data-bw-filter-group="year">' +
                '<button type="button" class="bw-search-surface__filter-group-toggle" data-bw-filter-toggle>' +
                    '<span class="bw-search-surface__filter-group-label">' + escapeHtml(strings.filterGroupYear || 'Year') + '</span>' +
                    '<span class="bw-search-surface__filter-group-chevron" aria-hidden="true">' + chevron + '</span>' +
                '</button>' +
                '<div class="bw-search-surface__filter-group-panel" hidden>' +
                    '<div class="bw-search-surface__year-slider" data-bw-year-slider>' +
                        '<div class="bw-search-surface__year-slider-topline">' +
                            '<span class="bw-search-surface__year-slider-bound">' + escapeHtml(min) + '</span>' +
                            '<span class="bw-search-surface__year-slider-bound">' + escapeHtml(max) + '</span>' +
                        '</div>' +
                        '<div class="bw-search-surface__year-slider-range">' +
                            '<div class="bw-search-surface__year-slider-track"></div>' +
                            '<div class="bw-search-surface__year-slider-active" data-bw-year-active-track style="' + buildYearSliderTrackStyle(resolvedDraft) + '"></div>' +
                            '<input type="range" class="bw-search-surface__year-slider-input bw-search-surface__year-slider-input--from" data-bw-year-edge="from" min="' + min + '" max="' + max + '" value="' + escapeHtml(resolvedDraft.from) + '" aria-label="' + escapeHtml(strings.filterYearFrom || 'From') + '">' +
                            '<input type="range" class="bw-search-surface__year-slider-input bw-search-surface__year-slider-input--to" data-bw-year-edge="to" min="' + min + '" max="' + max + '" value="' + escapeHtml(resolvedDraft.to) + '" aria-label="' + escapeHtml(strings.filterYearTo || 'To') + '">' +
                        '</div>' +
                    '</div>' +
                    '<div class="bw-search-surface__year-fields">' +
                        '<label class="bw-search-surface__year-field">' +
                            '<span class="bw-search-surface__year-field-label">' + escapeHtml(strings.filterYearFrom || 'From') + '</span>' +
                            '<input type="number" class="bw-search-surface__year-input" data-bw-year-from' +
                                ' min="' + min + '" max="' + max + '" value="' + escapeHtml(fromVal) + '" placeholder="' + min + '">' +
                        '</label>' +
                        '<span class="bw-search-surface__year-sep" aria-hidden="true">–</span>' +
                        '<label class="bw-search-surface__year-field">' +
                            '<span class="bw-search-surface__year-field-label">' + escapeHtml(strings.filterYearTo || 'To') + '</span>' +
                            '<input type="number" class="bw-search-surface__year-input" data-bw-year-to' +
                                ' min="' + min + '" max="' + max + '" value="' + escapeHtml(toVal) + '" placeholder="' + max + '">' +
                        '</label>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function renderFilter(surfaceState, payload) {
        var openGroups = getOpenFilterGroups(surfaceState);

        surfaceState.mode = 'filter';
        surfaceState.activeGroup = 'filter';
        surfaceState.filterData = payload || {};
        syncLayoutMode(surfaceState);
        renderSidebar(surfaceState);
        setContentHeader(surfaceState, '');

        if (surfaceState.filterFooter) { surfaceState.filterFooter.hidden = false; }

        var filterUi = (payload && payload.filter_ui) ? payload.filter_ui : {};
        var types    = Array.isArray(filterUi.types) ? filterUi.types : [];
        var tags     = Array.isArray(filterUi.tags)  ? filterUi.tags  : [];
        var year     = (filterUi.year && filterUi.year.supported) ? filterUi.year : null;
        var advanced = (filterUi.advanced && typeof filterUi.advanced === 'object') ? filterUi.advanced : {};
        var advKeys  = Object.keys(advanced).filter(function (k) { return advanced[k] && advanced[k].supported && Array.isArray(advanced[k].options) && advanced[k].options.length; });

        var initialCount = filterUi.result_count !== undefined ? filterUi.result_count : (payload && payload.result_count !== undefined ? payload.result_count : 0);
        updateFilterCount(surfaceState, initialCount);

        if (!types.length && !tags.length && !year && !advKeys.length) {
            surfaceState.content.innerHTML =
                '<div class="bw-search-surface__empty">' +
                escapeHtml(strings.filterEmpty || 'No filters available for this scope.') +
                '</div>';
            return;
        }

        var html = renderFilterChipsHtml(surfaceState);
        html += '<div class="bw-search-surface__filter-panel">';

        if (types.length) {
            html += renderFilterGroupHtml('subcategory', strings.filterGroupCategories || 'Categories', types, surfaceState.filterSel.subcategories, 'id');
        }
        if (tags.length) {
            html += renderFilterGroupHtml('tag', strings.filterGroupTags || 'Style / Subject', tags, surfaceState.filterSel.tags, 'id');
        }
        if (year) {
            html += renderFilterYearHtml(year, surfaceState.filterSel.year);
        }
        advKeys.forEach(function (key) {
            var advLabel = key.charAt(0).toUpperCase() + key.slice(1);
            html += renderFilterGroupHtml('advanced_' + key, advLabel, advanced[key].options, (surfaceState.filterSel.advanced || {})[key] || [], 'value');
        });

        html += '</div>';
        surfaceState.content.innerHTML = html;
        restoreOpenFilterGroups(surfaceState, openGroups);
    }

    function renderSuggest(surfaceState, payload) {
        var items = payload.items || [];
        var query = surfaceState.query;
        var searchUrl = payload.search_url || getSearchResultsUrl(query, surfaceState.scope);
        var actionLabel = (strings.searchActionLabel || 'Search for') + ' "' + query + '"';
        var actionRow =
            '<a class="bw-search-surface__action-row" href="' + escapeHtml(searchUrl) + '" data-bw-search-action-link>' +
                '<span class="bw-search-surface__action-icon" aria-hidden="true">' +
                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7"></circle><path d="M20 20L16.65 16.65"></path></svg>' +
                '</span>' +
                '<span class="bw-search-surface__row-body">' +
                    '<span class="bw-search-surface__row-title-text">' + escapeHtml(actionLabel) + '</span>' +
                    '<span class="bw-search-surface__row-meta">' + escapeHtml(getScopeLabel(surfaceState.scope)) + '</span>' +
                '</span>' +
                '<span class="bw-search-surface__row-action">' + escapeHtml(strings.searchActionHint || 'Enter') + '</span>' +
            '</a>';

        surfaceState.mode = 'suggest';
        if (surfaceState.filterFooter) { surfaceState.filterFooter.hidden = true; }
        syncLayoutMode(surfaceState);
        setContentHeader(surfaceState, '');

        if (!items.length) {
            surfaceState.content.innerHTML =
                '<div class="bw-search-surface__row-group">' + actionRow + '</div>' +
                '<div class="bw-search-surface__empty">' + escapeHtml(strings.emptySuggestions || 'No matching products found.') + '</div>';
            return;
        }

        var suggestRows = items.map(function (item) {
            var imageHtml = item.image_url
                ? '<div class="bw-search-surface__suggestion-media"><img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.title) + '" loading="lazy"></div>'
                : '<div class="bw-search-surface__suggestion-media"></div>';

            return (
                '<a class="bw-search-surface__suggestion-row" href="' + escapeHtml(item.permalink) + '">' +
                    imageHtml +
                    '<span class="bw-search-surface__row-body">' +
                        '<span class="bw-search-surface__row-title-text">' + escapeHtml(item.title) + '</span>' +
                        '<span class="bw-search-surface__row-meta">' + escapeHtml(item.description || '') + '</span>' +
                    '</span>' +
                '</a>'
            );
        }).join('');

        var hasMoreFooter = payload.has_more
            ? '<a class="bw-search-surface__see-all" href="' + escapeHtml(searchUrl) + '">' +
              escapeHtml(strings.seeAllResults || 'See all results') + '</a>'
            : '';

        surfaceState.content.innerHTML =
            '<div class="bw-search-surface__row-group">' + actionRow + suggestRows + '</div>' +
            hasMoreFooter;
    }

    function requestMode(surfaceState, mode) {
        mode = (typeof mode === 'string' && mode) ? mode : 'trending';
        setLoadingState(surfaceState);

        requestPayload(surfaceState, mode, {}).then(function (response) {
            if (!response || !response.success || !response.data) {
                if ('filter' === mode) {
                    renderFilter(surfaceState, {});
                } else {
                    renderFeed(surfaceState, mode, { items: [] });
                }

                return;
            }

            if ('filter' === mode) {
                renderFilter(surfaceState, response.data);
            } else {
                renderFeed(surfaceState, mode, response.data);
            }
        }).catch(function (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            if ('filter' === mode) {
                renderFilter(surfaceState, {});
            } else {
                renderFeed(surfaceState, mode, { items: [] });
            }
        });
    }

    function requestPayload(surfaceState, mode, extra) {
        var params = new URLSearchParams();
        var requestOptions = extra || {};

        if (surfaceState.abortController) {
            surfaceState.abortController.abort();
        }

        surfaceState.abortController = new AbortController();
        params.set('action', 'bw_ss_overlay_payload');
        params.set('nonce', config.nonce || '');
        params.set('mode', mode);
        params.set('scope', surfaceState.scope);
        params.set('query', requestOptions.query || surfaceState.query || '');
        params.set('group', requestOptions.group || '');

        return window.fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: params.toString(),
            signal: surfaceState.abortController.signal
        }).then(function (response) {
            return response.json();
        });
    }

    function requestSuggest(surfaceState) {
        setLoadingState(surfaceState);

        requestPayload(surfaceState, 'suggest').then(function (response) {
            if (!response || !response.success || !response.data) {
                renderSuggest(surfaceState, { items: [], search_url: getSearchResultsUrl(surfaceState.query, surfaceState.scope) });
                return;
            }

            renderSuggest(surfaceState, response.data);
        }).catch(function (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            renderSuggest(surfaceState, { items: [], search_url: getSearchResultsUrl(surfaceState.query, surfaceState.scope) });
        });
    }

    function handleInput(surfaceState) {
        surfaceState.query = surfaceState.input.value.trim();
        syncLayoutMode(surfaceState);

        window.clearTimeout(surfaceState.debounceTimer);

        if (!surfaceState.query) {
            requestMode(surfaceState, surfaceState.mode === 'suggest' ? 'trending' : surfaceState.mode);
            return;
        }

        surfaceState.debounceTimer = window.setTimeout(function () {
            requestSuggest(surfaceState);
        }, 260);
    }

    function handleSubmit(surfaceState, event) {
        event.preventDefault();

        if (!surfaceState.query) {
            return;
        }

        window.location.href = getSearchResultsUrl(surfaceState.query, surfaceState.scope);
    }

    function bindSurface(root) {
        var surface = root.querySelector('[data-bw-search-surface]');
        var button = root.querySelector('.bw-search-button');

        if (!surface || !button) {
            return;
        }

        if (root.dataset.bwSearchSurfaceBound === '1') {
            return;
        }

        moveSurfaceToBody(surface);

        var surfaceState = {
            root: root,
            button: button,
            surface: surface,
            input: surface.querySelector('[data-bw-search-input]'),
            form: surface.querySelector('[data-bw-search-form]'),
            sidebar: surface.querySelector('[data-bw-search-sidebar]'),
            content: surface.querySelector('[data-bw-search-content]'),
            contentHeader: surface.querySelector('[data-bw-search-content-header]'),
            filterFooter: surface.querySelector('[data-bw-filter-footer]'),
            filterCount: surface.querySelector('[data-bw-filter-count]'),
            filterApply: surface.querySelector('[data-bw-filter-apply]'),
            filterReset: surface.querySelector('[data-bw-filter-reset]'),
            scopeInput: surface.querySelector('[data-bw-search-scope-input]'),
            scopeRow: surface.querySelector('[data-bw-search-scope]'),
            scope: surface.getAttribute('data-default-scope') || 'all',
            activeGroup: 'trending',
            query: '',
            mode: 'trending',
            filterData: null,
            filterSel: { subcategories: [], tags: [], year: { from: null, to: null }, advanced: {} },
            filterCountTimer: null,
            filterCountAbortController: null,
            debounceTimer: null,
            abortController: null,
            scopeIndicatorFrame: null
        };

        renderSidebar(surfaceState);
        surfaceState.scopeIndicator = ensureScopeIndicator(surfaceState);
        scheduleScopeIndicatorUpdate(surfaceState);
        surfaceState.onResize = function () {
            scheduleScopeIndicatorUpdate(surfaceState);
        };

        window.addEventListener('resize', surfaceState.onResize);

        button.addEventListener('click', function (event) {
            event.preventDefault();
            openSurfaceDialog(surfaceState);
        });

        Array.prototype.forEach.call(surface.querySelectorAll('[data-bw-search-close]'), function (closeButton) {
            closeButton.addEventListener('click', function (event) {
                event.preventDefault();
                closeSurfaceDialog(surfaceState);
            });
        });

        surfaceState.form.addEventListener('submit', function (event) {
            handleSubmit(surfaceState, event);
        });

        surfaceState.input.addEventListener('input', function () {
            handleInput(surfaceState);
        });

        surfaceState.sidebar.addEventListener('click', function (event) {
            var groupButton = event.target.closest('[data-bw-search-group]');

            if (!groupButton) {
                return;
            }

            var clickedMode = groupButton.getAttribute('data-bw-search-group');
            surfaceState.mode = clickedMode;
            requestMode(surfaceState, clickedMode);
        });

        surfaceState.content.addEventListener('click', function (event) {
            if (surfaceState.mode !== 'filter') { return; }

            var toggleBtn = event.target.closest('[data-bw-filter-toggle]');
            if (toggleBtn) {
                var group = toggleBtn.closest('[data-bw-filter-group]');
                if (group) {
                    var panel = group.querySelector('.bw-search-surface__filter-group-panel');
                    var isOpen = group.classList.contains('is-open');
                    if (!isOpen) {
                        Array.prototype.forEach.call(
                            surfaceState.content.querySelectorAll('.bw-search-surface__filter-group.is-open'),
                            function (other) {
                                if (other !== group) {
                                    other.classList.remove('is-open');
                                    var otherPanel = other.querySelector('.bw-search-surface__filter-group-panel');
                                    if (otherPanel) { otherPanel.hidden = true; }
                                }
                            }
                        );
                    }
                    group.classList.toggle('is-open', !isOpen);
                    if (panel) { panel.hidden = isOpen; }
                }
                return;
            }

            var chipRemove = event.target.closest('[data-bw-chip-type]');
            if (chipRemove) {
                var chipType = chipRemove.getAttribute('data-bw-chip-type');
                var chipId   = chipRemove.getAttribute('data-bw-chip-id');
                var chipKey  = chipRemove.getAttribute('data-bw-chip-key');
                var chipSlug = chipRemove.getAttribute('data-bw-chip-slug');
                var cSel = surfaceState.filterSel;

                if (chipType === 'subcategory' && chipId) {
                    cSel.subcategories = cSel.subcategories.filter(function (v) { return String(v) !== chipId; });
                } else if (chipType === 'tag' && chipId) {
                    cSel.tags = cSel.tags.filter(function (v) { return String(v) !== chipId; });
                } else if (chipType === 'year') {
                    cSel.year = { from: null, to: null };
                } else if (chipType === 'advanced' && chipKey && chipSlug) {
                    if (cSel.advanced[chipKey]) {
                        cSel.advanced[chipKey] = cSel.advanced[chipKey].filter(function (s) { return s !== chipSlug; });
                    }
                }

                renderFilter(surfaceState, surfaceState.filterData);
                scheduleFilterCount(surfaceState);
                return;
            }

            var optionBtn = event.target.closest('[data-bw-filter-option]');
            if (optionBtn) {
                var optionType = optionBtn.getAttribute('data-bw-filter-option');
                var optionId   = optionBtn.getAttribute('data-bw-option-id');
                var oSel = surfaceState.filterSel;

                if (optionType === 'subcategory') {
                    toggleInArray(oSel.subcategories, optionId);
                } else if (optionType === 'tag') {
                    toggleInArray(oSel.tags, optionId);
                } else if (optionType && optionType.indexOf('advanced_') === 0) {
                    var advKey = optionType.slice(9);
                    if (!oSel.advanced[advKey]) { oSel.advanced[advKey] = []; }
                    toggleInArray(oSel.advanced[advKey], optionId);
                }

                renderFilter(surfaceState, surfaceState.filterData);
                scheduleFilterCount(surfaceState);
                return;
            }
        });

        if (surfaceState.content && surfaceState.content.parentNode) {
            surfaceState.content.parentNode.addEventListener('click', function (event) {
                if (surfaceState.mode !== 'filter') { return; }
                if (surfaceState.content.contains(event.target)) { return; }
                var chipRemove = event.target.closest('[data-bw-chip-type]');
                if (!chipRemove) { return; }
                var chipType = chipRemove.getAttribute('data-bw-chip-type');
                var chipId   = chipRemove.getAttribute('data-bw-chip-id');
                var chipKey  = chipRemove.getAttribute('data-bw-chip-key');
                var chipSlug = chipRemove.getAttribute('data-bw-chip-slug');
                var cSel = surfaceState.filterSel;
                if (chipType === 'subcategory' && chipId) {
                    cSel.subcategories = cSel.subcategories.filter(function (v) { return String(v) !== chipId; });
                } else if (chipType === 'tag' && chipId) {
                    cSel.tags = cSel.tags.filter(function (v) { return String(v) !== chipId; });
                } else if (chipType === 'year') {
                    cSel.year = { from: null, to: null };
                } else if (chipType === 'advanced' && chipKey && chipSlug) {
                    if (cSel.advanced[chipKey]) {
                        cSel.advanced[chipKey] = cSel.advanced[chipKey].filter(function (s) { return s !== chipSlug; });
                    }
                }
                renderFilter(surfaceState, surfaceState.filterData);
                scheduleFilterCount(surfaceState);
            });
        }

        surfaceState.content.addEventListener('input', function (event) {
            if (surfaceState.mode !== 'filter') { return; }
            if (event.target.hasAttribute('data-bw-year-from') || event.target.hasAttribute('data-bw-year-to') || event.target.hasAttribute('data-bw-year-edge')) {
                var fromInput = surfaceState.content.querySelector('[data-bw-year-from]');
                var toInput = surfaceState.content.querySelector('[data-bw-year-to]');
                var fromSlider = surfaceState.content.querySelector('[data-bw-year-edge="from"]');
                var toSlider = surfaceState.content.querySelector('[data-bw-year-edge="to"]');
                var fromVal;
                var toVal;

                if (event.target.hasAttribute('data-bw-year-edge')) {
                    fromVal = fromSlider && fromSlider.value ? parseNullableYear(fromSlider.value) : null;
                    toVal = toSlider && toSlider.value ? parseNullableYear(toSlider.value) : null;
                } else {
                    fromVal = fromInput && fromInput.value ? parseNullableYear(fromInput.value) : null;
                    toVal = toInput && toInput.value ? parseNullableYear(toInput.value) : null;
                }

                surfaceState.filterSel.year = normalizeYearRange(fromVal, toVal, getPopupYearBounds(surfaceState).min, getPopupYearBounds(surfaceState).max);
                updatePopupYearPresentation(surfaceState);
                updateFilterChips(surfaceState);
                scheduleFilterCount(surfaceState);
            }
        });

        if (surfaceState.filterApply) {
            surfaceState.filterApply.addEventListener('click', function () {
                window.location.href = buildFilterNavUrl(surfaceState);
            });
        }

        if (surfaceState.filterReset) {
            surfaceState.filterReset.addEventListener('click', function () {
                surfaceState.filterSel = { subcategories: [], tags: [], year: { from: null, to: null }, advanced: {} };
                renderFilter(surfaceState, surfaceState.filterData);
                var fUi = (surfaceState.filterData && surfaceState.filterData.filter_ui) ? surfaceState.filterData.filter_ui : {};
                updateFilterCount(surfaceState, fUi.result_count !== undefined ? fUi.result_count : 0);
            });
        }

        if (surfaceState.scopeRow) {
            surfaceState.scopeRow.addEventListener('click', function (event) {
                var scopeButton = event.target.closest('[data-bw-scope-option]');

                if (!scopeButton) {
                    return;
                }

                event.preventDefault();
                setScope(surfaceState, scopeButton.getAttribute('data-bw-scope-option'));
            });
        }

        surfaceState.scopeIndicator = ensureScopeIndicator(surfaceState);
        root.dataset.bwSearchSurfaceBound = '1';
        root._bwSearchSurfaceState = surfaceState;
    }

    document.addEventListener('keydown', function (event) {
        if (!openSurface) {
            return;
        }

        if (event.key === 'Escape') {
            closeSurfaceDialog(openSurface);
        }

        if (event.key === 'Enter' && document.activeElement === openSurface.input && openSurface.query) {
            window.location.href = getSearchResultsUrl(openSurface.query, openSurface.scope);
        }
    });

    function initSearchSurfaces() {
        Array.prototype.forEach.call(document.querySelectorAll('.bw-header-search'), bindSurface);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSearchSurfaces);
    } else {
        initSearchSurfaces();
    }
})();
