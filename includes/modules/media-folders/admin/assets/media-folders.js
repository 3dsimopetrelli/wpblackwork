(function ($) {
    'use strict';

    window.BW_MEDIA_FOLDERS_SCRIPT_LOADED = true;
    window.BW_MF_LOADED_AT = Date.now();
    window.BW_MF_PATCH_STATUS = window.BW_MF_PATCH_STATUS || {};
    window.BW_MEDIA_FOLDERS_DIAG = {
        scriptLoaded: true,
        wpMediaExists: !!(window.wp && wp.media),
        mediaFrameSelectExists: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select),
        patchApplied: false,
        lastPatchAttempt: null,
        lastMountAttempt: null,
        lastMountReason: '',
        lastMountFrameType: '',
        lastModalExists: false,
        lastContentExists: false,
        lastBrowserExists: false,
        lastAttachmentsBrowserExists: false,
        lastExistingSidebarCount: 0,
        lastSidebarMarkupLength: 0,
        lastInsertionTarget: '',
        lastMissingSelector: '',
        lastInjectionError: '',
        sidebarInjected: false
    };
    if (window.console && typeof console.log === 'function') {
        console.log('[BW MF BOOT]', {
            loaded: true,
            loadedAt: window.BW_MF_LOADED_AT,
            hasWpMedia: !!(window.wp && wp.media),
            hasMediaFramePost: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Post),
            hasMediaFrameSelect: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select)
        });
    }

    var cfg = window.bwMediaFolders || window.bwMF || {};
    function readSessionDebugFlag(key) {
        try {
            if (!window.sessionStorage) {
                return false;
            }

            return window.sessionStorage.getItem(key) === '1';
        } catch (err) {
            return false;
        }
    }

    function isMediaFoldersDebugEnabled() {
        return !!(
            window.BW_MEDIA_FOLDERS_DEBUG === true ||
            window.bwMediaFoldersDebug === true ||
            window.bwMFDebug === true ||
            readSessionDebugFlag('BW_MEDIA_FOLDERS_DEBUG') ||
            readSessionDebugFlag('bwMediaFoldersDebug') ||
            readSessionDebugFlag('bwMFDebug')
        );
    }

    function escapeAttr(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function sanitizeInlineColor(value) {
        var raw = String(value || '').trim();

        if (!raw) {
            return '';
        }

        if (!/^[#(),.%\sa-zA-Z0-9_-]+$/.test(raw)) {
            return '';
        }

        return raw;
    }

    if (isMediaFoldersDebugEnabled() && window.console && typeof console.log === 'function') {
        console.log('[BW Media Folders Modal] script loaded');
    }

    if (!cfg.ajaxUrl || !cfg.nonce) {
        return;
    }
    var currentPostType = String(cfg.postType || 'attachment');
    var currentScreenContext = String(cfg.screenContext || (currentPostType === 'attachment' ? 'upload' : currentPostType));

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
    var badgeTooltipEnabled = parseInt(cfg.badgeTooltipEnabled, 10) === 1;
    var markerObserver = null;
    var markerCache = new Map();
    var markerFailedIds = new Set();
    var markerFetchInFlight = null;
    var markerPendingIds = new Set();
    var markerFetchWaiters = [];
    var attachmentsBrowserEl = null;
    var markerIntersectionObserver = null;
    var markerVisibleTiles = new Set();
    var markerObservedTiles = (typeof WeakSet !== 'undefined') ? new WeakSet() : null;
    var badgeTooltipEl = null;
    var badgeTooltipEventsBound = false;
    var duplicateNoticeEl = null;
    var duplicateNoticeMessageEl = null;
    var quickTypeFilterActive = '';
    var quickTypeFilterObserver = null;
    var quickTypeFilterObserverTarget = null;
    var quickTypeLayoutObserver = null;
    var quickTypeMimeCache = new Map();
    var quickTypeFiltersEventsBound = false;
    var bwMfRefreshScheduled = false;
    var bwMfRefreshReasons = [];
    var bwMfRefreshCount = 0;
    var bwMfRefreshRunCount = 0;
    var gridToolbarEl = null;
    var listToolbarEl = null;
    var quickFiltersBarEl = null;
    var folderByParentMap = {};
    var folderCollapsedMap = {};
    var FOLDER_COLLAPSED_KEY = 'bw_mf_folder_collapsed';
    var modalState = {
        active: false,
        frame: null,
        activeFolder: 0,
        activeUnassigned: false,
        sidebarCollapsed: false,
        folders: [],
        counts: { all: 0, unassigned: 0 }
    };
    var modalSelectors = {
        root: '#bw-media-folders-modal-root',
        defaults: '#bw-media-folders-modal-defaults',
        tree: '#bw-media-folders-modal-tree',
        search: '#bw-mr-folder-search-modal',
        bulk: '#bw-media-folders-modal-bulk-select',
        toggle: '#bw-media-folders-toggle-modal',
        collapseTab: '#bw-mf-collapse-tab-modal',
        shell: '.bw-mf-modal-sidebar-shell'
    };
    var mediaFrameModalPatched = false;
    var mediaFramePatchAttempts = 0;
    var mediaFramePatchTimer = null;
    var mediaFramePatchDeadline = 0;
    var mediaFramePatchSignalsBound = false;
    var mediaFramePatchDelay = 250;
    var mediaModalEventsBound = false;
    var mediaModalMountTimer = null;
    var mediaModalMountAttempts = 0;
    var mediaFramePatchRunCount = 0;
    var mediaFramePatchSignalCount = 0;
    var mediaFramePatchRetryCount = 0;
    var classicModalBindTimer = null;
    var classicModalBindDeadline = 0;
    var classicModalObserverBound = false;
    var lastKnownMediaFrame = null;

    function getModalRootElement() {
        var roots = document.querySelectorAll('.media-modal');
        if (!roots || !roots.length) {
            return $();
        }

        return $(roots[roots.length - 1]);
    }

    function updateMediaFoldersDiag(patch) {
        window.BW_MEDIA_FOLDERS_DIAG = window.BW_MEDIA_FOLDERS_DIAG || {};
        if (patch && typeof patch === 'object') {
            for (var key in patch) {
                if (Object.prototype.hasOwnProperty.call(patch, key)) {
                    window.BW_MEDIA_FOLDERS_DIAG[key] = patch[key];
                }
            }
        }

        window.BW_MEDIA_FOLDERS_DIAG.scriptLoaded = true;
        window.BW_MEDIA_FOLDERS_DIAG.wpMediaExists = !!(window.wp && wp.media);
        window.BW_MEDIA_FOLDERS_DIAG.mediaFrameSelectExists = !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select);
        window.BW_MEDIA_FOLDERS_DIAG.patchApplied = !!mediaFrameModalPatched;
        window.BW_MEDIA_FOLDERS_DIAG.frameObjectExists = !!(patch && Object.prototype.hasOwnProperty.call(patch, 'frameObjectExists') ? patch.frameObjectExists : window.BW_MEDIA_FOLDERS_DIAG.frameObjectExists);
        window.BW_MEDIA_FOLDERS_DIAG.frameElExists = !!(patch && Object.prototype.hasOwnProperty.call(patch, 'frameElExists') ? patch.frameElExists : window.BW_MEDIA_FOLDERS_DIAG.frameElExists);
        window.BW_MEDIA_FOLDERS_DIAG.modalDomFallbackUsed = !!(patch && Object.prototype.hasOwnProperty.call(patch, 'modalDomFallbackUsed') ? patch.modalDomFallbackUsed : window.BW_MEDIA_FOLDERS_DIAG.modalDomFallbackUsed);
        window.BW_MEDIA_FOLDERS_DIAG.modalRootSelector = typeof (patch && patch.modalRootSelector) !== 'undefined' ? patch.modalRootSelector : (window.BW_MEDIA_FOLDERS_DIAG.modalRootSelector || '');
        window.BW_MEDIA_FOLDERS_DIAG.sidebarInjected = !!(modalState.active && getModalSidebarRoot().length);
        return window.BW_MEDIA_FOLDERS_DIAG;
    }

    function mediaFoldersDebugLog(message, payload) {
        if (!isMediaFoldersDebugEnabled() || !window.console || typeof console.log !== 'function') {
            return;
        }

        var prefix = '[BW Media Folders Modal] ' + message;
        if (typeof payload !== 'undefined') {
            console.log(prefix, payload);
            return;
        }

        console.log(prefix);
    }

    function classicModalDebugLog(message, payload) {
        if (!window.console || typeof console.log !== 'function') {
            return;
        }

        var prefix = '[BW MF Classic Modal] ' + message;
        if (typeof payload !== 'undefined') {
            console.log(prefix, payload);
            return;
        }

        console.log(prefix);
    }

    function getMediaFrameLabel(frame) {
        if (!frame || !window.wp || !wp.media || !wp.media.view || !wp.media.view.MediaFrame) {
            return '';
        }

        try {
            if (wp.media.view.MediaFrame.Post && frame instanceof wp.media.view.MediaFrame.Post) {
                return 'Post';
            }
        } catch (e) {
            // ignore instanceof errors
        }

        try {
            if (wp.media.view.MediaFrame.FeaturedImage && frame instanceof wp.media.view.MediaFrame.FeaturedImage) {
                return 'FeaturedImage';
            }
        } catch (e2) {
            // ignore instanceof errors
        }

        try {
            if (wp.media.view.MediaFrame.Select && frame instanceof wp.media.view.MediaFrame.Select) {
                return 'Select';
            }
        } catch (e3) {
            // ignore instanceof errors
        }

        if (frame.__bwMfFrameLabel) {
            return String(frame.__bwMfFrameLabel);
        }

        if (frame.constructor && frame.constructor.name) {
            return String(frame.constructor.name);
        }

        return '';
    }

    function getMediaFrameDebugMeta(frame) {
        var state = null;

        try {
            state = frame && typeof frame.state === 'function' ? frame.state() : null;
        } catch (e) {
            state = null;
        }

        return {
            frameType: getMediaFrameLabel(frame),
            frameCid: frame && frame.cid ? String(frame.cid) : '',
            stateId: state && state.id ? String(state.id) : '',
            stateTitle: state && typeof state.get === 'function' ? String(state.get('title') || '') : '',
            contentMode: frame && frame.content && typeof frame.content.mode === 'function' ? String(frame.content.mode() || '') : '',
            hasAttachmentsBrowser: !!(frame && frame.$el && frame.$el.length && frame.$el.find('.attachments-browser').length),
            hasInjectedRoot: !!getModalSidebarRoot().length
        };
    }

    function getPrototypeChainNames(obj) {
        var names = [];
        var cursor = obj;
        var depth = 0;

        while (cursor && depth < 8) {
            if (cursor.constructor && cursor.constructor.name) {
                names.push(String(cursor.constructor.name));
            } else {
                names.push('(anonymous)');
            }
            cursor = Object.getPrototypeOf(cursor);
            depth += 1;
        }

        return names;
    }

    function mediaFoldersDebugState(frame) {
        var frameEl = frame && frame.$el && frame.$el.length ? frame.$el : $();
        return {
            url: window.location ? window.location.href : '',
            hasWpMedia: !!(window.wp && wp.media),
            hasMediaFrameSelect: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select),
            frameHasEl: !!frameEl.length,
            modalExists: !!document.querySelector('.media-modal'),
            mediaFrameContentExists: !!frameEl.find('.media-frame-content').length,
            mediaFrameBrowseExists: !!frameEl.find('.media-frame-browse').length,
            attachmentsBrowserExists: !!frameEl.find('.attachments-browser').length,
            sidebarShellExists: !!getModalSidebarRoot().length
        };
    }

    function isGridMode() {
        return state.mode === 'grid' || !!document.querySelector('.attachments-browser');
    }

    function isUploadScreen() {
        return !!(document.body && document.body.classList && document.body.classList.contains('upload-php'));
    }

    function isSupportedListScreen() {
        if (!document.body || !document.body.classList) {
            return false;
        }

        return document.body.classList.contains('upload-php') || document.body.classList.contains('edit-php');
    }

    function isMediaPostType() {
        return currentPostType === 'attachment';
    }

    function isModalSidebarActive() {
        return !!(modalState.active && modalState.frame);
    }

    function getActiveFilterState() {
        return isModalSidebarActive() ? modalState : state;
    }

    function getActiveAjaxContext() {
        if (isModalSidebarActive()) {
            return {
                postType: 'attachment',
                screenContext: 'upload'
            };
        }

        return {
            postType: currentPostType,
            screenContext: currentScreenContext
        };
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

    function setupDragBadge(event, count, labelText) {
        destroyDragBadge();
        if (!event || !event.originalEvent || !event.originalEvent.dataTransfer || !count || count < 1) {
            return;
        }

        var badge = document.createElement('div');
        badge.className = 'bw-mf-drag-badge';
        badge.textContent = labelText ? String(labelText) : (count + ' item' + (count === 1 ? '' : 's') + ' selected');
        document.body.appendChild(badge);

        try {
            event.originalEvent.dataTransfer.setDragImage(badge, 0, 0);
            dragBadgeEl = badge;
        } catch (err) {
            destroyDragBadge();
        }
    }

    function ensureDuplicateNotice() {
        if (duplicateNoticeEl && duplicateNoticeMessageEl && document.body.contains(duplicateNoticeEl)) {
            return duplicateNoticeEl;
        }

        duplicateNoticeEl = document.createElement('div');
        duplicateNoticeEl.className = 'bw-mf-duplicate-notice';
        duplicateNoticeEl.setAttribute('aria-hidden', 'true');
        duplicateNoticeEl.innerHTML =
            '<div class="bw-mf-duplicate-notice__dialog" role="dialog" aria-modal="true" aria-label="Folder notice">' +
            '  <p class="bw-mf-duplicate-notice__message"></p>' +
            '</div>';
        duplicateNoticeMessageEl = duplicateNoticeEl.querySelector('.bw-mf-duplicate-notice__message');

        duplicateNoticeEl.addEventListener('click', function (event) {
            if (event.target === duplicateNoticeEl) {
                hideDuplicateNotice();
            }
        });

        document.body.appendChild(duplicateNoticeEl);
        return duplicateNoticeEl;
    }

    function showDuplicateNotice(message) {
        var text = String(message || '').trim();
        if (!text) {
            return;
        }

        ensureDuplicateNotice();
        if (!duplicateNoticeEl || !duplicateNoticeMessageEl) {
            return;
        }

        duplicateNoticeMessageEl.textContent = text;
        duplicateNoticeEl.classList.add('is-visible');
        duplicateNoticeEl.setAttribute('aria-hidden', 'false');
    }

    function hideDuplicateNotice() {
        if (!duplicateNoticeEl) {
            return;
        }

        duplicateNoticeEl.classList.remove('is-visible');
        duplicateNoticeEl.setAttribute('aria-hidden', 'true');
    }

    function copyTextToClipboard(text) {
        var value = String(text || '').trim();
        if (!value) {
            return Promise.reject(new Error('empty-copy-value'));
        }

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function' && window.isSecureContext) {
            return navigator.clipboard.writeText(value);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                if (document.execCommand('copy')) {
                    resolve();
                } else {
                    reject(new Error('execCommand-copy-failed'));
                }
            } catch (err) {
                reject(err);
            } finally {
                if (textarea.parentNode) {
                    textarea.parentNode.removeChild(textarea);
                }
            }
        });
    }

    function request(action, payload, onDone, options) {
        var opts = options || {};
        if (!action || !cfg.ajaxUrl || !cfg.nonce) {
            return;
        }

        var ajaxContext = getActiveAjaxContext();

        var body = $.extend({}, payload || {}, {
            action: action,
            nonce: cfg.nonce,
            bw_mf_context: ajaxContext.screenContext
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

    function scheduleBwMfRefresh(reason) {
        if (!isSupportedListScreen()) {
            return;
        }

        if (reason) {
            bwMfRefreshReasons.push(reason);
        }

        bwMfRefreshCount += 1;
        if (bwMfRefreshScheduled) {
            return;
        }

        bwMfRefreshScheduled = true;
        window.requestAnimationFrame(function () {
            bwMfRefreshScheduled = false;
            bwMfRefreshRunCount += 1;
            if (window.BW_MF_DEBUG) {
                debugLog('refresh coalesced', {
                    scheduled: bwMfRefreshCount,
                    runs: bwMfRefreshRunCount,
                    reasons: bwMfRefreshReasons.slice(-6)
                });
            }
            bwMfRefreshCount = 0;
            bwMfRefreshReasons = [];

            if (isMediaPostType()) {
                ensureTypeFiltersPlacement();
                if (cornerIndicatorEnabled && isGridMode()) {
                    observeGridTilesForCorners();
                    bwMfApplyCornerMarkers();
                } else if (!cornerIndicatorEnabled) {
                    clearCornerMarkers();
                }
                recomputeQuickTypeFilters();
            } else if (cornerIndicatorEnabled) {
                bwMfApplyListRowMarkers();
            } else {
                clearListRowMarkers();
            }
        });
    }

    function scheduleCornerMarkerRefresh() {
        if (!isMediaPostType() || !cornerIndicatorEnabled) {
            disableCornerMarkers();
            return;
        }
        scheduleBwMfRefresh('corner');
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
            if (tile.hasAttribute('data-bw-mf-folder-name')) {
                tile.removeAttribute('data-bw-mf-folder-name');
            }
        });
    }

    function getVisibleListRows() {
        return Array.prototype.slice.call(document.querySelectorAll('.wp-list-table tbody tr[id^="post-"]'));
    }

    function clearListRowMarkers() {
        getVisibleListRows().forEach(function (row) {
            var markerEl = row.querySelector('.column-bw_mf_drag_handle .bw-mf-row-folder-marker');
            if (markerEl && markerEl.parentNode) {
                markerEl.parentNode.removeChild(markerEl);
            }
        });
    }

    function disableCornerMarkers() {
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
        clearListRowMarkers();
        hideBadgeTooltip();
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

    function resolveFolderIdFromNode(node) {
        if (!node || !node.length) {
            return 0;
        }

        var attrs = ['data-folder-id', 'data-folder', 'data-term-id', 'data-id'];
        for (var i = 0; i < attrs.length; i += 1) {
            var value = parseInt(node.attr(attrs[i]) || '0', 10);
            if (value > 0) {
                return value;
            }
        }

        return 0;
    }

    function modalClickDebug() {}

    function getCollectionPropsSnapshot(collection) {
        try {
            if (collection && collection.props && typeof collection.props.toJSON === 'function') {
                return collection.props.toJSON();
            }
        } catch (err) {
            return { error: err && err.message ? String(err.message) : String(err) };
        }

        return null;
    }

    function setCollectionProp(collection, key, value) {
        if (!collection || !collection.props) {
            return;
        }

        if (typeof value === 'undefined' || value === null || value === '') {
            if (typeof collection.props.unset === 'function') {
                collection.props.unset(key);
                return;
            }
            if (typeof collection.props.set === 'function') {
                collection.props.set(key, value);
            }
            return;
        }

        if (typeof collection.props.set === 'function') {
            collection.props.set(key, value);
        }
    }

    function getModalMediaCollections(frame) {
        var collections = [];

        function addCollection(collection) {
            var hasProps = !!(collection && collection.props);
            var hasShape = !!(collection && (Array.isArray(collection.models) || typeof collection.length === 'number'));
            if (!collection || !hasProps || !hasShape) {
                return;
            }

            if (collections.indexOf(collection) === -1) {
                collections.push(collection);
            }
        }

        try {
            if (frame && typeof frame.state === 'function') {
                var activeState = frame.state();
                if (activeState && typeof activeState.get === 'function') {
                    addCollection(activeState.get('library'));
                }
            }
        } catch (err) {
            // ignore modal state lookup errors
        }

        try {
            if (frame && frame.content && typeof frame.content.get === 'function') {
                var content = frame.content.get();
                if (content) {
                    addCollection(content.collection);
                }
            }
        } catch (err2) {
            // ignore content lookup errors
        }

        try {
            addCollection(frame && frame.library ? frame.library : null);
        } catch (err3) {
            // ignore direct library lookup errors
        }

        return collections;
    }

    function getQuickTypeDefinitions() {
        return [
            { key: 'video', label: 'Video' },
            { key: 'jpeg', label: 'JPEG' },
            { key: 'png', label: 'PNG' },
            { key: 'svg', label: 'SVG' },
            { key: 'fonts', label: 'Fonts' }
        ];
    }

    function normalizeMimeType(mime) {
        return String(mime || '').trim().toLowerCase();
    }

    function getFileExtension(value) {
        var text = String(value || '').trim().toLowerCase();
        if (!text) {
            return '';
        }

        var clean = text.split('?')[0].split('#')[0];
        var dot = clean.lastIndexOf('.');
        if (dot < 0 || dot === clean.length - 1) {
            return '';
        }

        return clean.substring(dot + 1);
    }

    function mapExtensionToMime(extension) {
        var ext = String(extension || '').toLowerCase();
        if (!ext) {
            return '';
        }

        if (ext === 'jpg' || ext === 'jpeg') {
            return 'image/jpeg';
        }
        if (ext === 'png') {
            return 'image/png';
        }
        if (ext === 'svg') {
            return 'image/svg+xml';
        }
        if (ext === 'woff') {
            return 'font/woff';
        }
        if (ext === 'woff2') {
            return 'font/woff2';
        }
        if (ext === 'ttf') {
            return 'font/ttf';
        }
        if (ext === 'otf') {
            return 'font/otf';
        }
        if (ext === 'eot') {
            return 'application/vnd.ms-fontobject';
        }
        if (ext === 'mp4') {
            return 'video/mp4';
        }
        if (ext === 'mov') {
            return 'video/quicktime';
        }
        if (ext === 'm4v') {
            return 'video/x-m4v';
        }
        if (ext === 'webm') {
            return 'video/webm';
        }
        if (ext === 'ogv' || ext === 'ogg') {
            return 'video/ogg';
        }

        return '';
    }

    function getMimeFromClasses(node) {
        if (!node || !node.className) {
            return '';
        }

        var classes = String(node.className).split(/\s+/);
        if (classes.indexOf('type-video') !== -1) {
            return 'video/*';
        }

        if (classes.indexOf('type-font') !== -1) {
            return 'font/*';
        }

        var subtypeClass = classes.find(function (className) {
            return className.indexOf('subtype-') === 0;
        });

        if (subtypeClass) {
            var subtype = subtypeClass.replace('subtype-', '').toLowerCase();
            if (subtype === 'jpeg' || subtype === 'jpg') {
                return 'image/jpeg';
            }
            if (subtype === 'png') {
                return 'image/png';
            }
            if (subtype.indexOf('svg') !== -1) {
                return 'image/svg+xml';
            }
            if (subtype === 'woff' || subtype === 'woff2' || subtype === 'ttf' || subtype === 'otf') {
                return 'font/' + subtype;
            }
            if (subtype === 'vnd-ms-fontobject' || subtype === 'eot') {
                return 'application/vnd.ms-fontobject';
            }
            if (subtype === 'mp4') {
                return 'video/mp4';
            }
        }

        return '';
    }

    function getMimeFromMediaModel(id) {
        if (!(id > 0) || !(window.wp && wp.media && wp.media.model && wp.media.model.Attachment)) {
            return '';
        }

        try {
            var model = wp.media.model.Attachment.get(id);
            if (!model || typeof model.get !== 'function') {
                return '';
            }

            var mime = normalizeMimeType(model.get('mime'));
            if (mime) {
                return mime;
            }

            var type = normalizeMimeType(model.get('type'));
            var subtype = normalizeMimeType(model.get('subtype'));
            if (type && subtype) {
                return type + '/' + subtype;
            }
        } catch (e) {
            return '';
        }

        return '';
    }

    function detectMimeForNode(node, id) {
        if (!node) {
            return '';
        }

        var attrMime = normalizeMimeType(node.getAttribute('data-mime'));
        if (attrMime) {
            return attrMime;
        }

        var modelMime = getMimeFromMediaModel(id);
        if (modelMime) {
            return modelMime;
        }

        var classMime = getMimeFromClasses(node);
        if (classMime) {
            return classMime;
        }

        var fileText = '';
        var titleNode = node.querySelector('.column-title strong a, .attachment-filename, .filename');
        if (titleNode) {
            fileText = titleNode.textContent || '';
        }
        if (!fileText) {
            fileText = node.getAttribute('aria-label') || '';
        }
        var ext = getFileExtension(fileText);
        if (ext) {
            return mapExtensionToMime(ext);
        }

        return '';
    }

    function mapMimeToQuickType(mime) {
        var normalized = normalizeMimeType(mime);
        if (!normalized) {
            return '';
        }

        if (normalized.indexOf('video/') === 0 || normalized === 'video/*') {
            return 'video';
        }

        if (normalized === 'image/jpeg' || normalized === 'image/jpg') {
            return 'jpeg';
        }

        if (normalized === 'image/png') {
            return 'png';
        }

        if (normalized === 'image/svg+xml') {
            return 'svg';
        }

        if (
            normalized.indexOf('font/') === 0 ||
            normalized.indexOf('application/font') === 0 ||
            normalized.indexOf('application/x-font') === 0 ||
            normalized === 'application/vnd.ms-fontobject' ||
            normalized === 'font/woff' ||
            normalized === 'font/woff2' ||
            normalized === 'font/ttf' ||
            normalized === 'font/otf'
        ) {
            return 'fonts';
        }

        return '';
    }

    function getAttachmentNodesForQuickFilters() {
        if (isGridMode()) {
            return Array.prototype.slice.call(document.querySelectorAll('.attachments-browser .attachment[data-id]'));
        }

        return Array.prototype.slice.call(document.querySelectorAll('#the-list tr[id^="post-"]'));
    }

    function getAttachmentIdFromQuickNode(node) {
        if (!node) {
            return 0;
        }

        var dataId = absint(node.getAttribute('data-id'));
        if (dataId > 0) {
            return dataId;
        }

        var rowId = String(node.getAttribute('id') || '');
        if (rowId.indexOf('post-') === 0) {
            return absint(rowId.replace('post-', ''));
        }

        return 0;
    }

    function ensureQuickFiltersToolbar() {
        if (quickFiltersBarEl && document.body.contains(quickFiltersBarEl)) {
            return quickFiltersBarEl;
        }

        var bar = document.getElementById('bw-mf-type-filters');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'bw-mf-type-filters';
            bar.className = 'bw-mf-type-filters';
            getQuickTypeDefinitions().forEach(function (def) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'bw-mf-type-filter';
                button.setAttribute('data-filter', def.key);
                button.innerHTML = '<span class="bw-mf-type-filter__label">' + def.label + '</span><span class="bw-mf-type-filter__count">0</span>';
                bar.appendChild(button);
            });
        }

        quickFiltersBarEl = bar;
        return bar;
    }

    function ensureTypeFiltersPlacement() {
        var bar = ensureQuickFiltersToolbar();
        if (!bar) {
            return null;
        }

        var legacyRow = document.querySelector('.bw-mf-toolbar-row--typefilters');
        if (legacyRow && legacyRow !== bar.parentNode) {
            legacyRow.parentNode.removeChild(legacyRow);
        }

        var body = document.body;
        if (!gridToolbarEl || !document.body.contains(gridToolbarEl)) {
            gridToolbarEl = document.querySelector('.media-toolbar');
        }
        if (!listToolbarEl || !document.body.contains(listToolbarEl)) {
            listToolbarEl = document.querySelector('.tablenav.top');
        }

        var gridSecondary = gridToolbarEl ? gridToolbarEl.querySelector('.media-toolbar-secondary') : null;
        var gridPrimary = gridToolbarEl ? (gridToolbarEl.querySelector('.media-toolbar-primary') || gridToolbarEl) : null;
        var listBar = listToolbarEl;
        var preferredContainer = gridSecondary || gridPrimary || null;

        if (!preferredContainer && listBar) {
            preferredContainer = listBar.querySelector('.actions') ||
                (listBar.querySelector('.tablenav-pages') ? listBar.querySelector('.tablenav-pages').parentElement : null) ||
                listBar;
        }

        var inline = !!preferredContainer;
        var wrapper = document.querySelector('.bw-mf-toolbar-inline');

        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'bw-mf-toolbar-inline';
        }

        if (!preferredContainer) {
            var fallbackAnchor = document.querySelector('#wpbody-content .wrap') || document.querySelector('#wpbody-content h1');
            if (fallbackAnchor && fallbackAnchor.parentNode) {
                if (wrapper.parentNode !== fallbackAnchor.parentNode || wrapper.previousElementSibling !== fallbackAnchor) {
                    fallbackAnchor.parentNode.insertBefore(wrapper, fallbackAnchor.nextSibling);
                }
                if (bar.parentNode !== wrapper) {
                    wrapper.appendChild(bar);
                }
            }
            body.classList.remove('bw-mf-has-typefilters-inline');
            return wrapper;
        }

        if (wrapper.parentNode !== preferredContainer) {
            preferredContainer.appendChild(wrapper);
        }

        if (bar.parentNode !== wrapper) {
            wrapper.appendChild(bar);
        }

        body.classList.toggle('bw-mf-has-typefilters-inline', inline);
        return wrapper;
    }

    function updateQuickFilterCounts(counts) {
        var bar = document.getElementById('bw-mf-type-filters');
        if (!bar) {
            return;
        }

        getQuickTypeDefinitions().forEach(function (def) {
            var button = bar.querySelector('.bw-mf-type-filter[data-filter="' + def.key + '"]');
            if (!button) {
                return;
            }

            var countEl = button.querySelector('.bw-mf-type-filter__count');
            if (countEl) {
                countEl.textContent = String(counts[def.key] || 0);
            }
            button.classList.toggle('is-active', quickTypeFilterActive === def.key);
        });
    }

    function setQuickTypeNodeVisibility(node, visible) {
        if (!node) {
            return;
        }

        var shouldHide = !visible;
        if (node.classList.contains('bw-mf-type-hidden') === shouldHide) {
            return;
        }

        node.classList.toggle('bw-mf-type-hidden', shouldHide);
    }

    function recomputeQuickTypeFilters() {
        if (!isUploadScreen()) {
            return;
        }

        var bar = ensureQuickFiltersToolbar();
        if (!bar) {
            return;
        }

        var counts = { video: 0, jpeg: 0, png: 0, svg: 0, fonts: 0 };
        var nodes = getAttachmentNodesForQuickFilters();

        nodes.forEach(function (node) {
            var id = getAttachmentIdFromQuickNode(node);
            if (!(id > 0)) {
                setQuickTypeNodeVisibility(node, !quickTypeFilterActive);
                return;
            }

            var mime = quickTypeMimeCache.get(id);
            if (!mime) {
                mime = detectMimeForNode(node, id);
                if (mime) {
                    quickTypeMimeCache.set(id, mime);
                }
            }

            var typeKey = mapMimeToQuickType(mime);
            if (typeKey && counts[typeKey] !== undefined) {
                counts[typeKey] += 1;
            }

            if (!quickTypeFilterActive) {
                setQuickTypeNodeVisibility(node, true);
                return;
            }

            setQuickTypeNodeVisibility(node, quickTypeFilterActive === typeKey);
        });

        updateQuickFilterCounts(counts);
    }

    function scheduleQuickTypeFiltersRefresh() {
        scheduleBwMfRefresh('types');
    }

    function mutationNodeHasAttachment(node) {
        if (!node || node.nodeType !== 1) {
            return false;
        }

        if (node.matches && (node.matches('.attachment') || node.matches('.attachments-browser'))) {
            return true;
        }

        return !!(node.querySelector && node.querySelector('.attachment'));
    }

    function mutationsContainAttachmentChanges(mutations) {
        for (var i = 0; i < mutations.length; i += 1) {
            var mutation = mutations[i];
            if (!mutation) {
                continue;
            }

            if (mutation.type === 'attributes') {
                if (mutation.attributeName === 'class') {
                    var target = mutation.target;
                    if (target && target.nodeType === 1 && target.matches && (target.matches('.attachment') || target.matches('.attachments-browser') || target.matches('.attachments-browser *'))) {
                        return true;
                    }
                }
                continue;
            }

            if (mutation.type !== 'childList') {
                continue;
            }

            var added = mutation.addedNodes || [];
            for (var a = 0; a < added.length; a += 1) {
                if (mutationNodeHasAttachment(added[a])) {
                    return true;
                }
            }

            var removed = mutation.removedNodes || [];
            for (var r = 0; r < removed.length; r += 1) {
                if (mutationNodeHasAttachment(removed[r])) {
                    return true;
                }
            }
        }

        return false;
    }

    function mutationsContainLayoutChanges(mutations) {
        for (var i = 0; i < mutations.length; i += 1) {
            var mutation = mutations[i];
            if (!mutation || mutation.type !== 'childList') {
                continue;
            }

            var collections = [mutation.addedNodes || [], mutation.removedNodes || []];
            for (var c = 0; c < collections.length; c += 1) {
                var nodes = collections[c];
                for (var n = 0; n < nodes.length; n += 1) {
                    var node = nodes[n];
                    if (!node || node.nodeType !== 1) {
                        continue;
                    }
                    if (node.matches && (node.matches('.media-toolbar') || node.matches('.tablenav.top') || node.matches('.attachments-browser') || node.matches('#the-list'))) {
                        return true;
                    }
                    if (node.querySelector && node.querySelector('.media-toolbar, .tablenav.top, .attachments-browser, #the-list')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    function bindQuickTypeFilterObserver() {
        if (!isMediaPostType()) {
            return;
        }

        if (quickTypeFilterObserver && quickTypeFilterObserverTarget && document.body.contains(quickTypeFilterObserverTarget)) {
            return;
        }

        var target = isGridMode()
            ? document.querySelector('.attachments-browser')
            : document.querySelector('#the-list');

        if (!target || typeof MutationObserver === 'undefined') {
            return;
        }

        if (quickTypeFilterObserver) {
            quickTypeFilterObserver.disconnect();
        }

        quickTypeFilterObserverTarget = target;
        quickTypeFilterObserver = new MutationObserver(function (mutations) {
            if (!mutationsContainAttachmentChanges(mutations || [])) {
                return;
            }
            scheduleBwMfRefresh('types-observer');
        });
        quickTypeFilterObserver.observe(target, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }

    function bindQuickTypeLayoutObserver() {
        if (!isMediaPostType()) {
            return;
        }

        if (quickTypeLayoutObserver) {
            return;
        }

        if (typeof MutationObserver === 'undefined') {
            return;
        }

        var target = document.querySelector('#wpbody-content') || document.body;
        if (!target) {
            return;
        }

        quickTypeLayoutObserver = new MutationObserver(function (mutations) {
            if (!mutationsContainLayoutChanges(mutations || [])) {
                return;
            }
            scheduleBwMfRefresh('layout-observer');
        });
        quickTypeLayoutObserver.observe(target, { childList: true, subtree: true });
    }

    function bindQuickTypeFilterEvents() {
        if (!isMediaPostType()) {
            return;
        }

        if (quickTypeFiltersEventsBound) {
            return;
        }
        quickTypeFiltersEventsBound = true;

        $(document)
            .off('click.bwMfTypeFilters', '#bw-mf-type-filters .bw-mf-type-filter')
            .on('click.bwMfTypeFilters', '#bw-mf-type-filters .bw-mf-type-filter', function (e) {
                e.preventDefault();
                var filterKey = String($(this).attr('data-filter') || '');
                if (!filterKey) {
                    return;
                }

                if (quickTypeFilterActive === filterKey) {
                    quickTypeFilterActive = '';
                } else {
                    quickTypeFilterActive = filterKey;
                }
                scheduleQuickTypeFiltersRefresh();
            });
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

    function collectVisibleListRowIds(rows) {
        var ids = [];
        (rows || []).forEach(function (row) {
            if (!row || !row.id) {
                return;
            }

            var match = String(row.id).match(/^post-(\d+)$/);
            if (!match) {
                return;
            }

            var id = parseInt(match[1], 10);
            if (id > 0) {
                ids.push(id);
            }
        });

        return Array.from(new Set(ids)).slice(0, 200);
    }

    function getMarkerPendingBatch(maxBatch) {
        var batch = [];
        markerPendingIds.forEach(function (id) {
            if (batch.length >= maxBatch) {
                return;
            }
            batch.push(id);
        });
        return batch;
    }

    function flushMarkerWaiters() {
        if (!markerFetchWaiters.length) {
            return;
        }

        var waiters = markerFetchWaiters.slice();
        markerFetchWaiters = [];
        waiters.forEach(function (cb) {
            if (typeof cb === 'function') {
                cb();
            }
        });
    }

    function fetchCornerMarkers(ids, onDone) {
        if (typeof onDone === 'function') {
            markerFetchWaiters.push(onDone);
        }

        (ids || []).forEach(function (id) {
            var parsed = parseInt(id, 10);
            if (parsed > 0 && !markerCache.has(parsed) && !markerFailedIds.has(parsed)) {
                markerPendingIds.add(parsed);
            }
        });

        if (!markerPendingIds.size) {
            flushMarkerWaiters();
            return null;
        }

        if (markerFetchInFlight) {
            debugLog('corner markers fetch skipped (inFlight)', { pending: markerPendingIds.size });
            return markerFetchInFlight;
        }

        var batch = getMarkerPendingBatch(200);
        if (!batch.length) {
            flushMarkerWaiters();
            return null;
        }

        var requestData = {
            action: 'bw_mf_get_corner_markers',
            nonce: cfg.nonce,
            bw_mf_context: getActiveAjaxContext().screenContext,
        };
        if (isMediaPostType()) {
            requestData.attachment_ids = batch;
        } else {
            requestData.object_ids = batch;
        }

        markerFetchInFlight = $.post(cfg.ajaxUrl, requestData)
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
                        color: isValidHexColor(marker.color) ? marker.color : null,
                        folder_name: marker.folder_name ? String(marker.folder_name) : ''
                    });
                    markerFailedIds.delete(id);
                    markerPendingIds.delete(id);
                });
            })
            .fail(function () {
                batch.forEach(function (id) {
                    markerFailedIds.add(id);
                    markerPendingIds.delete(id);
                });
            })
            .always(function () {
                markerFetchInFlight = null;
                flushMarkerWaiters();
                debugLog('corner markers fetch done', { pending: markerPendingIds.size });
                if (markerPendingIds.size > 0) {
                    scheduleBwMfRefresh('corner-markers');
                }
            });

        return markerFetchInFlight;
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
            if (tile.hasAttribute('data-bw-mf-folder-name')) {
                tile.removeAttribute('data-bw-mf-folder-name');
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
        if (marker.folder_name) {
            tile.setAttribute('data-bw-mf-folder-name', marker.folder_name);
        } else if (tile.hasAttribute('data-bw-mf-folder-name')) {
            tile.removeAttribute('data-bw-mf-folder-name');
        }
    }

    function setListRowMarker(row, marker) {
        if (!row) {
            return;
        }

        var dragCell = row.querySelector('.column-bw_mf_drag_handle');
        if (!dragCell) {
            return;
        }

        var markerEl = dragCell.querySelector('.bw-mf-row-folder-marker');
        if (!marker || !marker.assigned) {
            if (markerEl && markerEl.parentNode) {
                markerEl.parentNode.removeChild(markerEl);
            }
            return;
        }

        if (!markerEl) {
            markerEl = document.createElement('span');
            markerEl.className = 'bw-mf-row-folder-marker';
            markerEl.setAttribute('aria-hidden', 'true');
            dragCell.appendChild(markerEl);
        }

        var targetColor = marker.color || '#000';
        if (markerEl.style.getPropertyValue('--bw-mf-marker-color') !== targetColor) {
            markerEl.style.setProperty('--bw-mf-marker-color', targetColor);
        }
        if (marker.folder_name) {
            markerEl.setAttribute('title', marker.folder_name);
        } else {
            markerEl.removeAttribute('title');
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
            var folder = state.folders.find(function (item) {
                return parseInt(item.id, 10) === viewCtx.folderId;
            });
            var folderName = folder && folder.name ? String(folder.name) : '';
            tiles.forEach(function (tile) {
                setTileCornerMarker(tile, {
                    assigned: true,
                    color: folderColor,
                    folder_name: folderName
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

    function bwMfApplyListRowMarkers() {
        if (!cornerIndicatorEnabled || isMediaPostType()) {
            clearListRowMarkers();
            return;
        }

        var rows = getVisibleListRows();
        if (!rows.length) {
            return;
        }

        var rowIds = collectVisibleListRowIds(rows);
        var missingIds = rowIds.filter(function (id) {
            return !markerCache.has(id) && !markerFailedIds.has(id);
        });

        var apply = function () {
            rows.forEach(function (row) {
                var match = String(row.id || '').match(/^post-(\d+)$/);
                var id = match ? parseInt(match[1], 10) : 0;
                setListRowMarker(row, markerCache.get(id));
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

        markerObserver = new MutationObserver(function (mutations) {
            if (!mutationsContainAttachmentChanges(mutations || [])) {
                return;
            }
            scheduleBwMfRefresh('marker-observer');
        });
        markerObserver.observe(attachmentsRoot, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }

    function ensureBadgeTooltipEl() {
        if (!cornerIndicatorEnabled || !badgeTooltipEnabled) {
            return null;
        }

        if (badgeTooltipEl && document.body.contains(badgeTooltipEl)) {
            return badgeTooltipEl;
        }

        badgeTooltipEl = document.createElement('div');
        badgeTooltipEl.id = 'bw-mf-badge-tooltip';
        badgeTooltipEl.className = 'bw-mf-badge-tooltip';
        badgeTooltipEl.setAttribute('aria-hidden', 'true');
        document.body.appendChild(badgeTooltipEl);
        return badgeTooltipEl;
    }

    function hideBadgeTooltip() {
        if (!badgeTooltipEl) {
            return;
        }

        badgeTooltipEl.classList.remove('is-visible');
        badgeTooltipEl.setAttribute('aria-hidden', 'true');
    }

    function showBadgeTooltipForTile(tile) {
        if (!cornerIndicatorEnabled || !badgeTooltipEnabled || !tile) {
            return;
        }

        var folderName = String(tile.getAttribute('data-bw-mf-folder-name') || '').trim();
        if (!folderName) {
            hideBadgeTooltip();
            return;
        }

        var tooltip = ensureBadgeTooltipEl();
        if (!tooltip) {
            return;
        }

        tooltip.textContent = folderName;

        var rect = tile.getBoundingClientRect();
        var x = rect.left + window.pageXOffset + 8;
        var y = rect.top + window.pageYOffset + 8;

        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
        tooltip.classList.add('is-visible');
        tooltip.setAttribute('aria-hidden', 'false');
    }

    function bindBadgeTooltipEvents() {
        if (!cornerIndicatorEnabled || !badgeTooltipEnabled || badgeTooltipEventsBound) {
            return;
        }

        ensureBadgeTooltipEl();
        badgeTooltipEventsBound = true;

        $(document).on('mouseenter.bwMfBadgeTooltip', '.attachments-browser .attachment.bw-mf-marked', function () {
            showBadgeTooltipForTile(this);
        });

        $(document).on('mousemove.bwMfBadgeTooltip', '.attachments-browser .attachment.bw-mf-marked', function () {
            showBadgeTooltipForTile(this);
        });

        $(document).on('mouseleave.bwMfBadgeTooltip', '.attachments-browser .attachment.bw-mf-marked', function () {
            hideBadgeTooltip();
        });
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

    function setCollectionFilterProps(collection, folderId, unassigned) {
        if (!collection || !collection.props || typeof collection.props.set !== 'function') {
            modalClickDebug('setCollectionFilterProps skipped: invalid collection', {
                folderId: folderId,
                unassigned: !!unassigned,
                collection: collection ? Object.keys(collection).slice(0, 12) : null
            });
            return false;
        }

        var folderValue = folderId > 0 ? folderId : '';
        var unassignedValue = unassigned ? '1' : '';
        var ignoreValue = String(+new Date());

        modalClickDebug('setCollectionFilterProps before', {
            folderId: folderId,
            unassigned: !!unassigned,
            propsBefore: getCollectionPropsSnapshot(collection)
        });

        setCollectionProp(collection, 'bw_media_folder', folderValue);
        setCollectionProp(collection, 'bw_media_unassigned', unassignedValue);
        setCollectionProp(collection, 'ignore', ignoreValue);
        setCollectionProp(collection, 'paged', 1);

        try {
            if (typeof collection.fetch === 'function') {
                modalClickDebug('setCollectionFilterProps calling fetch', {
                    folderId: folderId,
                    unassigned: !!unassigned
                });
                collection.fetch();
            }
        } catch (fetchErr) {
            modalClickDebug('setCollectionFilterProps fetch failed', {
                folderId: folderId,
                unassigned: !!unassigned,
                error: fetchErr && fetchErr.message ? String(fetchErr.message) : String(fetchErr)
            });
        }

        modalClickDebug('setCollectionFilterProps after', {
            folderId: folderId,
            unassigned: !!unassigned,
            propsAfter: getCollectionPropsSnapshot(collection)
        });

        return true;
    }

    function applyGridFilter(folderId, unassigned) {
        var modalActive = isModalSidebarActive();
        modalClickDebug('applyGridFilter invoked', {
            folderId: folderId,
            unassigned: !!unassigned,
            modalActive: modalActive,
            hasFrame: !!(modalActive ? modalState.frame : (window.wp && wp.media && wp.media.frame))
        });
        if (!modalActive && (state.mode !== 'grid' || !window.wp || !wp.media || !wp.media.frame)) {
            return false;
        }

        try {
            var frame = modalActive ? modalState.frame : wp.media.frame;
            var collection = null;

            if (modalActive) {
                try {
                    collection = frame && frame.state && frame.state() && frame.state().get ? frame.state().get('library') : null;
                } catch (stateErr) {
                    collection = null;
                    modalClickDebug('applyGridFilter modal library lookup failed', {
                        folderId: folderId,
                        unassigned: !!unassigned,
                        error: stateErr && stateErr.message ? String(stateErr.message) : String(stateErr)
                    });
                }
            } else {
                var collections = getModalMediaCollections(frame);
                modalClickDebug('applyGridFilter collections resolved', {
                    folderId: folderId,
                    unassigned: !!unassigned,
                    collectionsFound: collections.length,
                    hasFrame: !!frame,
                    frameType: frame && frame.constructor && frame.constructor.name ? frame.constructor.name : '',
                    hasContentCollection: !!(frame && frame.content && frame.content.get && frame.content.get() && frame.content.get().collection)
                });
                collection = collections.length ? collections[0] : null;
            }

            if (!collection || !collection.props || typeof collection.props.set !== 'function') {
                return false;
            }

            if (modalActive) {
                modalState.activeFolder = folderId > 0 ? folderId : 0;
                modalState.activeUnassigned = !!unassigned;
            } else {
                state.activeFolder = folderId > 0 ? folderId : 0;
                state.activeUnassigned = !!unassigned;
            }

            setCollectionFilterProps(collection, folderId, unassigned);
            if (modalActive) {
                modalClickDebug('applyGridFilter modal collection resolved', {
                    folderId: folderId,
                    unassigned: !!unassigned,
                    propsAfter: getCollectionPropsSnapshot(collection),
                    hasFetch: typeof collection.fetch === 'function',
                    hasMore: typeof collection.more === 'function',
                    hasReset: typeof collection.reset === 'function'
                });
            }

            if (typeof collection.reset === 'function') {
                modalClickDebug('applyGridFilter calling reset', {
                    folderId: folderId,
                    unassigned: !!unassigned
                });
                collection.reset();
            }
            if (typeof collection.more === 'function') {
                modalClickDebug('applyGridFilter calling more', {
                    folderId: folderId,
                    unassigned: !!unassigned
                });
                collection.more();
            } else if (typeof collection.fetch === 'function') {
                modalClickDebug('applyGridFilter calling fetch', {
                    folderId: folderId,
                    unassigned: !!unassigned
                });
                collection.fetch();
            }

            bindQuickTypeFilterObserver();
            scheduleBwMfRefresh('apply-grid-filter');
            return true;
        } catch (e) {
            return false;
        }
    }

    function nodeHtml(item, depth, viewState) {
        var activeState = viewState || state;
        var pad = Math.max(0, depth) * 14;
        var pinnedClass = item.pinned ? ' is-pinned' : '';
        var active = (!activeState.activeUnassigned && activeState.activeFolder === item.id) ? ' is-active' : '';
        var isCollapsed = !!folderCollapsedMap[item.id];
        var hasChildren = !!(folderByParentMap[item.id] && folderByParentMap[item.id].length);
        var styles = ['padding-left:' + pad + 'px'];
        var iconColor = sanitizeInlineColor(item.icon_color ? item.icon_color : (item.color ? item.color : ''));
        var iconColorEsc = iconColor ? escapeAttr(iconColor) : '';
        var iconColorAttr = '';
        var iconInlineAttr = '';
        var iconSvgInlineAttr = '';
        var iconPathFillAttr = ' fill="currentColor"';
        var iconPathStyleAttr = '';
        var pinnedAttr = item.pinned ? '1' : '0';
        var collapsedAttr = isCollapsed ? '1' : '0';
        var pinIndicator = '<span class="bw-mf-pin bw-mf-pin-indicator" aria-hidden="true">' + (item.pinned ? '📌' : '') + '</span>';
        var chevron = hasChildren
            ? '<button class="bw-mf-chevron" type="button" aria-label="Toggle folder" aria-expanded="' + (isCollapsed ? 'false' : 'true') + '">▶</button>'
            : '';

        if (iconColor) {
            styles.push('--bw-mf-icon-color:' + iconColorEsc);
            iconColorAttr = ' data-icon-color="' + iconColorEsc + '"';
            iconInlineAttr = ' style="color:' + iconColorEsc + '; fill:' + iconColorEsc + ';"';
            iconSvgInlineAttr = ' style="color:' + iconColorEsc + '; fill:' + iconColorEsc + ';"';
            iconPathFillAttr = ' fill="' + iconColorEsc + '"';
            iconPathStyleAttr = ' style="fill:' + iconColorEsc + ';"';
        }

        return '' +
            '<div class="bw-media-folder-node' + pinnedClass + active + (hasChildren ? ' is-parent' : '') + (isCollapsed ? ' is-collapsed' : '') + '" data-id="' + item.id + '" data-term-id="' + item.id + '" data-folder-id="' + item.id + '" data-parent="' + item.parent + '" data-pinned="' + pinnedAttr + '" data-collapsed="' + collapsedAttr + '"' + iconColorAttr + ' style="' + styles.join(';') + '">' +
            '  <div class="bw-media-folder-node__main" role="button" tabindex="0">' +
            '    <span class="bw-mf-left">' +
            chevron +
            '      <span class="bw-mf-folder-icon" aria-hidden="true"' + iconInlineAttr + '>' +
            '        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"' + iconSvgInlineAttr + '>' +
            '          <path' + iconPathFillAttr + iconPathStyleAttr + ' d="M5.5 5 H9.8 A1.6 1.6 0 0 1 11 5.6 L12.2 7 A1.6 1.6 0 0 0 13.4 7.6 H18.5 A1.5 1.5 0 0 1 20 9.1 V18.5 A1.5 1.5 0 0 1 18.5 20 H5.5 A1.5 1.5 0 0 1 4 18.5 V6.5 A1.5 1.5 0 0 1 5.5 5 Z"></path>' +
            '        </svg>' +
            '      </span>' +
            '      <span class="bw-media-folder-node__name bw-mf-folder-name">' + item.name + '</span>' +
            '    </span>' +
            '    <span class="bw-mf-right">' +
            pinIndicator +
            '      <span class="bw-media-folder-node__count bw-mf-count">' + item.count + '</span>' +
            '      <button class="bw-mf-folder-pencil bw-mf-folder-rename-btn" type="button" aria-label="Folder actions">' +
            '          <span class="dashicons dashicons-edit" aria-hidden="true"></span>' +
            '      </button>' +
            '    </span>' +
            '  </div>' +
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
            '  <button type="button" class="bw-mf-context-menu__item" data-cmd="new-subfolder"><span class="dashicons dashicons-category" aria-hidden="true"></span><span>New Subfolder</span></button>' +
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

        var indicator = rowEl.querySelector('.bw-mf-pin');
        if (indicator) {
            indicator.textContent = isPinned ? '📌' : '';
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

    function renderDefaults(viewState, selectors, isMedia) {
        var activeState = viewState || state;
        var sel = selectors || {};
        var target = sel.defaults || '#bw-media-folders-defaults';
        var mediaMode = typeof isMedia === 'boolean' ? isMedia : isMediaPostType();
        var html = '';
        var allClass = (!activeState.activeFolder && !activeState.activeUnassigned) ? ' is-active' : '';
        var unClass = activeState.activeUnassigned ? ' is-active' : '';
        var allLabel = mediaMode ? 'All Files' : 'All Items';
        var unassignedLabel = mediaMode ? 'Unassigned Files' : 'Unassigned Items';

        html += '<button type="button" class="bw-media-default' + allClass + '" data-type="all">' + allLabel + ' <span>' + (activeState.counts.all || 0) + '</span></button>';
        html += '<button type="button" class="bw-media-default bw-media-default--drop' + unClass + '" data-type="unassigned" data-term-id="0" data-folder-id="0">' + unassignedLabel + ' <span>' + (activeState.counts.unassigned || 0) + '</span></button>';

        $(target).html(html);
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
        if (isModalSidebarActive()) {
            syncModalTreeNodeVisibility();
        }
    }

    function getModalTreeNodesByParent(parentId) {
        return Array.prototype.slice.call(document.querySelectorAll('#bw-media-folders-modal-tree .bw-media-folder-node[data-parent="' + parentId + '"]'));
    }

    function setModalNodeVisibilityByParent(parentId, hidden) {
        var nodes = getModalTreeNodesByParent(parentId);
        nodes.forEach(function (node) {
            if (hidden) {
                $(node).hide();
            } else {
                $(node).show();
            }
            var chevron = node.querySelector('.bw-mf-chevron');
            if (chevron) {
                chevron.setAttribute('aria-expanded', hidden ? 'false' : 'true');
                chevron.classList.toggle('is-collapsed', !!hidden);
            }

            var childId = parseInt(node.getAttribute('data-id') || '0', 10);
            if (childId > 0) {
                var childCollapsed = !!folderCollapsedMap[childId];
                if (hidden) {
                    setModalNodeVisibilityByParent(childId, true);
                } else {
                    setModalNodeVisibilityByParent(childId, childCollapsed);
                }
            }
        });
    }

    function buildTreeRows(viewState) {
        var activeState = viewState || state;
        var byParent = {};

        activeState.folders.forEach(function (item) {
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
                out.push(nodeHtml(child, depth, activeState));
                walk(child.id, depth + 1, out);
            });
        }

        var rows = [];
        walk(0, 0, rows);
        return rows;
    }

    function syncTreeNodeVisibility(viewState, selectors) {
        var sel = selectors || {};
        var searchSelector = sel.search || '#bw-mr-folder-search';
        var treeSelector = sel.tree || '#bw-media-folders-tree';
        var searchTerm = ($(searchSelector).val() || '').toLowerCase().trim();
        var nodes = Array.prototype.slice.call(document.querySelectorAll(treeSelector + ' .bw-media-folder-node'));
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

            node.style.display = matchesSearch ? '' : 'none';
            node.classList.toggle('bw-mf-hidden-by-collapse', !!hiddenByCollapse);
            node.classList.toggle('is-collapsed', !!folderCollapsedMap[id]);

            var chevron = node.querySelector('.bw-mf-chevron');
            if (chevron) {
                chevron.setAttribute('aria-expanded', folderCollapsedMap[id] ? 'false' : 'true');
                chevron.classList.toggle('is-collapsed', !!folderCollapsedMap[id]);
            }
        });
    }

    function renderTree(viewState, selectors) {
        var activeState = viewState || state;
        var sel = selectors || {};
        var treeSelector = sel.tree || '#bw-media-folders-tree';
        var bulkSelector = sel.bulk || '#bw-media-folders-bulk-select';

        $(treeSelector).html(buildTreeRows(activeState).join(''));

        var options = ['<option value="0">Unassigned</option>'];
        activeState.folders.forEach(function (item) {
            options.push('<option value="' + item.id + '">' + item.name + '</option>');
        });
        if (bulkSelector) {
            $(bulkSelector).html(options.join(''));
        }

        syncTreeNodeVisibility(activeState, selectors);
        bindDropTargets();
        debugAssertFolderRowLayout();
    }

    function debugAssertFolderRowLayout() {
        if (!window.BW_MF_DEBUG) {
            return;
        }

        var nodes = document.querySelectorAll('#bw-media-folders-tree .bw-media-folder-node');
        Array.prototype.forEach.call(nodes, function (node) {
            var main = node.querySelector('.bw-media-folder-node__main');
            var left = node.querySelector('.bw-mf-left');
            var right = node.querySelector('.bw-mf-right');
            if (!main || !left || !right) {
                console.warn('[BW_MF_DEBUG] invalid row structure', node);
                return;
            }

            var styles = window.getComputedStyle(main);
            if (styles.justifyContent === 'space-between') {
                console.warn('[BW_MF_DEBUG] invalid main justify-content', node);
            }
        });
    }

    function refreshTree() {
        request('bw_media_get_folders_tree', {}, function (data) {
            state.folders = Array.isArray(data.folders) ? data.folders : [];
            state.counts = data.counts || { all: 0, unassigned: 0 };
            renderDefaults();
            renderTree();
            if (isModalSidebarActive()) {
                modalState.folders = state.folders;
                modalState.counts = state.counts;
                renderModalDefaults();
                renderModalTree();
            }
            scheduleBwMfRefresh('refresh-tree');
        }, {
            silent: !isSupportedListScreen()
        });
    }

    function getModalSidebarMarkup() {
        var html = String(cfg.sidebarHtml || '');
        if (!html) {
            return '';
        }

        return html
            .replace(/id="bw-media-folders-root"/g, 'id="bw-media-folders-modal-root"')
            .replace(/id="bw-media-folders-toggle"/g, 'id="bw-media-folders-toggle-modal"')
            .replace(/id="bw-mr-new-folder-btn"/g, 'id="bw-mr-new-folder-btn-modal"')
            .replace(/id="bw-mr-folder-search"/g, 'id="bw-mr-folder-search-modal"')
            .replace(/id="bw-media-folders-defaults"/g, 'id="bw-media-folders-modal-defaults"')
            .replace(/id="bw-media-folders-tree"/g, 'id="bw-media-folders-modal-tree"')
            .replace(/id="bw-media-folders-bulk-select"/g, 'id="bw-media-folders-modal-bulk-select"')
            .replace(/id="bw-media-folders-bulk-btn"/g, 'id="bw-media-folders-modal-bulk-btn"')
            .replace(/id="bw-mf-collapse-tab"/g, 'id="bw-mf-collapse-tab-modal"');
    }

    function getModalSidebarRoot() {
        return $(modalSelectors.root);
    }

    function getModalRootElement() {
        var roots = document.querySelectorAll('.media-modal');
        if (!roots || !roots.length) {
            return $();
        }

        return $(roots[roots.length - 1]);
    }

    function getModalSidebarContainer(frame) {
        if (!frame || !frame.$el || !frame.$el.length) {
            return $();
        }

        var content = frame.$el.find('.media-frame-content, .media-frame-browse').first();
        if (content && content.length) {
            return content;
        }

        return frame.$el;
    }

    function getModalSidebarToggle() {
        return $(modalSelectors.collapseTab);
    }

    function ensureModalSidebarToggle(root, container) {
        var toggle = getModalSidebarToggle();
        var $root = root && root.length ? root : getModalSidebarRoot();
        var $container = container && container.length ? container : getModalSidebarContainer(modalState.frame ? { $el: modalState.frame.$el } : null);

        if (!toggle.length) {
            toggle = $('<button type="button" id="bw-mf-collapse-tab-modal" class="bw-mf-modal-sidebar-toggle"></button>');
        }

        if ($root.length) {
            if (!$.contains(document, toggle[0])) {
                $root.before(toggle);
            }
        } else if ($container.length && !$.contains(document, toggle[0])) {
            $container.prepend(toggle);
        }

        return toggle;
    }

    function syncModalSidebarToggleLabel() {
        var toggle = getModalSidebarToggle();
        if (!toggle.length) {
            return;
        }

        var collapsed = !!modalState.sidebarCollapsed;
        toggle
            .attr('aria-expanded', collapsed ? 'false' : 'true')
            .text(collapsed ? 'Open folders' : 'Close folders');
    }

    function setModalSidebarCollapsed(collapsed, options) {
        var isCollapsed = !!collapsed;
        var opts = options || {};
        modalState.sidebarCollapsed = isCollapsed;

        var modalRoot = getModalRootElement();
        if (modalRoot.length) {
            modalRoot.toggleClass('bw-mf-modal-sidebar-collapsed', isCollapsed);
        }

        syncModalSidebarToggleLabel();

        if (modalState.active && modalState.frame && !opts.skipResize) {
            refreshModalLayout(modalState.frame);
        }
    }

    function renderModalDefaults() {
        renderDefaults(modalState, {
            defaults: modalSelectors.defaults
        }, true);
    }

    function renderModalTree() {
        renderTree(modalState, {
            tree: modalSelectors.tree,
            bulk: modalSelectors.bulk,
            search: modalSelectors.search,
            isMedia: true
        });
    }

    function syncModalTreeNodeVisibility() {
        syncTreeNodeVisibility(modalState, {
            tree: modalSelectors.tree,
            search: modalSelectors.search
        });
    }

    function setModalGridFilter(folderId, unassigned) {
        modalState.activeFolder = folderId > 0 ? folderId : 0;
        modalState.activeUnassigned = !!unassigned;
        modalState.active = true;
        applyGridFilter(folderId, unassigned);
        renderModalDefaults();
        renderModalTree();
    }

    function clearModalSidebar(frame) {
        if (mediaModalMountTimer) {
            window.clearTimeout(mediaModalMountTimer);
            mediaModalMountTimer = null;
        }

        var root = getModalSidebarRoot();
        if (root.length) {
            var shell = root.closest(modalSelectors.shell);
            if (shell.length) {
                shell.remove();
            } else {
                root.remove();
            }
        }

        $(modalSelectors.collapseTab).remove();

        var container = frame && frame.$el ? frame.$el.find('.media-frame-content, .media-frame-browse').first() : $();
        if (container && container.length) {
            container.removeClass('bw-mf-modal-has-sidebar');
        }

        var browser = frame && frame.$el ? frame.$el.find('.attachments-browser').first() : $();
        var browserParent = browser.length ? browser.parent() : $();
        if (browserParent && browserParent.length) {
            browserParent.removeClass('bw-mf-modal-has-sidebar');
        }

        var modalRoot = getModalRootElement();
        if (modalRoot.length) {
            modalRoot.removeClass('bw-mf-modal-has-sidebar');
            modalRoot.removeClass('bw-mf-modal-sidebar-collapsed');
        }

        modalState.active = false;
        modalState.frame = null;
        modalState.activeFolder = 0;
        modalState.activeUnassigned = false;
        modalState.sidebarCollapsed = false;
        modalState.folders = [];
        modalState.counts = { all: 0, unassigned: 0 };
        updateMediaFoldersDiag({
            sidebarInjected: false,
            lastMountAttempt: window.BW_MEDIA_FOLDERS_DIAG && window.BW_MEDIA_FOLDERS_DIAG.lastMountAttempt ? window.BW_MEDIA_FOLDERS_DIAG.lastMountAttempt : 0,
            lastMissingSelector: '',
            frameObjectExists: !!frame,
            frameElExists: !!(frame && frame.$el && frame.$el.length),
            modalDomFallbackUsed: false,
            modalRootSelector: ''
        });
    }

    function refreshModalLayout(frame) {
        if (!frame || !frame.$el || !frame.$el.length) {
            return;
        }

        var resizeFrame = function () {
            try {
                if (typeof frame.trigger === 'function') {
                    frame.trigger('resize');
                }
            } catch (err) {
                // ignore resize errors
            }

            try {
                if (window.wp && wp.media && wp.media.frame && typeof wp.media.frame.trigger === 'function') {
                    wp.media.frame.trigger('resize');
                }
            } catch (err2) {
                // ignore resize errors
            }

            window.dispatchEvent(new Event('resize'));
        };

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(resizeFrame);
            });
        } else {
            window.setTimeout(resizeFrame, 0);
        }
    }

    function renderModalSidebar(frame, attemptNumber) {
        if (!frame || !cfg.sidebarHtml) {
            mediaFoldersDebugLog('renderModalSidebar aborted: missing frame or sidebar html', {
                attempt: attemptNumber || 0,
                hasFrame: !!frame,
                hasFrameEl: !!(frame && frame.$el && frame.$el.length),
                hasSidebarHtml: !!cfg.sidebarHtml
            });
            return false;
        }

        var modalRoot = getModalRootElement();
        var frameEl = frame && frame.$el && frame.$el.length ? frame.$el : modalRoot;
        var modalDomFallbackUsed = !(frame && frame.$el && frame.$el.length) && !!modalRoot.length;
        var modalRootSelector = modalRoot.length ? '.media-modal:last' : '';

        if (!frameEl.length) {
            updateMediaFoldersDiag({
                lastMissingSelector: '.media-modal',
                sidebarInjected: false,
                frameObjectExists: !!frame,
                frameElExists: false,
                modalDomFallbackUsed: false,
                modalRootSelector: ''
            });
            mediaFoldersDebugLog('renderModalSidebar failed: missing modal root', {
                attempt: attemptNumber || 0
            });
            return false;
        }

        var modalExists = !!document.querySelector('.media-modal');
        var frameContentExists = !!frameEl.find('.media-frame-content').length;
        var frameBrowseExists = !!frameEl.find('.media-frame-browse').length;
        var attachmentsBrowser = frameEl.find('.attachments-browser').first();
        var attachmentsBrowserExists = !!attachmentsBrowser.length;
        var sidebarMarkup = getModalSidebarMarkup();
        var root = getModalSidebarRoot();
        var frameLabel = getMediaFrameLabel(frame);
        mediaFoldersDebugLog('renderModalSidebar attempt', {
            attempt: attemptNumber || 0,
            modalExists: modalExists,
            mediaFrameContentExists: frameContentExists,
            mediaFrameBrowseExists: frameBrowseExists,
            attachmentsBrowserExists: attachmentsBrowserExists,
            sidebarShellExists: !!root.length,
            modalDomFallbackUsed: modalDomFallbackUsed,
            modalRootSelector: modalRootSelector
        });
        if (frameLabel === 'Post') {
            classicModalDebugLog('renderModalSidebar attempt', {
                attempt: attemptNumber || 0,
                attachmentsBrowserExists: attachmentsBrowserExists,
                frame: getMediaFrameDebugMeta(frame)
            });
        }
        updateMediaFoldersDiag({
            lastMountAttempt: attemptNumber || 0
            ,
            lastMountReason: '',
            lastMountFrameType: (frame && frame.constructor && frame.constructor.name) ? String(frame.constructor.name) : (frame && frame.cid ? String(frame.cid) : ''),
            lastModalExists: modalExists,
            lastContentExists: frameContentExists,
            lastBrowserExists: frameBrowseExists,
            lastAttachmentsBrowserExists: attachmentsBrowserExists,
            lastExistingSidebarCount: document.querySelectorAll(modalSelectors.root).length,
            lastSidebarMarkupLength: sidebarMarkup ? sidebarMarkup.length : 0,
            lastInsertionTarget: '',
            lastInjectionError: '',
            frameObjectExists: !!frame,
            frameElExists: !!(frame && frame.$el && frame.$el.length),
            modalDomFallbackUsed: modalDomFallbackUsed,
            modalRootSelector: modalRootSelector
        });

        var container = getModalSidebarContainer({ $el: frameEl });
        if (!container.length) {
            updateMediaFoldersDiag({
                lastMissingSelector: '.media-frame-content or .media-frame-browse',
                sidebarInjected: false
            });
            mediaFoldersDebugLog('renderModalSidebar failed: missing container', {
                attempt: attemptNumber || 0,
                missing: '.media-frame-content or .media-frame-browse'
            });
            return false;
        }

        var browser = attachmentsBrowser;
        var browserParent = browser.length ? browser.parent() : $();
        if (!root.length) {
            var html = sidebarMarkup;
            if (!html) {
                updateMediaFoldersDiag({
                    lastMissingSelector: '#bw-media-folders-modal-root',
                    sidebarInjected: false
                });
                mediaFoldersDebugLog('renderModalSidebar failed: missing sidebar markup', {
                    attempt: attemptNumber || 0
                });
                return false;
            }

            if (!browser.length) {
                updateMediaFoldersDiag({
                    lastMissingSelector: '.attachments-browser',
                    sidebarInjected: false
                });
                if (frameLabel === 'Post') {
                    classicModalDebugLog('renderModalSidebar failed: missing attachments browser', {
                        frame: getMediaFrameDebugMeta(frame)
                    });
                }
                mediaFoldersDebugLog('renderModalSidebar failed: missing selector', {
                    attempt: attemptNumber || 0,
                    missing: '.attachments-browser'
                });
                return false;
            }

            updateMediaFoldersDiag({
                lastInsertionTarget: '.attachments-browser.prepend(#bw-media-folders-modal-root)'
            });
            try {
                browser.prepend(html);
            } catch (err) {
                updateMediaFoldersDiag({
                    lastInjectionError: err && err.message ? String(err.message) : 'insertion failed',
                    sidebarInjected: false
                });
                mediaFoldersDebugLog('renderModalSidebar failed: injection error', {
                    attempt: attemptNumber || 0,
                    error: err && err.message ? String(err.message) : err
                });
                return false;
            }
            root = getModalSidebarRoot();
            if (root.length) {
                root.addClass('bw-mf-modal-sidebar-shell');
            }
            ensureModalSidebarToggle(root, container);
            mediaFoldersDebugLog('renderModalSidebar injection attempted', {
                attempt: attemptNumber || 0,
                injected: !!root.length
            });
            updateMediaFoldersDiag({
                lastExistingSidebarCount: document.querySelectorAll(modalSelectors.root).length,
                sidebarInjected: !!root.length,
                lastInjectionError: root.length ? '' : (window.BW_MEDIA_FOLDERS_DIAG && window.BW_MEDIA_FOLDERS_DIAG.lastInjectionError ? window.BW_MEDIA_FOLDERS_DIAG.lastInjectionError : '')
            });

            if (!root.length && container.length) {
                updateMediaFoldersDiag({
                    lastInsertionTarget: '.media-frame-content.prepend(#bw-media-folders-modal-root)'
                });
                try {
                    container.prepend(html);
                } catch (fallbackErr) {
                    updateMediaFoldersDiag({
                        lastInjectionError: fallbackErr && fallbackErr.message ? String(fallbackErr.message) : 'fallback insertion failed',
                        sidebarInjected: false
                    });
                    mediaFoldersDebugLog('renderModalSidebar failed: fallback injection error', {
                        attempt: attemptNumber || 0,
                        error: fallbackErr && fallbackErr.message ? String(fallbackErr.message) : fallbackErr
                    });
                    return false;
                }

                root = getModalSidebarRoot();
                if (root.length) {
                    root.addClass('bw-mf-modal-sidebar-shell');
                }
                ensureModalSidebarToggle(root, container);
                updateMediaFoldersDiag({
                    lastExistingSidebarCount: document.querySelectorAll(modalSelectors.root).length,
                    sidebarInjected: !!root.length,
                    lastInjectionError: root.length ? '' : 'fallback insertion produced no root'
                });
                mediaFoldersDebugLog('renderModalSidebar fallback injection attempted', {
                    attempt: attemptNumber || 0,
                    injected: !!root.length
                });
            }
        }

        if (!root.length) {
            updateMediaFoldersDiag({
                lastMissingSelector: '#bw-media-folders-modal-root',
                sidebarInjected: false
            });
            mediaFoldersDebugLog('renderModalSidebar failed after injection attempt', {
                attempt: attemptNumber || 0,
                missing: '#bw-media-folders-modal-root'
            });
            return false;
        }

        ensureModalSidebarToggle(root, container);

        container.addClass('bw-mf-modal-has-sidebar');
        if (browserParent && browserParent.length) {
            browserParent.addClass('bw-mf-modal-has-sidebar');
        } else if (modalRoot.length) {
            modalRoot.addClass('bw-mf-modal-has-sidebar');
        }
        if (modalRoot.length) {
            modalRoot.addClass('bw-mf-modal-has-sidebar');
        }
        modalState.active = true;
        modalState.frame = frame;
        modalState.activeFolder = 0;
        modalState.activeUnassigned = false;
        if (!state.folders.length) {
            refreshTree();
        }
        modalState.folders = state.folders;
        modalState.counts = state.counts;
        setModalSidebarCollapsed(modalState.sidebarCollapsed, { skipResize: true });
        updateMediaFoldersDiag({
            sidebarInjected: true,
            lastMissingSelector: '',
            lastExistingSidebarCount: document.querySelectorAll(modalSelectors.root).length,
            lastInjectionError: '',
            modalDomFallbackUsed: modalDomFallbackUsed,
            modalRootSelector: modalRootSelector
        });

        renderModalDefaults();
        renderModalTree();
        bindModalEvents();
        applyGridFilter(0, false);
        refreshModalLayout(frame);
        if (frameLabel === 'Post') {
            classicModalDebugLog('renderModalSidebar success', {
                attempt: attemptNumber || 0,
                injected: !!getModalSidebarRoot().length,
                frame: getMediaFrameDebugMeta(frame)
            });
        }
        mediaFoldersDebugLog('renderModalSidebar success', {
            attempt: attemptNumber || 0,
            activeFolder: modalState.activeFolder,
            activeUnassigned: modalState.activeUnassigned
        });
        return true;
    }

    function scheduleModalSidebarMount(frame, source) {
        if (mediaModalMountTimer) {
            window.clearTimeout(mediaModalMountTimer);
        }

        lastKnownMediaFrame = frame || lastKnownMediaFrame;
        mediaModalMountAttempts += 1;
        var attemptNumber = mediaModalMountAttempts;
        var frameLabel = getMediaFrameLabel(frame);
        mediaFoldersDebugLog('scheduleModalSidebarMount', {
            attempt: attemptNumber,
            source: source || '',
            state: mediaFoldersDebugState(frame)
        });
        if (frameLabel === 'Post') {
            classicModalDebugLog('scheduleModalSidebarMount', {
                attempt: attemptNumber,
                source: source || '',
                frame: getMediaFrameDebugMeta(frame)
            });
        }
        updateMediaFoldersDiag({
            lastMountAttempt: attemptNumber,
            lastMountReason: source || '',
            lastMountFrameType: (frame && frame.constructor && frame.constructor.name) ? String(frame.constructor.name) : (frame && frame.cid ? String(frame.cid) : ''),
            lastModalExists: !!document.querySelector('.media-modal'),
            lastContentExists: !!(frame && frame.$el && frame.$el.find('.media-frame-content').length),
            lastBrowserExists: !!(frame && frame.$el && frame.$el.find('.media-frame-browse').length),
            lastAttachmentsBrowserExists: !!(frame && frame.$el && frame.$el.find('.attachments-browser').length),
            lastExistingSidebarCount: document.querySelectorAll(modalSelectors.root).length
        });

        mediaModalMountTimer = window.setTimeout(function () {
            mediaModalMountTimer = null;
            var mounted = renderModalSidebar(frame, attemptNumber);
            if (!mounted) {
                mediaFoldersDebugLog('scheduleModalSidebarMount retry queued', {
                    attempt: attemptNumber,
                    retryDelayMs: 150
                });
                window.setTimeout(function () {
                    mediaModalMountAttempts += 1;
                    renderModalSidebar(frame, mediaModalMountAttempts);
                }, 150);
            }
        }, 0);
    }

    function attemptClassicModalMount(source) {
        var frame = (window.wp && wp.media && wp.media.frame) ? wp.media.frame : lastKnownMediaFrame;
        if (!frame) {
            classicModalDebugLog('attemptClassicModalMount skipped: no frame', {
                source: source || ''
            });
            return false;
        }

        lastKnownMediaFrame = frame;
        bindExistingMediaFrameInstance(source || 'classic-attempt');

        var hasModal = !!document.querySelector('.media-modal');
        var hasRoot = !!document.querySelector('#bw-media-folders-modal-root');
        classicModalDebugLog('attemptClassicModalMount', {
            source: source || '',
            hasModal: hasModal,
            hasRoot: hasRoot,
            frame: getMediaFrameDebugMeta(frame),
            prototypeChain: getPrototypeChainNames(frame)
        });

        if (hasModal && !hasRoot) {
            scheduleModalSidebarMount(frame, source || 'classic-attempt');
            window.setTimeout(function () {
                if (!document.querySelector('#bw-media-folders-modal-root')) {
                    renderModalSidebar(frame, 'classic-fallback');
                }
            }, 80);
        }

        return true;
    }

    function startClassicModalBindLoop(source) {
        classicModalBindDeadline = Date.now() + 2000;

        if (classicModalBindTimer) {
            return;
        }

        classicModalDebugLog('startClassicModalBindLoop', {
            source: source || '',
            deadline: classicModalBindDeadline
        });

        classicModalBindTimer = window.setInterval(function () {
            if (Date.now() > classicModalBindDeadline) {
                window.clearInterval(classicModalBindTimer);
                classicModalBindTimer = null;
                classicModalDebugLog('classic bind loop finished', {
                    source: source || ''
                });
                return;
            }

            attemptClassicModalMount(source || 'classic-bind-loop');
        }, 100);
    }

    function bindClassicModalObserver() {
        if (classicModalObserverBound || !window.MutationObserver || !document.body) {
            return;
        }

        classicModalObserverBound = true;
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (!mutation.addedNodes || !mutation.addedNodes.length) {
                    return;
                }

                Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }

                    if (
                        (node.matches && node.matches('.media-modal')) ||
                        (node.querySelector && node.querySelector('.media-modal'))
                    ) {
                        classicModalDebugLog('MutationObserver detected media modal', {
                            hasRoot: !!document.querySelector('#bw-media-folders-modal-root')
                        });
                        attemptClassicModalMount('mutation-observer');
                        startClassicModalBindLoop('mutation-observer');
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    function bindModalEvents() {
        if (mediaModalEventsBound) {
            return;
        }
        mediaModalEventsBound = true;

        $(document).on('click.bwMfModalSidebar', '#bw-media-folders-modal-root .bw-media-default', function (e) {
            modalClickDebug('All Files / Unassigned click', {
                element: this,
                target: e.target,
                currentTarget: e.currentTarget,
                dataType: $(this).attr('data-type') || '',
                rootNode: $(this).closest('#bw-media-folders-modal-root')[0] || null
            });
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            var library = null;
            try {
                library = wp.media.frame.state().get('library');
            } catch (err) {
                library = null;
            }

            modalClickDebug('All Files / Unassigned library resolved', {
                hasLibrary: !!library,
                propsBefore: getCollectionPropsSnapshot(library)
            });

            if (!library || !library.props || typeof library.props.set !== 'function') {
                modalClickDebug('All Files / Unassigned skipped: no library', {});
                return;
            }

            var type = $(this).attr('data-type');
            if (type === 'unassigned') {
                library.props.set({
                    bw_media_unassigned: 1,
                    paged: 1
                });
                if (typeof library.props.unset === 'function') {
                    library.props.unset('bw_media_folder');
                }
                if (typeof library.reset === 'function') {
                    library.reset();
                }
                if (typeof library.more === 'function') {
                    library.more();
                }
                modalClickDebug('All Files / Unassigned applied unassigned', {
                    propsAfter: getCollectionPropsSnapshot(library)
                });
                return;
            }

            if (typeof library.props.unset === 'function') {
                library.props.unset('bw_media_folder');
                library.props.unset('bw_media_unassigned');
            }
            library.props.set('paged', 1);
            if (typeof library.reset === 'function') {
                library.reset();
            }
            if (typeof library.more === 'function') {
                library.more();
            }
            modalClickDebug('All Files applied all files', {
                propsAfter: getCollectionPropsSnapshot(library)
            });
        });

        $(document).on('click.bwMfModalSidebar', '#bw-mf-collapse-tab-modal', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            setModalSidebarCollapsed(!modalState.sidebarCollapsed);
        });

        $(document).on('click.bwMfModalSidebar', '#bw-media-folders-modal-root .bw-media-folder-node__main', function (e) {
            var row = $(this).closest('.bw-media-folder-node');
            var folderId = resolveFolderIdFromNode(row);
            modalClickDebug('Folder row click', {
                element: this,
                target: e.target,
                currentTarget: e.currentTarget,
                row: row[0] || null,
                resolvedFolderId: folderId,
                frameExists: !!modalState.frame,
                collectionsFound: getModalMediaCollections(modalState.frame).length,
                propsBefore: (function () {
                    var collections = getModalMediaCollections(modalState.frame);
                    return collections.length ? getCollectionPropsSnapshot(collections[0]) : null;
                })()
            });
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            if ($(e.target).closest('.bw-mf-chevron').length) {
                return;
            }
            if ($(e.target).closest('.bw-mf-folder-pencil').length) {
                return;
            }

            var library = null;
            try {
                library = wp.media.frame.state().get('library');
            } catch (err2) {
                library = null;
            }

            modalClickDebug('Folder row library resolved', {
                hasLibrary: !!library,
                propsBefore: getCollectionPropsSnapshot(library)
            });

            if (!library || !library.props || typeof library.props.set !== 'function') {
                modalClickDebug('Folder row skipped: no library', {
                    resolvedFolderId: folderId
                });
                return;
            }

            library.props.set({
                bw_media_folder: folderId,
                bw_media_unassigned: null,
                paged: 1
            });
            if (typeof library.props.unset === 'function') {
                library.props.unset('bw_media_unassigned');
            }
            if (typeof library.reset === 'function') {
                library.reset();
            }
            if (typeof library.more === 'function') {
                library.more();
            }
            modalClickDebug('Folder row applied folder', {
                resolvedFolderId: folderId,
                propsAfter: getCollectionPropsSnapshot(library)
            });
        });

        $(document).on('keydown.bwMfModalSidebar', '#bw-media-folders-modal-root .bw-media-folder-node__main', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            e.preventDefault();
            $(this).trigger('click');
        });

        $(document).on('input.bwMfModalSidebar', '#bw-mr-folder-search-modal', function () {
            syncModalTreeNodeVisibility();
        });

        $(document).on('click.bwMfModalSidebar', '#bw-media-folders-modal-root .bw-mf-chevron', function (e) {
            var row = $(this).closest('.bw-media-folder-node');
            var termId = resolveFolderIdFromNode(row);
            modalClickDebug('Chevron click', {
                element: this,
                target: e.target,
                currentTarget: e.currentTarget,
                row: row[0] || null,
                resolvedFolderId: termId
            });
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            if (!(termId > 0) || !row.length) {
                return;
            }

            var collapsed = !row.hasClass('is-collapsed');
            row.toggleClass('is-collapsed', collapsed);
            row.attr('data-collapsed', collapsed ? '1' : '0');
            $(this).attr('aria-expanded', collapsed ? 'false' : 'true')
                .toggleClass('is-collapsed', collapsed);

            folderCollapsedMap[termId] = collapsed;
            if (!collapsed) {
                delete folderCollapsedMap[termId];
            }
            saveCollapsedState();

            setModalNodeVisibilityByParent(termId, collapsed);
            if (row && row.length) {
                row.toggleClass('is-collapsed', collapsed);
            }
            modalClickDebug('Chevron toggled via state', {
                termId: termId,
                collapsed: collapsed
            });
        });
    }

    function patchSingleMediaFrame(Frame, frameLabel) {
        if (!Frame || !Frame.prototype) {
            return false;
        }

        if (Frame.prototype.__bwMfModalPatched) {
            mediaFoldersDebugLog('patchSingleMediaFrame skipped: prototype already patched', {
                frameType: frameLabel || ''
            });
            return true;
        }

        var originalInitialize = Frame.prototype.initialize;
        if (typeof originalInitialize !== 'function') {
            mediaFoldersDebugLog('patchSingleMediaFrame skipped: initialize is not a function', {
                frameType: frameLabel || ''
            });
            return false;
        }

        if (frameLabel === 'Post') {
            classicModalDebugLog('applying Post frame patch');
            window.BW_MF_PATCH_STATUS.postFound = true;
        }

        Frame.prototype.initialize = function () {
            originalInitialize.apply(this, arguments);
            this.__bwMfFrameLabel = frameLabel || '';
            mediaFoldersDebugLog('MediaFrame.initialize called', {
                frameType: frameLabel || '',
                frame: mediaFoldersDebugState(this)
            });
            if (frameLabel === 'Post') {
                window.BW_MF_PATCH_STATUS.postInitializeRan = true;
                classicModalDebugLog('Post frame initialize', {
                    frame: getMediaFrameDebugMeta(this),
                    prototypeChain: getPrototypeChainNames(this)
                });
            }

            if (this.__bwMfModalInitBound) {
                mediaFoldersDebugLog('MediaFrame.initialize skipped duplicate binding', {
                    frameType: frameLabel || ''
                });
                return;
            }

            this.__bwMfModalInitBound = true;

            this.on('open', function () {
                mediaFoldersDebugLog('patched frame event: open', {
                    frameType: frameLabel || '',
                    frame: mediaFoldersDebugState(this)
                });
                if (frameLabel === 'Post') {
                    classicModalDebugLog('Post frame event: open', getMediaFrameDebugMeta(this));
                }
                scheduleModalSidebarMount(this, 'open');
            });

            this.on('content:render:browse', function () {
                mediaFoldersDebugLog('patched frame event: content:render:browse', {
                    frameType: frameLabel || '',
                    frame: mediaFoldersDebugState(this)
                });
                if (frameLabel === 'Post') {
                    classicModalDebugLog('Post frame event: content:render:browse', getMediaFrameDebugMeta(this));
                }
                scheduleModalSidebarMount(this, 'content:render:browse');
            });

            this.on('close', function () {
                mediaFoldersDebugLog('patched frame event: close', {
                    frameType: frameLabel || '',
                    frame: mediaFoldersDebugState(this)
                });
                if (frameLabel === 'Post') {
                    classicModalDebugLog('Post frame event: close', getMediaFrameDebugMeta(this));
                }
                clearModalSidebar(this);
            });
        };

        Frame.prototype.__bwMfModalPatched = true;
        if (frameLabel === 'Post') {
            window.BW_MF_PATCH_STATUS.postPatched = true;
            classicModalDebugLog('Post frame patched');
        }
        return true;
    }

    function bindExistingMediaFrameInstance(source) {
        if (!window.wp || !wp.media || !wp.media.frame) {
            return false;
        }

        var frame = wp.media.frame;
        lastKnownMediaFrame = frame || lastKnownMediaFrame;
        if (!frame || typeof frame.on !== 'function') {
            return false;
        }

        if (frame.__bwMfModalInitBound) {
            if (getMediaFrameLabel(frame) === 'Post') {
                classicModalDebugLog('existing Post frame already bound', {
                    source: source || '',
                    frame: getMediaFrameDebugMeta(frame),
                    prototypeChain: getPrototypeChainNames(frame)
                });
            }
            return true;
        }

        var frameLabel = getMediaFrameLabel(frame);
        frame.__bwMfFrameLabel = frameLabel || '';

        if (frameLabel === 'Post') {
            classicModalDebugLog('binding existing Post frame instance', {
                source: source || '',
                frame: getMediaFrameDebugMeta(frame),
                prototypeChain: getPrototypeChainNames(frame)
            });
        }

        frame.__bwMfModalInitBound = true;

        frame.on('open', function () {
            mediaFoldersDebugLog('patched existing frame event: open', {
                frameType: this.__bwMfFrameLabel || '',
                frame: mediaFoldersDebugState(this)
            });
            if (this.__bwMfFrameLabel === 'Post') {
                classicModalDebugLog('existing Post frame event: open', getMediaFrameDebugMeta(this));
            }
            scheduleModalSidebarMount(this, 'open');
        });

        frame.on('content:render:browse', function () {
            mediaFoldersDebugLog('patched existing frame event: content:render:browse', {
                frameType: this.__bwMfFrameLabel || '',
                frame: mediaFoldersDebugState(this)
            });
            if (this.__bwMfFrameLabel === 'Post') {
                classicModalDebugLog('existing Post frame event: content:render:browse', getMediaFrameDebugMeta(this));
            }
            scheduleModalSidebarMount(this, 'content:render:browse');
        });

        frame.on('close', function () {
            mediaFoldersDebugLog('patched existing frame event: close', {
                frameType: this.__bwMfFrameLabel || '',
                frame: mediaFoldersDebugState(this)
            });
            if (this.__bwMfFrameLabel === 'Post') {
                classicModalDebugLog('existing Post frame event: close', getMediaFrameDebugMeta(this));
            }
            clearModalSidebar(this);
        });

        return true;
    }

    function patchMediaFrameSelect() {
        if (window.console && typeof console.log === 'function') {
            console.log('[BW MF BOOT] patch pass start', {
                run: mediaFramePatchRunCount + 1,
                hasWpMedia: !!(window.wp && wp.media),
                hasMediaFramePost: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Post),
                hasMediaFrameSelect: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select)
            });
        }
        mediaFoldersDebugLog('patchMediaFrameSelect invoked', {
            run: ++mediaFramePatchRunCount,
            state: {
                hasWpMedia: !!(window.wp && wp.media),
                hasMediaFrameSelect: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select),
                hasMediaFramePost: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Post),
                alreadyPatched: !!mediaFrameModalPatched
            }
        });
        updateMediaFoldersDiag({
            lastPatchAttempt: mediaFramePatchRunCount,
            patchApplied: !!mediaFrameModalPatched
        });

        if (!window.wp || !wp.media || !wp.media.view || !wp.media.view.MediaFrame) {
            mediaFoldersDebugLog('patchMediaFrameSelect early exit: wp.media.view.MediaFrame unavailable');
            return false;
        }

        var frameTypes = [
            { key: 'Select', ref: wp.media.view.MediaFrame.Select },
            { key: 'Post', ref: wp.media.view.MediaFrame.Post },
            { key: 'FeaturedImage', ref: wp.media.view.MediaFrame.FeaturedImage }
        ];
        if (window.console && typeof console.log === 'function') {
            console.log('[BW MF BOOT] frame classes available', {
                select: !!wp.media.view.MediaFrame.Select,
                post: !!wp.media.view.MediaFrame.Post,
                featuredImage: !!wp.media.view.MediaFrame.FeaturedImage
            });
        }
        var patchedAny = false;

        frameTypes.forEach(function (frameType) {
            if (patchSingleMediaFrame(frameType.ref, frameType.key)) {
                patchedAny = true;
            }
        });

        if (!patchedAny) {
            mediaFoldersDebugLog('patchMediaFrameSelect early exit: no supported media frames available');
            return false;
        }

        bindExistingMediaFrameInstance('patch-pass');
        mediaFrameModalPatched = true;
        mediaFramePatchAttempts = 0;
        mediaFramePatchDeadline = 0;
        mediaFramePatchDelay = 250;
        if (mediaFramePatchTimer) {
            window.clearTimeout(mediaFramePatchTimer);
            mediaFramePatchTimer = null;
        }
        mediaFoldersDebugLog('patchMediaFrameSelect success: media frame initialize patched');
        updateMediaFoldersDiag({
            patchApplied: true
        });
        return true;
    }

    function scheduleMediaFramePatchRetry(source) {
        if (
            mediaFrameModalPatched &&
            window.wp &&
            wp.media &&
            wp.media.view &&
            wp.media.view.MediaFrame &&
            wp.media.view.MediaFrame.Post
        ) {
            mediaFoldersDebugLog('scheduleMediaFramePatchRetry skipped: already patched');
            return;
        }

        var now = Date.now();
        if (!mediaFramePatchDeadline) {
            mediaFramePatchDeadline = now + 30000;
        }

        if (now >= mediaFramePatchDeadline) {
            mediaFoldersDebugLog('scheduleMediaFramePatchRetry stopped: deadline reached', {
                source: source || '',
                attempts: mediaFramePatchAttempts
            });
            return;
        }

        if (mediaFramePatchTimer) {
            mediaFoldersDebugLog('scheduleMediaFramePatchRetry skipped: timer already pending', {
                source: source || '',
                attempts: mediaFramePatchAttempts
            });
            return;
        }

        mediaFramePatchTimer = window.setTimeout(function () {
            mediaFramePatchTimer = null;
            mediaFoldersDebugLog('scheduleMediaFramePatchRetry timer fired', {
                source: source || '',
                attempts: mediaFramePatchAttempts
            });
            ensureMediaFramePatch(source || 'timer');
        }, mediaFramePatchDelay);

        mediaFramePatchAttempts += 1;
        mediaFramePatchDelay = Math.min(Math.max(mediaFramePatchDelay * 1.5, 250), 2000);
        mediaFramePatchRetryCount += 1;
        mediaFoldersDebugLog('scheduleMediaFramePatchRetry scheduled', {
            source: source || '',
            retryCount: mediaFramePatchRetryCount,
            delayMs: mediaFramePatchDelay,
            attempts: mediaFramePatchAttempts
        });
    }

    function ensureMediaFramePatch(source) {
        mediaFoldersDebugLog('ensureMediaFramePatch invoked', {
            source: source || '',
            state: {
                hasWpMedia: !!(window.wp && wp.media),
                hasMediaFrameSelect: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select),
                patched: !!mediaFrameModalPatched
            }
        });

        var patched = patchMediaFrameSelect();
        bindExistingMediaFrameInstance(source || 'ensure');

        if (patched && window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Post) {
            return;
        }

        if (mediaFramePatchAttempts >= 60) {
            mediaFoldersDebugLog('ensureMediaFramePatch stopped: max attempts reached', {
                source: source || '',
                attempts: mediaFramePatchAttempts
            });
            return;
        }

        scheduleMediaFramePatchRetry(source || 'ensure');
    }

    function bindMediaFramePatchSignals() {
        if (mediaFramePatchSignalsBound) {
            mediaFoldersDebugLog('bindMediaFramePatchSignals skipped: already bound');
            return;
        }

        mediaFramePatchSignalsBound = true;
        mediaFoldersDebugLog('bindMediaFramePatchSignals start');

        var retryEvents = [
            'elementor:init',
            'elementor/editor/init',
            'elementor/frontend/init',
            'elementor/components/init'
        ];

        retryEvents.forEach(function (eventName) {
            if (window.jQuery && typeof jQuery !== 'undefined') {
                jQuery(window).on(eventName + '.bwMfMediaPatch', function () {
                    mediaFramePatchSignalCount += 1;
                    mediaFoldersDebugLog('Elementor hook fired: ' + eventName, {
                        signalCount: mediaFramePatchSignalCount
                    });
                    ensureMediaFramePatch(eventName);
                });
                mediaFoldersDebugLog('Elementor hook bound: ' + eventName);
            }
        });

        if (window.jQuery && typeof jQuery !== 'undefined') {
            jQuery(document).on(
                'mousedown.bwMfMediaPatch click.bwMfMediaPatch',
                '.insert-media, .add_media, #set-post-thumbnail, .set-post-thumbnail, .upload_image_button, .add_product_images, [href*="media-upload.php"]',
                function () {
                    mediaFramePatchSignalCount += 1;
                    classicModalDebugLog('classic media opener click', {
                        signalCount: mediaFramePatchSignalCount
                    });
                    mediaFoldersDebugLog('classic media opener signal fired', {
                        signalCount: mediaFramePatchSignalCount
                    });
                    ensureMediaFramePatch('classic-media-opener');
                    window.setTimeout(function () {
                        bindExistingMediaFrameInstance('classic-media-opener');
                    }, 0);
                    startClassicModalBindLoop('classic-media-opener');
                }
            );
            mediaFoldersDebugLog('classic media opener signals bound');
        }

        bindClassicModalObserver();

        if (window.elementorFrontend && elementorFrontend.hooks && typeof elementorFrontend.hooks.addAction === 'function') {
            try {
                elementorFrontend.hooks.addAction('frontend/element_ready/global', function () {
                    mediaFramePatchSignalCount += 1;
                    mediaFoldersDebugLog('Elementor hook fired: frontend/element_ready/global', {
                        signalCount: mediaFramePatchSignalCount
                    });
                    ensureMediaFramePatch('frontend/element_ready/global');
                });
                mediaFoldersDebugLog('Elementor hook bound: frontend/element_ready/global');
            } catch (e) {
                // ignore Elementor hook registration errors
            }
        }

        if (document.readyState === 'complete') {
            mediaFoldersDebugLog('document readyState complete; scheduling immediate patch check');
            window.setTimeout(function () {
                ensureMediaFramePatch('document-ready-complete');
                bindExistingMediaFrameInstance('document-ready-complete');
                startClassicModalBindLoop('document-ready-complete');
            }, 0);
        } else {
            window.addEventListener('load', function () {
                mediaFoldersDebugLog('window load fired');
                ensureMediaFramePatch('window-load');
                bindExistingMediaFrameInstance('window-load');
                startClassicModalBindLoop('window-load');
            }, { once: true });
        }
    }

    function findFolder(id) {
        return state.folders.find(function (item) {
            return item.id === id;
        });
    }

    function applySearchFilter() {
        syncTreeNodeVisibility();
    }

    function collectSelectedObjectIds() {
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
                    if (browser && browser.collection && browser.collection.props && typeof browser.collection.props.set === 'function') {
                        browser.collection.props.set({
                            bw_media_folder: state.activeFolder > 0 ? state.activeFolder : undefined,
                            bw_media_unassigned: state.activeUnassigned ? '1' : undefined
                        });
                        browser.collection.more();
                        scheduleBwMfRefresh('refresh-media-view');
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
        }, function (data) {
            var assignedIds = Array.isArray(data && data.assigned_ids) ? data.assigned_ids : [];
            var duplicateIds = Array.isArray(data && data.duplicate_ids) ? data.duplicate_ids : [];
            var changedIds = assignedIds.length ? assignedIds : ids;

            if (Array.isArray(changedIds)) {
                changedIds.forEach(function (id) {
                    var parsed = parseInt(id, 10);
                    if (parsed > 0) {
                        markerCache.delete(parsed);
                        markerFailedIds.delete(parsed);
                    }
                });
            }

            if (assignedIds.length) {
                refreshTree();
                if (typeof onDone === 'function') {
                    onDone();
                }
                scheduleBwMfRefresh('assign-folder');
            }

            if (duplicateIds.length && data && data.message) {
                showDuplicateNotice(data.message);
            }
        });
    }

    function makeGridTilesDraggable() {
        $('.attachments-browser .attachment').attr('draggable', 'true');
        $('.attachments-browser .attachment img').attr('draggable', 'false');
    }

    function makeListRowsDraggable() {
        if (isMediaPostType()) {
            $('.wp-list-table tbody tr').attr('draggable', 'true');
            return;
        }

        $('.wp-list-table tbody tr').attr('draggable', 'false');
        $('.bw-mf-row-drag-handle').attr('draggable', 'true');
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
        if (!isMediaPostType()) {
            var singleId = parseInt($el.attr('data-post-id') || '0', 10);
            return singleId > 0 ? [singleId] : [];
        }

        var id = parseInt($el.attr('data-id') || (($el.attr('id') || '').replace('post-', '')) || '0', 10);
        if (!(id > 0)) {
            return [];
        }

        var selected = collectSelectedObjectIds();
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
                if (!isMediaPostType()) {
                    return;
                }
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
            .off('dragstart.bwMfDnDHandle', '.bw-mf-row-drag-handle')
            .on('dragstart.bwMfDnDHandle', '.bw-mf-row-drag-handle', function (e) {
                if (isMediaPostType()) {
                    return;
                }

                var ids = collectDragIdsForElement($(this));
                if (!ids.length) {
                    draggedIds = [];
                    setInternalDrag(false);
                    return;
                }

                var dragTitle = String($(this).attr('data-drag-title') || '').trim();
                if (!dragTitle) {
                    dragTitle = 'Item';
                }
                var dragLabel = '↕ ' + dragTitle;

                draggedIds = [ids[0]];
                setInternalDrag(true);
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    try {
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                    } catch (err) {}
                    e.originalEvent.dataTransfer.setData('text/plain', String(ids[0]));
                }
                setupDragBadge(e, 1, dragLabel);
                debugLog('handle dragstart', { ids: draggedIds, title: dragTitle });
            });

        $(document)
            .off('dragend.bwMfDnDCleanup', '.attachments-browser .attachment, .wp-list-table tbody tr, .bw-mf-row-drag-handle')
            .on('dragend.bwMfDnDCleanup', '.attachments-browser .attachment, .wp-list-table tbody tr, .bw-mf-row-drag-handle', function () {
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
        if (!isMediaPostType()) {
            return;
        }

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

            var activeState = getActiveFilterState();

            if (activeState.activeUnassigned) {
                if (options.data.indexOf('query%5Bbw_media_unassigned%5D=1') === -1) {
                    options.data += '&query%5Bbw_media_unassigned%5D=1';
                }
            } else if (activeState.activeFolder > 0) {
                var encodedFolder = 'query%5Bbw_media_folder%5D=' + encodeURIComponent(String(activeState.activeFolder));
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
            if ($(e.target).closest('.bw-mf-folder-pencil').length) {
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

        root().on('keydown', '.bw-media-folder-node__main', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            e.preventDefault();
            $(this).trigger('click');
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

        $(document).on('click', '.bw-mf-copy-link-btn', function (e) {
            var button = e.currentTarget;
            var url = button ? String(button.getAttribute('data-copy-url') || '').trim() : '';
            var copiedLabel = cfg.text && cfg.text.copiedLink ? cfg.text.copiedLink : 'Copied';
            var copyLabel = cfg.text && cfg.text.copyLink ? cfg.text.copyLink : 'Copy link';
            var copyFailedLabel = cfg.text && cfg.text.copyFailed ? cfg.text.copyFailed : 'Copy failed';

            e.preventDefault();
            e.stopPropagation();

            if (!url) {
                window.alert(copyFailedLabel);
                return;
            }

            copyTextToClipboard(url)
                .then(function () {
                    var $button = $(button);
                    window.clearTimeout($button.data('bwMfCopyResetTimer') || 0);
                    $button.addClass('is-copied')
                        .attr('aria-label', copiedLabel)
                        .attr('title', copiedLabel);

                    var resetTimer = window.setTimeout(function () {
                        $button.removeClass('is-copied')
                            .attr('aria-label', copyLabel)
                            .attr('title', copyLabel);
                        $button.removeData('bwMfCopyResetTimer');
                    }, 1400);

                    $button.data('bwMfCopyResetTimer', resetTimer);
                })
                .catch(function () {
                    window.alert(copyFailedLabel);
                });
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

            if (cmd === 'new-subfolder') {
                if (!folder || termId <= 0) {
                    hideContextMenu();
                    return;
                }

                var promptText = cfg.text && cfg.text.createSubPrompt ? cfg.text.createSubPrompt : 'Subfolder name';
                var subName = window.prompt(promptText);
                if (!subName) {
                    hideContextMenu();
                    return;
                }

                request('bw_media_create_folder', {
                    name: subName,
                    parent: termId
                }, refreshTree);

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
            var ids = collectSelectedObjectIds();
            if (!ids.length) {
                window.alert(cfg.text && cfg.text.selectItems ? cfg.text.selectItems : 'Select at least one item.');
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

        // Woo product list has a fixed top header that can overlap the sidebar.
        if (body.hasClass('post-type-product')) {
            var topOffset = 32;
            var wooHeader = document.querySelector('.woocommerce-layout__header, .woocommerce-layout-header');
            if (wooHeader) {
                var rect = wooHeader.getBoundingClientRect();
                if (rect && rect.bottom > 32) {
                    topOffset = Math.ceil(rect.bottom);
                }
            }
            document.body.style.setProperty('--bw-mf-top-offset', topOffset + 'px');
        } else {
            document.body.style.setProperty('--bw-mf-top-offset', '32px');
        }
    }

    function init() {
        if (window.__BW_MF_INIT_DONE) {
            return;
        }
        window.__BW_MF_INIT_DONE = true;

        mediaFoldersDebugLog('script loaded', {
            url: window.location ? window.location.href : '',
            hasWpMedia: !!(window.wp && wp.media),
            hasMediaFrameSelect: !!(window.wp && wp.media && wp.media.view && wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select),
            patchApplied: !!mediaFrameModalPatched
        });
        updateMediaFoldersDiag({
            lastPatchAttempt: 0,
            lastMountAttempt: 0,
            lastMountReason: 'init',
            lastMountFrameType: '',
            lastModalExists: !!document.querySelector('.media-modal'),
            lastContentExists: false,
            lastBrowserExists: false,
            lastAttachmentsBrowserExists: false,
            lastExistingSidebarCount: document.querySelectorAll(modalSelectors.root).length,
            lastSidebarMarkupLength: cfg.sidebarHtml ? String(cfg.sidebarHtml).length : 0,
            lastInsertionTarget: '',
            lastMissingSelector: '',
            lastInjectionError: '',
            sidebarInjected: !!getModalSidebarRoot().length
        });

        bindMediaFramePatchSignals();
        ensureMediaFramePatch('init');
        loadCollapsedState();

        if (isSupportedListScreen()) {
            mountLayout();
            ensureDuplicateNotice();
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
            bindBadgeTooltipEvents();
            if (isMediaPostType()) {
                bindQuickTypeFilterEvents();
                bindQuickTypeLayoutObserver();
                registerGridAjaxFilter();
            }
            bindCornerMarkerObserver();
            if (isMediaPostType()) {
                bindQuickTypeFilterObserver();
                ensureTypeFiltersPlacement();
            }
        }

        if (isSupportedListScreen() || (window.wp && wp.media)) {
            refreshTree();
        }
        if (isModalSidebarActive()) {
            renderModalSidebar(modalState.frame, 'init');
        }
        scheduleBwMfRefresh('init');
    }

    $(init);

    document.addEventListener('DOMContentLoaded', function () {
        if (!isMediaPostType() || !cornerIndicatorEnabled) {
            clearCornerMarkers();
        } else {
            window.setTimeout(function () {
                scheduleBwMfRefresh('dom-ready-corner');
            }, 500);
        }
        if (isMediaPostType()) {
            window.setTimeout(function () {
                bindQuickTypeFilterObserver();
                bindQuickTypeLayoutObserver();
                scheduleBwMfRefresh('dom-ready-types');
            }, 350);
        }
    });
})(jQuery);
