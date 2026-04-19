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

    function setScope(surfaceState, scope) {
        surfaceState.scope = scope in scopeOptions ? scope : 'all';
        surfaceState.activeGroup = 'trending';
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

        if (surfaceState.query) {
            requestSuggest(surfaceState);
            return;
        }

        requestTrending(surfaceState);
    }

    function openSurfaceDialog(surfaceState) {
        if (openSurface && openSurface !== surfaceState) {
            closeSurfaceDialog(openSurface);
        }

        surfaceState.surface.classList.add('is-open');
        document.body.classList.add('bw-search-overlay-active');
        openSurface = surfaceState;

        if (!surfaceState.query && surfaceState.hasLoadedTrending) {
            renderTrending(surfaceState, surfaceState.trendingRows || []);
        }

        window.setTimeout(function () {
            surfaceState.input.focus();
        }, 40);

        if (!surfaceState.hasLoadedTrending) {
            requestTrending(surfaceState);
        }
    }

    function closeSurfaceDialog(surfaceState) {
        if (surfaceState.abortController) {
            surfaceState.abortController.abort();
            surfaceState.abortController = null;
        }

        window.clearTimeout(surfaceState.debounceTimer);
        surfaceState.surface.classList.remove('is-open');
<<<<<<< HEAD
=======
        surfaceState.scopeRoot.classList.remove('is-open');
        if (surfaceState.scopeTrigger) {
            surfaceState.scopeTrigger.setAttribute('aria-expanded', 'false');
        }
>>>>>>> 7503aa23e259a920fe6d48bed8f0831a31ea7a2b
        surfaceState.query = '';
        surfaceState.activeGroup = 'trending';
        surfaceState.input.value = '';
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

    function renderTrending(surfaceState, rows) {
        var html;

        surfaceState.mode = 'trending';
        surfaceState.activeGroup = 'trending';
        syncLayoutMode(surfaceState);
        renderSidebar(surfaceState);
        setContentHeader(surfaceState, getScopeLabel(surfaceState.scope));

        if (!rows.length) {
            surfaceState.content.innerHTML = '<div class="bw-search-surface__empty">' + escapeHtml(strings.emptyTrending || 'No curated products are available right now.') + '</div>';
            return;
        }

        html = rows.map(function (row) {
            var cards = (row.products || []).map(function (product) {
                var imageHtml = product.image_url
                    ? '<div class="bw-search-surface__trending-image"><img src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.title) + '" loading="lazy"></div>'
                    : '<div class="bw-search-surface__trending-image"></div>';

                return (
                    '<a class="bw-search-surface__trending-card" href="' + escapeHtml(product.permalink) + '">' +
                        imageHtml +
                        '<div class="bw-search-surface__trending-copy">' +
                            '<span class="bw-search-surface__row-title-text">' + escapeHtml(product.title) + '</span>' +
                            '<span class="bw-search-surface__row-meta">' + escapeHtml(product.description || '') + '</span>' +
                        '</div>' +
                    '</a>'
                );
            }).join('');

            return (
                '<section class="bw-search-surface__trending-row">' +
                    '<div class="bw-search-surface__trending-header">' +
                        '<h3 class="bw-search-surface__trending-title">' + escapeHtml(row.title) + '</h3>' +
                    '</div>' +
                    '<div class="bw-search-surface__trending-grid">' + cards + '</div>' +
                '</section>'
            );
        }).join('');

        surfaceState.content.innerHTML = '<div class="bw-search-surface__rows">' + html + '</div>';
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
                    '<span class="bw-search-surface__row-action"></span>' +
                '</a>'
            );
        }).join('');

        surfaceState.content.innerHTML = '<div class="bw-search-surface__row-group">' + actionRow + suggestRows + '</div>';
    }

    function renderBrowse(surfaceState, groupKey, payload) {
        var groups = getScopeGroups(surfaceState.scope);
        var active = null;
        var items = payload && Array.isArray(payload.items) ? payload.items : [];
        var index;
        var rows;

        for (index = 0; index < groups.length; index += 1) {
            if (groups[index].key === groupKey) {
                active = groups[index];
                break;
            }
        }

        surfaceState.mode = 'browse';
        surfaceState.activeGroup = groupKey;
        syncLayoutMode(surfaceState);
        renderSidebar(surfaceState);
        setContentHeader(surfaceState, active ? active.label : '');

        if (!items.length) {
            surfaceState.content.innerHTML = '<div class="bw-search-surface__empty">' + escapeHtml(strings.emptyBrowse || 'No values are available for this filter.') + '</div>';
            return;
        }

        rows = items.map(function (item) {
            return (
                '<a class="bw-search-surface__facet-link" href="' + escapeHtml(item.url || '#') + '" data-bw-search-facet-link>' +
                    '<span class="bw-search-surface__facet-label">' + escapeHtml(item.label || '') + '</span>' +
                    '<span class="bw-search-surface__facet-count">' + escapeHtml(item.count || 0) + '</span>' +
                '</a>'
            );
        }).join('');

        surfaceState.content.innerHTML =
            '<div class="bw-search-surface__facet-group">' +
                '<div class="bw-search-surface__facet-list">' + rows + '</div>' +
            '</div>';
    }

    function requestBrowse(surfaceState, groupKey) {
        setLoadingState(surfaceState);

        requestPayload(surfaceState, 'browse', { group: groupKey }).then(function (response) {
            if (!response || !response.success || !response.data) {
                renderBrowse(surfaceState, groupKey, { items: [] });
                return;
            }

            renderBrowse(surfaceState, groupKey, response.data);
        }).catch(function (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            renderBrowse(surfaceState, groupKey, { items: [] });
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

    function requestTrending(surfaceState) {
        setLoadingState(surfaceState);

        requestPayload(surfaceState, 'trending', { query: '' }).then(function (response) {
            if (!response || !response.success || !response.data) {
                renderTrending(surfaceState, []);
                return;
            }

            surfaceState.trendingRows = response.data.rows || [];
            surfaceState.hasLoadedTrending = true;
            renderTrending(surfaceState, surfaceState.trendingRows);
        }).catch(function (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            renderTrending(surfaceState, []);
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
            requestTrending(surfaceState);
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
            scopeInput: surface.querySelector('[data-bw-search-scope-input]'),
<<<<<<< HEAD
            scopeRow: surface.querySelector('[data-bw-search-scope]'),
=======
            scopeTrigger: surface.querySelector('[data-bw-scope-toggle]'),
            scopeRoot: surface.querySelector('[data-bw-search-scope]'),
            scopeCurrent: surface.querySelector('[data-bw-scope-current]'),
            scopeMenu: surface.querySelector('[data-bw-scope-menu]'),
>>>>>>> 7503aa23e259a920fe6d48bed8f0831a31ea7a2b
            scope: surface.getAttribute('data-default-scope') || 'all',
            activeGroup: 'trending',
            query: '',
            mode: 'trending',
            debounceTimer: null,
            abortController: null,
            hasLoadedTrending: false,
            trendingRows: []
        };

        renderSidebar(surfaceState);

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

            if (groupButton.getAttribute('data-bw-search-group') === 'trending') {
                surfaceState.query = '';
                surfaceState.input.value = '';
                requestTrending(surfaceState);
                return;
            }

            requestBrowse(surfaceState, groupButton.getAttribute('data-bw-search-group'));
        });

        if (surfaceState.scopeRow) {
            surfaceState.scopeRow.addEventListener('click', function (event) {
                var scopeButton = event.target.closest('[data-bw-scope-option]');

                if (!scopeButton) {
                    return;
                }

                event.preventDefault();
<<<<<<< HEAD
                setScope(surfaceState, scopeButton.getAttribute('data-bw-scope-option'));
            });
        }
=======
                surfaceState.scopeRoot.classList.remove('is-open');
                if (surfaceState.scopeTrigger) {
                    surfaceState.scopeTrigger.setAttribute('aria-expanded', 'false');
                }
                setScope(surfaceState, scopeButton.getAttribute('data-bw-scope-option'));
                return;
            }

            if (event.target.closest('[data-bw-scope-toggle]')) {
                event.preventDefault();
                surfaceState.scopeRoot.classList.toggle('is-open');
                if (surfaceState.scopeTrigger) {
                    surfaceState.scopeTrigger.setAttribute('aria-expanded', surfaceState.scopeRoot.classList.contains('is-open') ? 'true' : 'false');
                }
            }
        });
>>>>>>> 7503aa23e259a920fe6d48bed8f0831a31ea7a2b

        root.dataset.bwSearchSurfaceBound = '1';
        root._bwSearchSurfaceState = surfaceState;
    }

<<<<<<< HEAD
=======
    document.addEventListener('click', function (event) {
        if (!openSurface) {
            return;
        }

        if (!event.target.closest('[data-bw-scope-toggle]') && !event.target.closest('[data-bw-scope-menu]')) {
            openSurface.scopeRoot.classList.remove('is-open');
            if (openSurface.scopeTrigger) {
                openSurface.scopeTrigger.setAttribute('aria-expanded', 'false');
            }
        }
    });

>>>>>>> 7503aa23e259a920fe6d48bed8f0831a31ea7a2b
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
