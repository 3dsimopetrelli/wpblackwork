(function ($) {
    'use strict';

    var cfg = window.bwMediaFolders || {};
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
        $('#bw-media-folders-tree li').removeClass('bw-mf-folder-drop-hover');
    }

    function dropRowForElement($el) {
        if (!$el || !$el.length) {
            return { node: $(), row: $() };
        }

        var node = $el.closest('[data-term-id]');
        if (!node.length) {
            node = $el.closest('.bw-media-folder-node, .bw-media-default--drop');
        }
        var row = node.closest('li');
        if (!row.length) {
            row = node;
        }

        return { node: node, row: row };
    }

    function setDropHover($el) {
        var target = dropRowForElement($el);
        target.node.addClass('is-drag-over bw-mf-folder-drop-hover');
        target.row.addClass('bw-mf-folder-drop-hover');
    }

    function clearDropHoverFor($el) {
        var target = dropRowForElement($el);
        target.node.removeClass('is-drag-over bw-mf-folder-drop-hover');
        target.row.removeClass('bw-mf-folder-drop-hover');
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

    function request(action, payload, onDone) {
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
                    window.alert(msg);
                    return;
                }

                if (typeof onDone === 'function') {
                    onDone(res.data || {});
                }
            })
            .fail(function () {
                window.alert('Request failed');
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

            return true;
        } catch (e) {
            return false;
        }
    }

    function nodeHtml(item, depth) {
        var pad = Math.max(0, depth) * 14;
        var pinnedClass = item.pinned ? ' is-pinned' : '';
        var active = (!state.activeUnassigned && state.activeFolder === item.id) ? ' is-active' : '';
        var color = item.color ? item.color : '#6b7280';

        return '' +
            '<div class="bw-media-folder-node' + pinnedClass + active + '" data-id="' + item.id + '" data-term-id="' + item.id + '" data-folder-id="' + item.id + '" data-parent="' + item.parent + '" style="padding-left:' + pad + 'px">' +
            '  <button class="bw-media-folder-node__main" type="button">' +
            '    <span class="bw-media-folder-node__dot" style="background:' + color + '"></span>' +
            '    <span class="bw-media-folder-node__name">' + item.name + '</span>' +
            '    <span class="bw-media-folder-node__count">' + item.count + '</span>' +
            '  </button>' +
            '  <div class="bw-media-folder-node__actions">' +
            '    <button type="button" class="bw-mf-action" data-action="sub">+</button>' +
            '    <button type="button" class="bw-mf-action" data-action="rename">R</button>' +
            '    <button type="button" class="bw-mf-action" data-action="pin">' + (item.pinned ? 'U' : 'P') + '</button>' +
            '    <button type="button" class="bw-mf-action" data-action="color">C</button>' +
            '    <button type="button" class="bw-mf-action bw-mf-action--danger" data-action="delete">X</button>' +
            '  </div>' +
            '</div>';
    }

    function renderDefaults() {
        var html = '';
        var allClass = (!state.activeFolder && !state.activeUnassigned) ? ' is-active' : '';
        var unClass = state.activeUnassigned ? ' is-active' : '';

        html += '<button type="button" class="bw-media-default' + allClass + '" data-type="all">All Files <span>' + (state.counts.all || 0) + '</span></button>';
        html += '<button type="button" class="bw-media-default bw-media-default--drop' + unClass + '" data-type="unassigned" data-term-id="0" data-folder-id="0">Unassigned Files <span>' + (state.counts.unassigned || 0) + '</span></button>';

        $('#bw-media-folders-defaults').html(html);
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

    function renderTree() {
        $('#bw-media-folders-tree').html(buildTreeRows().join(''));

        var options = ['<option value="0">Unassigned</option>'];
        state.folders.forEach(function (item) {
            options.push('<option value="' + item.id + '">' + item.name + '</option>');
        });
        $('#bw-media-folders-bulk-select').html(options.join(''));

        applySearchFilter();
        bindDropTargets();
    }

    function refreshTree() {
        request('bw_media_get_folders_tree', {}, function (data) {
            state.folders = Array.isArray(data.folders) ? data.folders : [];
            state.counts = data.counts || { all: 0, unassigned: 0 };
            renderDefaults();
            renderTree();
        });
    }

    function findFolder(id) {
        return state.folders.find(function (item) {
            return item.id === id;
        });
    }

    function applySearchFilter() {
        var term = ($('#bw-mr-folder-search').val() || '').toLowerCase().trim();
        $('#bw-media-folders-tree .bw-media-folder-node').each(function () {
            var text = ($(this).find('.bw-media-folder-node__name').text() || '').toLowerCase();
            var show = !term || text.indexOf(term) !== -1;
            $(this).toggle(show);
        });
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
            refreshTree();
            if (typeof onDone === 'function') {
                onDone();
            }
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

            if (event.type === 'drop') {
                setInternalDrag(false);
            }

            event.preventDefault();
            if (event.type === 'dragenter' || event.type === 'dragover') {
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
                setDropHover($(e.target));
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    try {
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                    } catch (err) {}
                }
            })
            .on('dragover.bwMfDnD', function (e) {
                e.preventDefault();
                setDropHover($(e.target));
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    try {
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                    } catch (err) {}
                }
            }).on('dragleave.bwMfDnD', function (e) {
                clearDropHoverFor($(e.target));
            }).on('drop.bwMfDnD', function (e) {
                e.preventDefault();
                clearDropHoverFor($(e.target));
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

        root().on('click', '.bw-media-folder-node__main', function () {
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
                var newName = window.prompt(cfg.text && cfg.text.renamePrompt ? cfg.text.renamePrompt : 'Rename folder', folder.name);
                if (newName) {
                    request('bw_media_rename_folder', { term_id: termId, name: newName }, refreshTree);
                }
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

            if (action === 'sub') {
                var subName = window.prompt(cfg.text && cfg.text.createSubPrompt ? cfg.text.createSubPrompt : 'Subfolder name');
                if (subName) {
                    request('bw_media_create_folder', { name: subName, parent: termId }, refreshTree);
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
        mountLayout();
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
    }

    $(init);
})(jQuery);
