(function ($) {
    'use strict';

    var cfg = window.bwMediaFolders || window.bwMF || {};
    if (!cfg.ajaxUrl || !cfg.nonce) {
        return;
    }

    var state = {
        folders: [],
        counts: { all: 0, unassigned: 0 },
        activeFolder: cfg.active && cfg.active.folder ? parseInt(cfg.active.folder, 10) : 0,
        activeUnassigned: !!(cfg.active && parseInt(cfg.active.unassigned, 10) === 1),
        mode: (cfg.active && cfg.active.mode === 'grid') ? 'grid' : 'list'
    };
    var draggedIds = [];
    var dragBadgeEl = null;
    var INTERNAL_DRAG_KEY = '__BW_MF_INTERNAL_DRAG';
    var FOLDER_NODE_SEL = '.bw-media-folder-node[data-term-id]';
    var currentHoverNode = null;
    var contextMenuTargetId = 0;
    var contextMenuRowRef = null;
    var contextMenuOpenTick = false;
    var colorPopoverRowRef = null;
    var colorSaveTimer = null;
    var eventsBound = false;
    var cornerIndicatorEnabled = !!(
        (cfg.flags && parseInt(cfg.flags.cornerIndicator, 10) === 1) ||
        parseInt(cfg.cornerIndicatorEnabled, 10) === 1
    );
    var markerObserver = null;
    var markerDebounceTimer = null;
    var markerCache = new Map();
    var markerFailedIds = new Set();
    var markerFetchInFlight = false;
    var attachmentsBrowserEl = null;
    var markerIntersectionObserver = null;
    var markerVisibleTiles = new Set();
    var markerObservedTiles = (typeof WeakSet !== 'undefined') ? new WeakSet() : null;
    var folderByParentMap = {};
    var folderCollapsedMap = {};
    var FOLDER_COLLAPSED_KEY = 'bw_mf_folder_collapsed';

    function isGridMode() {
        return state.mode === 'grid' || !!document.querySelector('.attachments-browser');
    }

    function getAttachmentsBrowserEl() {
        if (attachmentsBrowserEl && document.body.contains(attachmentsBrowserEl)) {
            return attachmentsBrowserEl;
        }

        attachmentsBrowserEl = document.querySelector('.attachments-browser');
        return attachmentsBrowserEl;
    }

    function root() {
        return $('#bw-media-folders-root');
    }

    function setInternalDrag(active) {
        window[INTERNAL_DRAG_KEY] = !!active;
    }

    function isInternalDragActive() {
        return !!window[INTERNAL_DRAG_KEY];
    }

    function debugLog(message, payload) {
        if (!window.BW_MF_DEBUG) {
            return;
        }

        if (payload) {
            console.log('[BW_MF_DEBUG] ' + message, payload);
            return;
        }

        console.log('[BW_MF_DEBUG] ' + message);
    }

    function clearDropHover() {
        $('#bw-media-folders-tree .bw-media-folder-node, #bw-media-folders-defaults .bw-media-default--drop')
            .removeClass('is-drag-over bw-mf-folder-drop-hover');
        currentHoverNode = null;
    }

    function setCurrentHoverNode(node) {
        if (currentHoverNode && currentHoverNode !== node) {
            currentHoverNode.classList.remove('bw-mf-folder-drop-hover', 'is-drag-over');
        }

        if (!node) {
            currentHoverNode = null;
            return;
        }

        node.classList.add('bw-mf-folder-drop-hover', 'is-drag-over');
        currentHoverNode = node;
    }

    function destroyDragBadge() {
        if (dragBadgeEl && dragBadgeEl.parentNode) {
            dragBadgeEl.parentNode.removeChild(dragBadgeEl);
        }
        dragBadgeEl = null;
    }

    function setupDragBadge(event, count) {
        destroyDragBadge();
        if (!event || !event.originalEvent || !event.originalEvent.dataTransfer || !count || count < 1) {
            return;
        }

        var badge = document.createElement('div');
        badge.className = 'bw-mf-drag-badge';
        badge.textContent = count + ' item' + (count === 1 ? '' : 's') + ' selected';
        document.body.appendChild(badge);

        try {
            event.originalEvent.dataTransfer.setDragImage(badge, 0, 0);
            dragBadgeEl = badge;
        } catch (err) {
            destroyDragBadge();
        }
    }

    function request(action, payload, onDone, options) {
        var opts = options || {};
        if (!action || !cfg.ajaxUrl || !cfg.nonce) {
            return;
        }

        var body = $.extend({}, payload || {}, {
            action: action,
            nonce: cfg.nonce,
            bw_mf_context: 'upload'
        });

        $.post(cfg.ajaxUrl, body)
            .done(function (res) {
                if (!res || !res.success) {
                    var msg = res && res.data && res.data.message ? res.data.message : 'Request failed';
                    if (!opts.silent) {
                        window.alert(msg);
                    }
                    return;
                }

                if (typeof onDone === 'function') {
                    onDone(res.data || {});
                }
            })
            .fail(function () {
                if (!opts.silent) {
                    window.alert('Request failed');
                }
            });
    }

    function isValidHexColor(color) {
        return /^#[0-9a-f]{6}$/i.test(String(color || ''));
    }

    function scheduleCornerMarkerRefresh() {
        if (!cornerIndicatorEnabled) {
            disableCornerMarkers();
            return;
        }

        if (!isGridMode()) {
            return;
        }

        if (markerDebounceTimer) {
            window.clearTimeout(markerDebounceTimer);
        }
        markerDebounceTimer = window.setTimeout(function () {
            markerDebounceTimer = null;
            bwMfApplyCornerMarkers();
        }, 320);
    }

    function getVisibleGridTiles() {
        var browser = getAttachmentsBrowserEl();
        if (!browser) {
            return [];
        }

        return Array.prototype.slice.call(browser.querySelectorAll('.attachment[data-id]'));
    }

    function initCornerIntersectionObserver() {
        if (markerIntersectionObserver || typeof IntersectionObserver === 'undefined') {
            return;
        }

        if (window.__BW_MF_CORNER_IO && typeof window.__BW_MF_CORNER_IO.observe === 'function') {
            markerIntersectionObserver = window.__BW_MF_CORNER_IO;
            return;
        }

        markerIntersectionObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry || !entry.target) {
                    return;
                }

                if (entry.isIntersecting) {
                    markerVisibleTiles.add(entry.target);
                } else {
                    markerVisibleTiles.delete(entry.target);
                }
            });

            scheduleCornerMarkerRefresh();
        }, {
            root: null,
            threshold: 0
        });

        window.__BW_MF_CORNER_IO = markerIntersectionObserver;
    }

    function observeGridTilesForCorners() {
        if (!cornerIndicatorEnabled || !isGridMode()) {
            return;
        }

        initCornerIntersectionObserver();

        var tiles = getVisibleGridTiles();
        if (!tiles.length) {
            return;
        }

        if (!markerIntersectionObserver) {
            markerVisibleTiles.clear();
            tiles.forEach(function (tile) {
                markerVisibleTiles.add(tile);
            });
            return;
        }

        tiles.forEach(function (tile) {
            if (markerObservedTiles && markerObservedTiles.has(tile)) {
                return;
            }

            if (markerObservedTiles) {
                markerObservedTiles.add(tile);
            }

            markerIntersectionObserver.observe(tile);
        });
    }

    function getCornerWorkingTiles() {
        if (!markerIntersectionObserver) {
            return getVisibleGridTiles();
        }

        var tiles = [];
        markerVisibleTiles.forEach(function (tile) {
            if (!tile || !document.body.contains(tile)) {
                markerVisibleTiles.delete(tile);
                return;
            }
            tiles.push(tile);
        });

        return tiles;
    }

    function clearCornerMarkers() {
        getCornerWorkingTiles().forEach(function (tile) {
            if (tile.classList.contains('bw-mf-marked')) {
                tile.classList.remove('bw-mf-marked');
            }
            if (tile.style.getPropertyValue('--bw-mf-marker-color')) {
                tile.style.removeProperty('--bw-mf-marker-color');
            }
        });
    }

    function disableCornerMarkers() {
        if (markerDebounceTimer) {
            window.clearTimeout(markerDebounceTimer);
            markerDebounceTimer = null;
        }

        if (markerObserver) {
            markerObserver.disconnect();
            markerObserver = null;
        }

        if (markerIntersectionObserver && typeof markerIntersectionObserver.disconnect === 'function') {
            markerIntersectionObserver.disconnect();
        }

        markerVisibleTiles.clear();
        markerObservedTiles = (typeof WeakSet !== 'undefined') ? new WeakSet() : null;

        clearCornerMarkers();
    }

    function getCornerViewContext() {
        var folderId = 0;
        var unassigned = false;

        try {
            var url = new URL(window.location.href);
            folderId = absint(url.searchParams.get('bw_media_folder'));
            unassigned = String(url.searchParams.get('bw_media_unassigned') || '') === '1';
        } catch (e) {
            folderId = 0;
            unassigned = false;
        }

        if (folderId <= 0 && !unassigned) {
            if (state.activeFolder > 0) {
                folderId = state.activeFolder;
            } else if (state.activeUnassigned) {
                unassigned = true;
            }
        }

        return {
            folderId: folderId > 0 ? folderId : 0,
            unassigned: !!unassigned
        };
    }

    function absint(value) {
        var parsed = parseInt(value, 10);
        return parsed > 0 ? parsed : 0;
    }

    function resolveFolderViewColor(folderId) {
        var defaultColor = '#000';
        if (!(folderId > 0)) {
            return defaultColor;
        }

        var row = document.querySelector('.bw-media-folder-node[data-term-id="' + folderId + '"]');
        if (row) {
            var attrColor = row.getAttribute('data-icon-color');
            if (isValidHexColor(attrColor)) {
                return attrColor;
            }

            var cssVar = row.style ? row.style.getPropertyValue('--bw-mf-icon-color') : '';
            if (!cssVar) {
                cssVar = window.getComputedStyle(row).getPropertyValue('--bw-mf-icon-color');
            }

            cssVar = String(cssVar || '').trim();
            if (isValidHexColor(cssVar)) {
                return cssVar;
            }
        }

        var folder = state.folders.find(function (item) {
            return parseInt(item.id, 10) === folderId;
        });
        if (folder && isValidHexColor(folder.icon_color)) {
            return folder.icon_color;
        }

        return defaultColor;
    }

    function collectVisibleAttachmentIds(tiles) {
        var ids = [];
        (tiles || []).forEach(function (tile) {
            var id = parseInt(tile.getAttribute('data-id') || '0', 10);
            if (id > 0) {
                ids.push(id);
            }
        });

        return Array.from(new Set(ids)).slice(0, 200);
    }

    function fetchCornerMarkers(ids, onDone) {
        if (!ids.length || markerFetchInFlight) {
            if (typeof onDone === 'function') {
                onDone();
            }
            return;
        }

        markerFetchInFlight = true;
        $.post(cfg.ajaxUrl, {
            action: 'bw_mf_get_corner_markers',
            nonce: cfg.nonce,
            bw_mf_context: 'upload',
            attachment_ids: ids
        })
            .done(function (res) {
                if (!res || !res.success || !res.data || typeof res.data.markers !== 'object') {
                    return;
                }

                var markers = res.data.markers;
                Object.keys(markers).forEach(function (idKey) {
                    var id = parseInt(idKey, 10);
                    if (!(id > 0)) {
                        return;
                    }

                    var marker = markers[idKey] || {};
                    markerCache.set(id, {
                        assigned: !!marker.assigned,
                        color: isValidHexColor(marker.color) ? marker.color : null
                    });
                    markerFailedIds.delete(id);
                });
            })
            .always(function () {
                markerFetchInFlight = false;
                if (typeof onDone === 'function') {
                    onDone();
                }
            });
    }

    function setTileCornerMarker(tile, marker) {
        if (!tile) {
            return;
        }

        if (!marker || !marker.assigned) {
            if (tile.classList.contains('bw-mf-marked')) {
                tile.classList.remove('bw-mf-marked');
            }
            if (tile.style.getPropertyValue('--bw-mf-marker-color')) {
                tile.style.removeProperty('--bw-mf-marker-color');
            }
            return;
        }

        if (!tile.classList.contains('bw-mf-marked')) {
            tile.classList.add('bw-mf-marked');
        }
        var targetColor = marker.color || '#000';
        if (tile.style.getPropertyValue('--bw-mf-marker-color') !== targetColor) {
            tile.style.setProperty('--bw-mf-marker-color', targetColor);
        }
    }

    function bwMfApplyCornerMarkers() {
        if (!cornerIndicatorEnabled) {
            disableCornerMarkers();
            return;
        }

        if (!isGridMode()) {
            return;
        }

        bindCornerMarkerObserver();
        observeGridTilesForCorners();

        var tiles = getCornerWorkingTiles();
        if (!tiles.length) {
            return;
        }

        var viewCtx = getCornerViewContext();
        if (viewCtx.unassigned) {
            clearCornerMarkers();
            return;
        }

        if (viewCtx.folderId > 0) {
            var folderColor = resolveFolderViewColor(viewCtx.folderId);
            tiles.forEach(function (tile) {
                setTileCornerMarker(tile, {
                    assigned: true,
                    color: folderColor
                });
            });
            return;
        }

        var visibleIds = collectVisibleAttachmentIds(tiles);
        var missingIds = visibleIds.filter(function (id) {
            return !markerCache.has(id) && !markerFailedIds.has(id);
        });

        var apply = function () {
            getCornerWorkingTiles().forEach(function (tile) {
                var id = parseInt(tile.getAttribute('data-id') || '0', 10);
                setTileCornerMarker(tile, markerCache.get(id));
            });
        };

        if (!missingIds.length) {
            apply();
            return;
        }

        fetchCornerMarkers(missingIds, function () {
            missingIds.forEach(function (id) {
                if (!markerCache.has(id)) {
                    markerFailedIds.add(id);
                }
            });
            apply();
        });
    }

    function bindCornerMarkerObserver() {
        if (!cornerIndicatorEnabled || !isGridMode() || markerObserver) {
            return;
        }

        var attachmentsRoot = getAttachmentsBrowserEl();
        if (!attachmentsRoot || typeof MutationObserver === 'undefined') {
            return;
        }

        markerObserver = new MutationObserver(function () {
            observeGridTilesForCorners();
            scheduleCornerMarkerRefresh();
        });
        markerObserver.observe(attachmentsRoot, { childList: true, subtree: false });
    }

    function refreshCounts() {
        request('bw_media_get_folder_counts', {}, function (data) {
            var map = data && data.folder_counts && typeof data.folder_counts === 'object' ? data.folder_counts : null;
            if (map) {
                state.folders = state.folders.map(function (item) {
                    var byString = map[String(item.id)];
                    var byInt = map[item.id];
                    var next = byString !== undefined ? byString : byInt;
                    if (next !== undefined) {
                        item.count = parseInt(next, 10) || 0;
                    }
                    return item;
                });
            }

            if (data && data.counts) {
                state.counts = data.counts;
            }
            $('#bw-media-folders-defaults .bw-media-default[data-type="all"] span').text(state.counts.all || 0);
            $('#bw-media-folders-defaults .bw-media-default[data-type="unassigned"] span').text(state.counts.unassigned || 0);
            state.folders.forEach(function (item) {
                $('#bw-media-folders-tree .bw-media-folder-node[data-id="' + item.id + '"] .bw-media-folder-node__count').text(item.count);
            });
        }, { silent: true });
    }

    function getQueryUrl(folderId, unassigned) {
        var url = new URL(window.location.href);
        url.searchParams.delete('bw_media_folder');
        url.searchParams.delete('bw_media_unassigned');

        if (unassigned) {
            url.searchParams.set('bw_media_unassigned', '1');
        } else if (folderId > 0) {
            url.searchParams.set('bw_media_folder', String(folderId));
        }

        return url.toString();
    }

    function applyGridFilter(folderId, unassigned) {
        if (state.mode !== 'grid' || !window.wp || !wp.media || !wp.media.frame) {
            return false;
        }

        try {
            var frame = wp.media.frame;
            if (!frame.content || !frame.content.get) {
                return false;
            }

            var content = frame.content.get();
            if (!content || !content.collection || !content.collection.props || typeof content.collection.props.set !== 'function') {
                return false;
            }

            var collection = content.collection;
            var nextProps = {
                bw_media_folder: folderId > 0 ? String(folderId) : '',
                bw_media_unassigned: unassigned ? '1' : '',
                ignore: (+new Date())
            };

            collection.props.set(nextProps);
            if (typeof collection.reset === 'function') {
                collection.reset();
            }
            if (typeof collection.more === 'function') {
                collection.more();
            }

            scheduleCornerMarkerRefresh();
            return true;
        } catch (e) {
            return false;
        }
    }

    function nodeHtml(item, depth) {
        var pad = Math.max(0, depth) * 14;
        var pinnedClass = item.pinned ? ' is-pinned' : '';
        var active = (!state.activeUnassigned && state.activeFolder === item.id) ? ' is-active' : '';
        var isCollapsed = !!folderCollapsedMap[item.id];
        var hasChildren = !!(folderByParentMap[item.id] && folderByParentMap[item.id].length);
        var styles = ['padding-left:' + pad + 'px'];
        var iconColor = item.icon_color ? String(item.icon_color) : '';
        var iconColorAttr = '';
        var pinnedAttr = item.pinned ? '1' : '0';
        var collapsedAttr = isCollapsed ? '1' : '0';
        var pinIndicator = item.pinned ? '<span class="bw-mf-pin-indicator" aria-hidden="true">📌</span>' : '';
        var chevron = hasChildren
            ? '<button class="bw-mf-chevron" type="button" aria-label="Toggle folder" aria-expanded="' + (isCollapsed ? 'false' : 'true') + '">▶</button>'
            : '';

        if (iconColor) {
            styles.push('--bw-mf-icon-color:' + iconColor);
            iconColorAttr = ' data-icon-color="' + iconColor + '"';
        }

        return '' +
            '<div class="bw-media-folder-node' + pinnedClass + active + (hasChildren ? ' is-parent' : '') + (isCollapsed ? ' is-collapsed' : '') + '" data-id="' + item.id + '" data-term-id="' + item.id + '" data-folder-id="' + item.id + '" data-parent="' + item.parent + '" data-pinned="' + pinnedAttr + '" data-collapsed="' + collapsedAttr + '"' + iconColorAttr + ' style="' + styles.join(';') + '">' +
            '  <button class="bw-media-folder-node__main" type="button">' +
            chevron +
            '    <span class="bw-mf-folder-icon" aria-hidden="true">' +
            '      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">' +
            '        <path fill="currentColor" d="M5.5 5 H9.8 A1.6 1.6 0 0 1 11 5.6 L12.2 7 A1.6 1.6 0 0 0 13.4 7.6 H18.5 A1.5 1.5 0 0 1 20 9.1 V18.5 A1.5 1.5 0 0 1 18.5 20 H5.5 A1.5 1.5 0 0 1 4 18.5 V6.5 A1.5 1.5 0 0 1 5.5 5 Z"></path>' +
            '      </svg>' +
            '    </span>' +
            '    <span class="bw-media-folder-node__name">' + item.name + '</span>' +
            '    <span class="bw-media-folder-node__meta">' +
            pinIndicator +
            '      <span class="bw-media-folder-node__count">' + item.count + '</span>' +
            '    </span>' +
            '  </button>' +
            '  <button type="button" class="bw-mf-folder-pencil bw-mf-folder-rename-btn" aria-label="Folder actions">' +
            '    <span class="dashicons dashicons-edit" aria-hidden="true"></span>' +
            '  </button>' +
            '  <div class="bw-media-folder-node__actions bw-media-folder-node__actions--hidden" aria-hidden="true">' +
            '    <button type="button" class="bw-mf-action" data-action="rename">R</button>' +
            '    <button type="button" class="bw-mf-action" data-action="pin">' + (item.pinned ? 'U' : 'P') + '</button>' +
            '    <button type="button" class="bw-mf-action" data-action="color">C</button>' +
            '    <button type="button" class="bw-mf-action bw-mf-action--danger" data-action="delete">X</button>' +
            '  </div>' +
            '</div>';
    }

    function renderContextMenu() {
        if ($('#bw-mf-context-menu').length) {
            return;
        }

        var html = '' +
            '<div id="bw-mf-context-menu" class="bw-mf-context-menu" role="menu" aria-hidden="true">' +
            '  <button type="button" class="bw-mf-context-menu__item" data-cmd="rename"><span class="dashicons dashicons-edit" aria-hidden="true"></span><span>Rename</span></button>' +
            '  <button type="button" class="bw-mf-context-menu__item" data-cmd="pin"><span class="dashicons dashicons-sticky" aria-hidden="true"></span><span>Pin / Unpin</span></button>' +
            '  <button type="button" class="bw-mf-context-menu__item" data-cmd="color"><span class="dashicons dashicons-art" aria-hidden="true"></span><span>Icon Color</span></button>' +
            '  <button type="button" class="bw-mf-context-menu__item bw-mf-context-menu__item--danger" data-cmd="delete"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span>Delete</span></button>' +
            '</div>';

        $('body').append(html);
    }

    function renderColorPopover() {
        if ($('#bw-mf-color-popover').length) {
            return;
        }

        var html = '' +
            '<div id="bw-mf-color-popover" class="bw-mf-color-popover" aria-hidden="true">' +
            '  <input type="color" id="bw-mf-color-input" value="#9aa0a6" />' +
            '  <button type="button" class="button button-small" id="bw-mf-color-reset">Reset</button>' +
            '</div>';

        $('body').append(html);
    }

    function hideContextMenu() {
        $('#bw-mf-context-menu').removeClass('is-open').attr('aria-hidden', 'true');
        contextMenuTargetId = 0;
        contextMenuRowRef = null;
    }

    function hideColorPopover() {
        $('#bw-mf-color-popover').removeClass('is-open').attr('aria-hidden', 'true');
        colorPopoverRowRef = null;
        if (colorSaveTimer) {
            window.clearTimeout(colorSaveTimer);
            colorSaveTimer = null;
        }
    }

    function bwMfOpenFolderMenu(config) {
        var cfgMenu = config || {};
        var menu = $('#bw-mf-context-menu');
        var rowEl = cfgMenu.rowEl || null;
        var anchorEl = cfgMenu.anchorEl || null;
        var clientX = typeof cfgMenu.clientX === 'number' ? cfgMenu.clientX : null;
        var clientY = typeof cfgMenu.clientY === 'number' ? cfgMenu.clientY : null;
        var termId = rowEl ? parseInt($(rowEl).attr('data-id') || '0', 10) : 0;

        if (!menu.length || !rowEl || !(termId > 0)) {
            hideContextMenu();
            return;
        }

        hideColorPopover();

        contextMenuTargetId = termId;
        contextMenuRowRef = rowEl;

        var left = 0;
        var top = 0;
        var rect = null;
        var menuWidth = 0;
        var menuHeight = 0;

        menu.addClass('is-open').attr('aria-hidden', 'false').css({
            left: '0px',
            top: '0px',
            visibility: 'hidden'
        });
        menuWidth = menu.outerWidth() || 0;
        menuHeight = menu.outerHeight() || 0;

        if (anchorEl && typeof anchorEl.getBoundingClientRect === 'function') {
            rect = anchorEl.getBoundingClientRect();
            left = rect.right + window.pageXOffset - menuWidth;
            top = rect.bottom + window.pageYOffset + 6;
        } else {
            left = (clientX !== null ? clientX : 0) + window.pageXOffset;
            top = (clientY !== null ? clientY : 0) + window.pageYOffset;
        }

        contextMenuOpenTick = true;
        var viewportLeft = window.pageXOffset;
        var viewportTop = window.pageYOffset;
        var viewportRight = viewportLeft + window.innerWidth;
        var viewportBottom = viewportTop + window.innerHeight;

        left = Math.max(viewportLeft + 10, Math.min(left, viewportRight - menuWidth - 10));
        top = Math.max(viewportTop + 10, Math.min(top, viewportBottom - menuHeight - 10));

        menu.css({
            left: left + 'px',
            top: top + 'px',
            visibility: 'visible'
        });

        window.requestAnimationFrame(function () {
            contextMenuOpenTick = false;
        });
    }

    function bwMfResolveActionButtons(rowEl) {
        if (!rowEl || !rowEl.querySelectorAll) {
            return {};
        }

        var byData = {
            rename: rowEl.querySelector('[data-action="rename"]'),
            pin: rowEl.querySelector('[data-action="pin"], [data-action="up"], [data-action="sticky"]'),
            color: rowEl.querySelector('[data-action="color"]'),
            del: rowEl.querySelector('[data-action="delete"]')
        };

        var btns = Array.from(rowEl.querySelectorAll('button, a'));
        var byText = {
            rename: btns.find(function (b) { return ((b.textContent || '').trim() === 'R'); }),
            pin: btns.find(function (b) {
                var t = (b.textContent || '').trim();
                return t === 'U' || t === 'P';
            }),
            color: btns.find(function (b) { return ((b.textContent || '').trim() === 'C'); }),
            del: btns.find(function (b) { return ((b.textContent || '').trim() === 'X'); })
        };

        var resolved = $.extend({}, byText);
        Object.keys(byData).forEach(function (key) {
            if (byData[key]) {
                resolved[key] = byData[key];
            }
        });

        return resolved;
    }

    function updateRowPinnedState(rowEl, pinned) {
        if (!rowEl) {
            return;
        }

        var isPinned = !!pinned;
        rowEl.setAttribute('data-pinned', isPinned ? '1' : '0');
        rowEl.classList.toggle('is-pinned', isPinned);

        var meta = rowEl.querySelector('.bw-media-folder-node__meta');
        var indicator = rowEl.querySelector('.bw-mf-pin-indicator');
        if (isPinned && !indicator && meta) {
            indicator = document.createElement('span');
            indicator.className = 'bw-mf-pin-indicator';
            indicator.setAttribute('aria-hidden', 'true');
            indicator.textContent = '📌';
            meta.insertBefore(indicator, meta.firstChild || null);
        } else if (!isPinned && indicator && indicator.parentNode) {
            indicator.parentNode.removeChild(indicator);
        }
    }

    function bwMfTriggerActionButton(button, label) {
        if (!button || typeof button.dispatchEvent !== 'function') {
            if (window.BW_MF_DEBUG) {
                console.warn('[BW_MF_DEBUG] missing folder action button:', label);
            }
            return false;
        }

        button.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
        return true;
    }

    function setRowIconColor(rowEl, color) {
        if (!rowEl || !rowEl.style) {
            return;
        }

        if (color) {
            rowEl.style.setProperty('--bw-mf-icon-color', color);
            rowEl.setAttribute('data-icon-color', color);
            return;
        }

        rowEl.style.removeProperty('--bw-mf-icon-color');
        rowEl.removeAttribute('data-icon-color');
    }

    function openColorPopover(rowEl, anchorEl) {
        var pop = $('#bw-mf-color-popover');
        if (!rowEl || !anchorEl || !pop.length) {
            return;
        }

        colorPopoverRowRef = rowEl;
        var current = rowEl.getAttribute('data-icon-color') || '#9aa0a6';
        $('#bw-mf-color-input').val(current);

        var rect = anchorEl.getBoundingClientRect();
        pop.addClass('is-open').attr('aria-hidden', 'false').css({
            left: '0px',
            top: '0px',
            visibility: 'hidden'
        });

        var popWidth = pop.outerWidth() || 0;
        var popHeight = pop.outerHeight() || 0;
        var left = rect.right + window.pageXOffset - popWidth;
        var top = rect.bottom + window.pageYOffset + 6;

        var viewportLeft = window.pageXOffset;
        var viewportTop = window.pageYOffset;
        var viewportRight = viewportLeft + window.innerWidth;
        var viewportBottom = viewportTop + window.innerHeight;

        left = Math.max(viewportLeft + 10, Math.min(left, viewportRight - popWidth - 10));
        top = Math.max(viewportTop + 10, Math.min(top, viewportBottom - popHeight - 10));

        pop.css({
            left: left + 'px',
            top: top + 'px',
            visibility: 'visible'
        });
    }

    function promptRenameFolder(termId) {
        var folder = findFolder(termId);
        if (!folder || termId <= 0) {
            return;
        }

        var newName = window.prompt(cfg.text && cfg.text.renamePrompt ? cfg.text.renamePrompt : 'Rename folder', folder.name);
        if (!newName) {
            return;
        }

        request('bw_media_rename_folder', { term_id: termId, name: newName }, refreshTree);
    }

    function renderDefaults() {
        var html = '';
        var allClass = (!state.activeFolder && !state.activeUnassigned) ? ' is-active' : '';
        var unClass = state.activeUnassigned ? ' is-active' : '';

        html += '<button type="button" class="bw-media-default' + allClass + '" data-type="all">All Files <span>' + (state.counts.all || 0) + '</span></button>';
        html += '<button type="button" class="bw-media-default bw-media-default--drop' + unClass + '" data-type="unassigned" data-term-id="0" data-folder-id="0">Unassigned Files <span>' + (state.counts.unassigned || 0) + '</span></button>';

        $('#bw-media-folders-defaults').html(html);
    }

    function loadCollapsedState() {
        folderCollapsedMap = {};
        try {
            var raw = window.localStorage.getItem(FOLDER_COLLAPSED_KEY);
            if (!raw) {
                return;
            }

            var parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') {
                return;
            }

            Object.keys(parsed).forEach(function (key) {
                if (parsed[key]) {
                    var id = parseInt(key, 10);
                    if (id > 0) {
                        folderCollapsedMap[id] = true;
                    }
                }
            });
        } catch (e) {
            folderCollapsedMap = {};
        }
    }

    function saveCollapsedState() {
        try {
            window.localStorage.setItem(FOLDER_COLLAPSED_KEY, JSON.stringify(folderCollapsedMap));
        } catch (e) {
            // ignore storage errors
        }
    }

    function toggleFolderCollapsed(termId) {
        if (!(termId > 0)) {
            return;
        }
        if (folderCollapsedMap[termId]) {
            delete folderCollapsedMap[termId];
        } else {
            folderCollapsedMap[termId] = true;
        }
        saveCollapsedState();
        syncTreeNodeVisibility();
    }

    function buildTreeRows() {
        var byParent = {};

        state.folders.forEach(function (item) {
            var p = item.parent || 0;
            if (!byParent[p]) {
                byParent[p] = [];
            }
            byParent[p].push(item);
        });
        folderByParentMap = byParent;

        function walk(parent, depth, out) {
            var children = byParent[parent] || [];
            children.forEach(function (child) {
                out.push(nodeHtml(child, depth));
                walk(child.id, depth + 1, out);
            });
        }

        var rows = [];
        walk(0, 0, rows);
        return rows;
    }

    function syncTreeNodeVisibility() {
        var searchTerm = ($('#bw-mr-folder-search').val() || '').toLowerCase().trim();
        var nodes = Array.prototype.slice.call(document.querySelectorAll('#bw-media-folders-tree .bw-media-folder-node'));
        var nodeById = {};
        nodes.forEach(function (node) {
            var id = parseInt(node.getAttribute('data-id') || '0', 10);
            if (id > 0) {
                nodeById[id] = node;
            }
        });

        nodes.forEach(function (node) {
            var id = parseInt(node.getAttribute('data-id') || '0', 10);
            var parentId = parseInt(node.getAttribute('data-parent') || '0', 10);
            var matchesSearch = !searchTerm || ((node.querySelector('.bw-media-folder-node__name') && node.querySelector('.bw-media-folder-node__name').textContent || '').toLowerCase().indexOf(searchTerm) !== -1);

            var hiddenByCollapse = false;
            var currentParent = parentId;
            while (currentParent > 0) {
                if (folderCollapsedMap[currentParent]) {
                    hiddenByCollapse = true;
                    break;
                }

                var parentNode = nodeById[currentParent];
                if (!parentNode) {
                    break;
                }
                currentParent = parseInt(parentNode.getAttribute('data-parent') || '0', 10);
            }

            node.style.display = (matchesSearch && !hiddenByCollapse) ? '' : 'none';
            node.classList.toggle('is-collapsed', !!folderCollapsedMap[id]);

            var chevron = node.querySelector('.bw-mf-chevron');
            if (chevron) {
                chevron.setAttribute('aria-expanded', folderCollapsedMap[id] ? 'false' : 'true');
                chevron.classList.toggle('is-collapsed', !!folderCollapsedMap[id]);
            }
        });
    }

    function renderTree() {
        $('#bw-media-folders-tree').html(buildTreeRows().join(''));

        var options = ['<option value="0">Unassigned</option>'];
        state.folders.forEach(function (item) {
            options.push('<option value="' + item.id + '">' + item.name + '</option>');
        });
        $('#bw-media-folders-bulk-select').html(options.join(''));

        syncTreeNodeVisibility();
        bindDropTargets();
    }

    function refreshTree() {
        request('bw_media_get_folders_tree', {}, function (data) {
            state.folders = Array.isArray(data.folders) ? data.folders : [];
            state.counts = data.counts || { all: 0, unassigned: 0 };
            renderDefaults();
            renderTree();
            refreshCounts();
            scheduleCornerMarkerRefresh();
        });
    }

    function findFolder(id) {
        return state.folders.find(function (item) {
            return item.id === id;
        });
    }

    function applySearchFilter() {
        syncTreeNodeVisibility();
    }

    function collectSelectedMediaIds() {
        var ids = [];

        $('.wp-list-table .check-column input[type="checkbox"]:checked').each(function () {
            var row = $(this).closest('tr');
            var idAttr = row.attr('id') || '';
            var id = parseInt(idAttr.replace('post-', ''), 10);
            if (id > 0) {
                ids.push(id);
            }
        });

        $('.attachments .attachment.selected').each(function () {
            var id = parseInt($(this).attr('data-id') || '0', 10);
            if (id > 0) {
                ids.push(id);
            }
        });

        if (window.wp && wp.media && wp.media.frame && wp.media.frame.state) {
            try {
                var selection = wp.media.frame.state().get('selection');
                if (selection && typeof selection.each === 'function') {
                    selection.each(function (model) {
                        var modelId = parseInt(model && model.get ? model.get('id') : 0, 10);
                        if (modelId > 0) {
                            ids.push(modelId);
                        }
                    });
                }
            } catch (e) {
                // fail-open: fallback to DOM selected nodes only
            }
        }

        return Array.from(new Set(ids));
    }

    function refreshMediaView() {
        if (state.mode === 'grid' && window.wp && wp.media && wp.media.frame) {
            try {
                var frame = wp.media.frame;
                if (frame.content && frame.content.get) {
                    var browser = frame.content.get();
                    if (browser && browser.collection && typeof browser.collection.props === 'function') {
                        browser.collection.props.set({
                            bw_media_folder: state.activeFolder > 0 ? state.activeFolder : undefined,
                            bw_media_unassigned: state.activeUnassigned ? '1' : undefined
                        });
                        browser.collection.more();
                        scheduleCornerMarkerRefresh();
                        return;
                    }
                }
            } catch (e) {
                // fallback below
            }
        }

        window.location.reload();
    }

    function assignFolder(folderId, ids, onDone) {
        request('bw_media_assign_folder', {
            term_id: folderId,
            attachment_ids: ids
        }, function () {
            if (Array.isArray(ids)) {
                ids.forEach(function (id) {
                    var parsed = parseInt(id, 10);
                    if (parsed > 0) {
                        markerCache.delete(parsed);
                        markerFailedIds.delete(parsed);
                    }
                });
            }
            refreshTree();
            if (typeof onDone === 'function') {
                onDone();
            }
            scheduleCornerMarkerRefresh();
        });
    }

    function makeGridTilesDraggable() {
        $('.attachments-browser .attachment').attr('draggable', 'true');
        $('.attachments-browser .attachment img').attr('draggable', 'false');
    }

    function makeListRowsDraggable() {
        $('.wp-list-table tbody tr').attr('draggable', 'true');
    }

    function bindInternalDragSuppression() {
        if (document.__bwMfDragSuppressionBound) {
            return;
        }

        document.__bwMfDragSuppressionBound = true;

        function isFolderTarget(target) {
            return !!(target && target.closest && target.closest('#bw-media-folders-root'));
        }

        function suppressUploaderHijack(event) {
            if (!isInternalDragActive()) {
                return;
            }

            if (event.type === 'dragover') {
                var hoverNode = event.target && event.target.closest ? event.target.closest(FOLDER_NODE_SEL) : null;
                setCurrentHoverNode(hoverNode);
                if (window.BW_MF_DEBUG && hoverNode) {
                    console.log('[BW_MF_DEBUG] hover node', hoverNode.getAttribute('data-term-id') || '');
                }
            }

            if (event.type === 'drop') {
                setInternalDrag(false);
                clearDropHover();
            }

            event.preventDefault();
            if (event.type === 'dragenter' || event.type === 'dragover') {
                if (event.dataTransfer) {
                    try {
                        event.dataTransfer.dropEffect = 'move';
                    } catch (err) {}
                }
                event.stopImmediatePropagation();
                return;
            }

            if (!isFolderTarget(event.target)) {
                event.stopImmediatePropagation();
            }
        }

        document.addEventListener('dragenter', suppressUploaderHijack, true);
        document.addEventListener('dragover', suppressUploaderHijack, true);
        document.addEventListener('drop', suppressUploaderHijack, true);
    }

    function readDraggedIdsFromDataTransfer(event) {
        var result = [];
        var transfer = event && event.originalEvent ? event.originalEvent.dataTransfer : null;
        var raw = transfer && typeof transfer.getData === 'function' ? transfer.getData('text/plain') : '';

        if (raw) {
            raw.split(',').forEach(function (chunk) {
                var id = parseInt(String(chunk).trim(), 10);
                if (id > 0) {
                    result.push(id);
                }
            });
        }

        if (!result.length && draggedIds.length) {
            result = draggedIds.slice();
        }

        return Array.from(new Set(result));
    }

    function collectDragIdsForElement($el) {
        var id = parseInt($el.attr('data-id') || (($el.attr('id') || '').replace('post-', '')) || '0', 10);
        if (!(id > 0)) {
            return [];
        }

        var selected = collectSelectedMediaIds();
        if (selected.indexOf(id) !== -1 && selected.length > 1) {
            return selected;
        }

        return [id];
    }

    function bindDropTargets() {
        makeGridTilesDraggable();
        makeListRowsDraggable();

        $('#bw-media-folders-tree .bw-media-folder-node, #bw-media-folders-defaults .bw-media-default--drop')
            .off('.bwMfDnD')
            .on('dragenter.bwMfDnD', function (e) {
                e.preventDefault();
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    try {
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                    } catch (err) {}
                }
            })
            .on('dragover.bwMfDnD', function (e) {
                e.preventDefault();
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    try {
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                    } catch (err) {}
                }
            }).on('drop.bwMfDnD', function (e) {
                e.preventDefault();
                clearDropHover();
                setInternalDrag(false);
                destroyDragBadge();

                var folderId = parseInt($(this).attr('data-term-id') || $(this).attr('data-folder-id') || $(this).attr('data-id') || '0', 10);
                var ids = readDraggedIdsFromDataTransfer(e);
                if (!ids.length) {
                    debugLog('drop ignored: no draggable ids');
                    return;
                }

                debugLog('drop assign request', { folderId: folderId, ids: ids });
                assignFolder(folderId, ids, function () {
                    refreshMediaView();
                });
            });

        $(document)
            .off('dragstart.bwMfDnDGrid', '.attachments-browser .attachment')
            .on('dragstart.bwMfDnDGrid', '.attachments-browser .attachment', function (e) {
                var ids = collectDragIdsForElement($(this));
                if (!ids.length) {
                    draggedIds = [];
                    setInternalDrag(false);
                    return;
                }

                draggedIds = ids.slice();
                setInternalDrag(true);
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    try {
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                    } catch (err) {}
                    e.originalEvent.dataTransfer.setData('text/plain', ids.join(','));
                }
                setupDragBadge(e, ids.length);
                debugLog('grid dragstart', { ids: ids });
            });

        $(document)
            .off('dragstart.bwMfDnDList', '.wp-list-table tbody tr')
            .on('dragstart.bwMfDnDList', '.wp-list-table tbody tr', function (e) {
                var ids = collectDragIdsForElement($(this));
                if (!ids.length) {
                    draggedIds = [];
                    setInternalDrag(false);
                    return;
                }

                draggedIds = ids.slice();
                setInternalDrag(true);
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    try {
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                    } catch (err) {}
                    e.originalEvent.dataTransfer.setData('text/plain', ids.join(','));
                }
                setupDragBadge(e, ids.length);
                debugLog('list dragstart', { ids: ids });
            });

        $(document)
            .off('dragend.bwMfDnDCleanup', '.attachments-browser .attachment, .wp-list-table tbody tr')
            .on('dragend.bwMfDnDCleanup', '.attachments-browser .attachment, .wp-list-table tbody tr', function () {
                draggedIds = [];
                setInternalDrag(false);
                clearDropHover();
                destroyDragBadge();
            }
        );

        $(document)
            .off('mousedown.bwMfDnDImage', '.attachments-browser .attachment')
            .on('mousedown.bwMfDnDImage', '.attachments-browser .attachment', function () {
                $(this).attr('draggable', 'true');
                $(this).find('img').attr('draggable', 'false');
            });
    }

    function registerGridAjaxFilter() {
        if (window.__BW_MF_PREFILTER_DONE) {
            return;
        }
        window.__BW_MF_PREFILTER_DONE = true;

        $.ajaxPrefilter(function (options) {
            if (typeof options.data !== 'string' || typeof options.url !== 'string' || options.url.indexOf('admin-ajax.php') === -1) {
                return;
            }

            if (options.data.indexOf('action=query-attachments') === -1) {
                return;
            }

            if (state.activeUnassigned) {
                if (options.data.indexOf('query%5Bbw_media_unassigned%5D=1') === -1) {
                    options.data += '&query%5Bbw_media_unassigned%5D=1';
                }
            } else if (state.activeFolder > 0) {
                var encodedFolder = 'query%5Bbw_media_folder%5D=' + encodeURIComponent(String(state.activeFolder));
                if (options.data.indexOf('query%5Bbw_media_folder%5D=') === -1) {
                    options.data += '&' + encodedFolder;
                }
            }
        });
    }

    function setCollapsedState(collapsed) {
        var body = $('body');
        body.toggleClass('bw-mf-collapsed', collapsed);

        $('#bw-media-folders-toggle')
            .attr('aria-expanded', collapsed ? 'false' : 'true')
            .text(collapsed ? 'Expand' : 'Collapse');

        $('#bw-mf-collapse-tab')
            .attr('aria-expanded', collapsed ? 'false' : 'true')
            .text('Open Folders');

        try {
            window.localStorage.setItem('bw_mf_collapsed', collapsed ? '1' : '0');
        } catch (e) {
            // ignore storage errors
        }
    }

    function bindEvents() {
        if (eventsBound) {
            return;
        }
        eventsBound = true;

        root().on('click', '#bw-media-folders-toggle', function () {
            var collapsed = !$('body').hasClass('bw-mf-collapsed');
            setCollapsedState(collapsed);
        });

        $(document).on('click', '#bw-mf-collapse-tab', function () {
            setCollapsedState(false);
        });

        root().on('click', '#bw-mr-new-folder-btn', function () {
            var name = window.prompt(cfg.text && cfg.text.newFolderPrompt ? cfg.text.newFolderPrompt : 'Folder name');
            if (!name) {
                return;
            }

            request('bw_media_create_folder', { name: name, parent: 0 }, refreshTree);
        });

        root().on('input', '#bw-mr-folder-search', applySearchFilter);

        root().on('click', '.bw-mf-chevron', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var row = $(this).closest('.bw-media-folder-node');
            var termId = parseInt(row.attr('data-term-id') || row.attr('data-id') || '0', 10);
            toggleFolderCollapsed(termId);
        });

        root().on('click', '.bw-media-default', function () {
            var type = $(this).attr('data-type');
            if (type === 'unassigned') {
                state.activeFolder = 0;
                state.activeUnassigned = true;
                if (applyGridFilter(0, true)) {
                    renderDefaults();
                    renderTree();
                    return;
                }
                window.location.href = getQueryUrl(0, true);
                return;
            }

            state.activeFolder = 0;
            state.activeUnassigned = false;
            if (applyGridFilter(0, false)) {
                renderDefaults();
                renderTree();
                return;
            }
            window.location.href = getQueryUrl(0, false);
        });

        root().on('click', '.bw-media-folder-node__main', function (e) {
            if ($(e.target).closest('.bw-mf-chevron').length) {
                return;
            }
            var folderId = parseInt($(this).closest('.bw-media-folder-node').attr('data-term-id') || $(this).closest('.bw-media-folder-node').attr('data-id') || '0', 10);
            state.activeFolder = folderId > 0 ? folderId : 0;
            state.activeUnassigned = false;
            if (applyGridFilter(state.activeFolder, false)) {
                renderDefaults();
                renderTree();
                return;
            }
            window.location.href = getQueryUrl(folderId, false);
        });

        root().on('contextmenu', '.bw-media-folder-node', function (e) {
            e.preventDefault();
            bwMfOpenFolderMenu({
                rowEl: this,
                clientX: e.clientX,
                clientY: e.clientY
            });
        });

        $(document).on('click', function (e) {
            if (contextMenuOpenTick) {
                return;
            }
            if ($(e.target).closest('#bw-mf-context-menu').length) {
                return;
            }
            if ($(e.target).closest('#bw-mf-color-popover').length) {
                return;
            }
            if ($(e.target).closest('.bw-mf-folder-pencil, .bw-mf-folder-rename-btn').length) {
                return;
            }
            hideContextMenu();
            hideColorPopover();
        });

        $(window).on('scroll resize', function () {
            hideContextMenu();
            hideColorPopover();
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                hideContextMenu();
                hideColorPopover();
            }
        });

        $(document).on('click', '#bw-mf-context-menu .bw-mf-context-menu__item', function () {
            var item = $(this);
            var cmd = item.attr('data-cmd') || '';
            var row = contextMenuRowRef ? $(contextMenuRowRef) : $();
            var termId = row.length ? parseInt(row.attr('data-id') || '0', 10) : 0;
            var folder = termId > 0 ? findFolder(termId) : null;

            if (!row.length) {
                hideContextMenu();
                return;
            }

            if (cmd === 'pin') {
                if (!folder || !row.length) {
                    hideContextMenu();
                    return;
                }

                var nextPin = row.attr('data-pinned') === '1' ? 0 : 1;
                request('bw_mf_toggle_folder_pin', {
                    term_id: termId,
                    pinned: nextPin
                }, refreshTree, { silent: true });

                folder.pinned = nextPin ? 1 : 0;
                updateRowPinnedState(row.get(0), nextPin);
                state.folders.sort(function (a, b) {
                    if (a.parent !== b.parent) {
                        return 0;
                    }
                    if ((a.pinned ? 1 : 0) !== (b.pinned ? 1 : 0)) {
                        return (b.pinned ? 1 : 0) - (a.pinned ? 1 : 0);
                    }
                    return String(a.name || '').localeCompare(String(b.name || ''));
                });
                renderTree();
                hideContextMenu();
                return;
            }

            var actions = bwMfResolveActionButtons(row.get(0));
            var actionButton = null;
            if (cmd === 'rename') {
                actionButton = actions.rename || null;
            } else if (cmd === 'pin') {
                actionButton = actions.pin || null;
            } else if (cmd === 'color') {
                hideContextMenu();
                openColorPopover(row.get(0), row.find('.bw-mf-folder-pencil, .bw-mf-folder-rename-btn').get(0));
                return;
            } else if (cmd === 'delete') {
                actionButton = actions.del || null;
            }

            if (!actionButton) {
                hideContextMenu();
                return;
            }

            bwMfTriggerActionButton(actionButton, cmd);
            hideContextMenu();
        });

        $(document).on('input change', '#bw-mf-color-input', function () {
            var rowEl = colorPopoverRowRef;
            var color = String($(this).val() || '');
            var termId = rowEl ? parseInt($(rowEl).attr('data-id') || '0', 10) : 0;
            if (!(termId > 0) || !color) {
                return;
            }

            setRowIconColor(rowEl, color);
            if (colorSaveTimer) {
                window.clearTimeout(colorSaveTimer);
            }
            colorSaveTimer = window.setTimeout(function () {
                request('bw_mf_set_folder_color', { term_id: termId, color: color }, function (data) {
                    var applied = data && data.color ? String(data.color) : color;
                    setRowIconColor(rowEl, applied);
                }, { silent: true });
            }, 180);
        });

        $(document).on('click', '#bw-mf-color-reset', function (e) {
            e.preventDefault();
            var rowEl = colorPopoverRowRef;
            var termId = rowEl ? parseInt($(rowEl).attr('data-id') || '0', 10) : 0;
            if (!(termId > 0)) {
                hideColorPopover();
                return;
            }

            request('bw_mf_reset_folder_color', { term_id: termId }, function () {
                setRowIconColor(rowEl, '');
                hideColorPopover();
            }, { silent: true });
        });

        root().on('click', '.bw-mf-folder-pencil, .bw-mf-folder-rename-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            var row = $(this).closest('.bw-media-folder-node[data-term-id]');
            if (!row.length) {
                return;
            }

            bwMfOpenFolderMenu({
                rowEl: row.get(0),
                anchorEl: this
            });
        });

        root().on('click', '.bw-mf-action', function (e) {
            e.stopPropagation();
            var node = $(this).closest('.bw-media-folder-node');
            var termId = parseInt(node.attr('data-id') || '0', 10);
            var action = $(this).attr('data-action');
            var folder = findFolder(termId);

            if (!folder || termId <= 0) {
                return;
            }

            if (action === 'rename') {
                promptRenameFolder(termId);
                return;
            }

            if (action === 'delete') {
                if (window.confirm(cfg.text && cfg.text.confirmDelete ? cfg.text.confirmDelete : 'Delete this folder?')) {
                    request('bw_media_delete_folder', { term_id: termId }, function () {
                        if (state.activeFolder === termId) {
                            window.location.href = getQueryUrl(0, false);
                            return;
                        }
                        refreshTree();
                    });
                }
                return;
            }

            if (action === 'pin') {
                var nextPin = folder.pinned ? 0 : 1;
                request('bw_media_update_folder_meta', {
                    term_id: termId,
                    pinned: nextPin,
                    color: folder.color || '',
                    sort: folder.sort || 0
                }, refreshTree);
                return;
            }

            if (action === 'color') {
                var color = window.prompt('Folder color (hex)', folder.color || '#6b7280');
                if (color) {
                    request('bw_media_update_folder_meta', {
                        term_id: termId,
                        pinned: folder.pinned ? 1 : 0,
                        color: color,
                        sort: folder.sort || 0
                    }, refreshTree);
                }
            }
        });

        root().on('click', '#bw-media-folders-bulk-btn', function () {
            var ids = collectSelectedMediaIds();
            if (!ids.length) {
                window.alert(cfg.text && cfg.text.selectMedia ? cfg.text.selectMedia : 'Select at least one media item.');
                return;
            }

            var folderId = parseInt($('#bw-media-folders-bulk-select').val() || '0', 10);
            assignFolder(folderId, ids, function () {
                refreshMediaView();
            });
        });
    }

    function mountLayout() {
        var body = $('body');
        var rootEl = root();
        if (!rootEl.length) {
            return;
        }

        if (!body.hasClass('bw-mf-enabled')) {
            body.addClass('bw-mf-enabled');
        }

        var target = $('#wpbody-content');
        if (target.length && !rootEl.parent().is(target)) {
            rootEl.prependTo(target);
        }
    }

    function init() {
        if (window.__BW_MF_INIT_DONE) {
            return;
        }
        window.__BW_MF_INIT_DONE = true;

        mountLayout();
        loadCollapsedState();
        renderContextMenu();
        renderColorPopover();
        makeGridTilesDraggable();
        makeListRowsDraggable();
        bindInternalDragSuppression();

        var collapsed = false;
        try {
            if (window.localStorage.getItem('bw_mf_collapsed') === '1') {
                collapsed = true;
            }
        } catch (e) {
            // ignore storage errors
        }

        setCollapsedState(collapsed);
        bindEvents();
        registerGridAjaxFilter();
        refreshTree();
        bindCornerMarkerObserver();
        scheduleCornerMarkerRefresh();
    }

    $(init);

    document.addEventListener('DOMContentLoaded', function () {
        if (!cornerIndicatorEnabled) {
            clearCornerMarkers();
            return;
        }
        window.setTimeout(function () {
            bwMfApplyCornerMarkers();
        }, 500);
    });
})(jQuery);
