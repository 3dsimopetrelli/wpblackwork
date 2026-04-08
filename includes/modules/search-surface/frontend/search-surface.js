(function () {
    'use strict';

    var config = window.bwSearchSurfaceConfig || {};
    var strings = config.strings || {};
    var sidebarGroups = config.sidebarGroups || {};
    var scopeOptions = config.scopeOptions || {};
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

    function getGroupIconSvg(groupKey) {
        switch (String(groupKey || '')) {
            case 'trending':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 7h6v6"></path><path d="m22 7-8.5 8.5-5-5L2 17"></path></svg>';
            case 'categories':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 2h10"></path><path d="M5 6h14"></path><rect width="18" height="12" x="3" y="10" rx="2"></rect></svg>';
            case 'tags':
            case 'technique':
            case 'source':
            case 'artist':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 12V4a1 1 0 0 1 1-1h6.297a1 1 0 0 1 .651 1.759l-4.696 4.025"></path><path d="m12 21-7.414-7.414A2 2 0 0 1 4 12.172V6.415a1.002 1.002 0 0 1 1.707-.707L20 20.009"></path><path d="m12.214 3.381 8.414 14.966a1 1 0 0 1-.167 1.199l-1.168 1.163a1 1 0 0 1-.706.291H6.351a1 1 0 0 1-.625-.219L3.25 18.8a1 1 0 0 1 .631-1.781l4.165.027"></path></svg>';
            case 'author':
            case 'publisher':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 7v14"></path><path d="M16 12h2"></path><path d="M16 8h2"></path><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path><path d="M6 12h2"></path><path d="M6 8h2"></path></svg>';
            case 'years':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 14v2.2l1.6 1"></path><path d="M16 2v4"></path><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"></path><path d="M3 10h5"></path><path d="M8 2v4"></path><circle cx="16" cy="16" r="6"></circle></svg>';
            default:
                return '';
        }
    }

    function moveSurfaceToBody(surface) {
        if (!surface || !surface.parentNode || surface.parentNode === document.body) {
            return;
        }

        document.body.appendChild(surface);
    }

    function renderPreview(surfaceState, title, body) {
        if (!surfaceState.preview) {
            return;
        }

        surfaceState.preview.innerHTML =
            '<div class="bw-search-surface__preview-card">' +
                '<h3 class="bw-search-surface__preview-title">' + escapeHtml(title) + '</h3>' +
                '<p class="bw-search-surface__preview-copy">' + escapeHtml(body) + '</p>' +
            '</div>';
    }

    function renderSidebar(surfaceState) {
        var groups = getScopeGroups(surfaceState.scope);
        var html = groups.map(function (group, index) {
            var isActive = surfaceState.activeGroup === group.key || (!surfaceState.activeGroup && index === 0);

            return (
                '<button class="bw-search-surface__nav-item' + (isActive ? ' is-active' : '') + '" type="button" data-bw-search-group="' + escapeHtml(group.key) + '">' +
                    '<span class="bw-search-surface__nav-icon" aria-hidden="true">' + getGroupIconSvg(group.key) + '</span>' +
                    '<span class="bw-search-surface__nav-label">' + escapeHtml(group.label) + '</span>' +
                '</button>'
            );
        }).join('');

        surfaceState.sidebar.innerHTML = html;
    }

    function setScope(surfaceState, scope) {
        surfaceState.scope = scope in scopeOptions ? scope : 'all';
        surfaceState.activeGroup = 'trending';
        surfaceState.scopeCurrent.textContent = getScopeLabel(surfaceState.scope);
        if (surfaceState.scopeInput) {
            surfaceState.scopeInput.value = surfaceState.scope;
        }

        Array.prototype.forEach.call(surfaceState.scopeMenu.querySelectorAll('[data-bw-scope-option]'), function (button) {
            var selected = button.getAttribute('data-bw-scope-option') === surfaceState.scope;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-checked', selected ? 'true' : 'false');
        });

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
        surfaceState.scopeRoot.classList.remove('is-open');
        surfaceState.query = '';
        surfaceState.activeGroup = 'trending';
        surfaceState.input.value = '';
        document.body.classList.remove('bw-search-overlay-active');

        if (openSurface === surfaceState) {
            openSurface = null;
        }
    }

    function setContentTitle(surfaceState, title) {
        if (surfaceState.title) {
            surfaceState.title.textContent = title;
        }
    }

    function setLoadingState(surfaceState) {
        setContentTitle(surfaceState, strings.loading || 'Loading…');
        surfaceState.content.innerHTML = '<div class="bw-search-surface__empty">' + escapeHtml(strings.loading || 'Loading…') + '</div>';
    }

    function renderTrending(surfaceState, rows) {
        var html;

        surfaceState.mode = 'trending';
        surfaceState.activeGroup = 'trending';
        renderSidebar(surfaceState);
        setContentTitle(surfaceState, strings.trendingTitle || 'Trending');
        renderPreview(surfaceState, strings.previewTitle || 'Preview', strings.previewBody || '');

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
        var rows = [
            '<a class="bw-search-surface__action-row" href="' + escapeHtml(searchUrl) + '" data-bw-search-action-link>' +
                '<span class="bw-search-surface__action-icon" aria-hidden="true">' +
                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7"></circle><path d="M20 20L16.65 16.65"></path></svg>' +
                '</span>' +
                '<span class="bw-search-surface__row-body">' +
                    '<span class="bw-search-surface__row-title-text">' + escapeHtml(actionLabel) + '</span>' +
                    '<span class="bw-search-surface__row-meta">' + escapeHtml(getScopeLabel(surfaceState.scope)) + '</span>' +
                '</span>' +
                '<span class="bw-search-surface__row-action">' + escapeHtml(strings.searchActionHint || 'Enter') + '</span>' +
            '</a>'
        ];

        setContentTitle(surfaceState, strings.suggestionsTitle || 'Suggested products');
        renderPreview(surfaceState, strings.previewTitle || 'Preview', strings.previewBody || '');

        if (!items.length) {
            rows.push('<div class="bw-search-surface__empty">' + escapeHtml(strings.emptySuggestions || 'No matching products found.') + '</div>');
            surfaceState.content.innerHTML = '<div class="bw-search-surface__row-group">' + rows.join('') + '</div>';
            return;
        }

        items.forEach(function (item) {
            var imageHtml = item.image_url
                ? '<div class="bw-search-surface__suggestion-media"><img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.title) + '" loading="lazy"></div>'
                : '<div class="bw-search-surface__suggestion-media"></div>';

            rows.push(
                '<a class="bw-search-surface__suggestion-row" href="' + escapeHtml(item.permalink) + '">' +
                    imageHtml +
                    '<span class="bw-search-surface__row-body">' +
                        '<span class="bw-search-surface__row-title-text">' + escapeHtml(item.title) + '</span>' +
                        '<span class="bw-search-surface__row-meta">' + escapeHtml(item.description || '') + '</span>' +
                    '</span>' +
                    '<span class="bw-search-surface__row-action"></span>' +
                '</a>'
            );
        });

        surfaceState.content.innerHTML = '<div class="bw-search-surface__row-group">' + rows.join('') + '</div>';
    }

    function renderBrowsePlaceholder(surfaceState, groupKey) {
        var groups = getScopeGroups(surfaceState.scope);
        var active = null;
        var title = active ? active.label : (strings.previewTitle || 'Preview');
        var index;

        for (index = 0; index < groups.length; index += 1) {
            if (groups[index].key === groupKey) {
                active = groups[index];
                break;
            }
        }

        title = active ? active.label : (strings.previewTitle || 'Preview');

        surfaceState.mode = 'browse';
        surfaceState.activeGroup = groupKey;
        renderSidebar(surfaceState);
        setContentTitle(surfaceState, title);
        surfaceState.content.innerHTML = '<div class="bw-search-surface__empty">' + escapeHtml(strings.browsePlaceholder || 'Facet browsing will be expanded in the next milestone.') + '</div>';
        renderPreview(surfaceState, title, strings.browsePlaceholder || 'Facet browsing will be expanded in the next milestone.');
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
            preview: surface.querySelector('[data-bw-search-preview]'),
            title: surface.querySelector('[data-bw-search-title]'),
            scopeInput: surface.querySelector('[data-bw-search-scope-input]'),
            scopeRoot: surface.querySelector('[data-bw-search-scope]'),
            scopeCurrent: surface.querySelector('[data-bw-scope-current]'),
            scopeMenu: surface.querySelector('[data-bw-scope-menu]'),
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
        renderPreview(surfaceState, strings.previewTitle || 'Preview', strings.previewBody || '');

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

            renderBrowsePlaceholder(surfaceState, groupButton.getAttribute('data-bw-search-group'));
        });

        surfaceState.scopeRoot.addEventListener('click', function (event) {
            var scopeButton = event.target.closest('[data-bw-scope-option]');

            if (scopeButton) {
                event.preventDefault();
                surfaceState.scopeRoot.classList.remove('is-open');
                setScope(surfaceState, scopeButton.getAttribute('data-bw-scope-option'));
                return;
            }

            if (event.target.closest('[data-bw-scope-toggle]')) {
                event.preventDefault();
                surfaceState.scopeRoot.classList.toggle('is-open');
            }
        });

        root.dataset.bwSearchSurfaceBound = '1';
        root._bwSearchSurfaceState = surfaceState;
    }

    document.addEventListener('click', function (event) {
        if (!openSurface) {
            return;
        }

        if (!event.target.closest('[data-bw-scope-toggle]') && !event.target.closest('[data-bw-scope-menu]')) {
            openSurface.scopeRoot.classList.remove('is-open');
        }
    });

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
