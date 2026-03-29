/**
 * Virtual Museum — Admin JS
 */
(function ($) {
    'use strict';

    const cfg = typeof vmAdmin !== 'undefined' ? vmAdmin : { ajaxUrl: '', nonce: '', i18n: {} };

    // ====================================================
    // AJAX helper
    // ====================================================
    function vmAjax(action, data, cb) {
        $.post(cfg.ajaxUrl, Object.assign({ action: action, nonce: cfg.nonce }, data), cb);
    }

    // ====================================================
    // Remove child relation (via 🗑️ button)
    // ====================================================
    $(document).on('click', '.vm-remove-child-relation', function () {
        if (!confirm(cfg.i18n.confirmRemove || 'Entfernen?')) return;
        const $btn = $(this);
        const $item = $btn.closest('.vm-rel-item');
        const relationId = $item.data('relation-id');

        vmAjax('vm_remove_relation', { relation_id: relationId }, function (res) {
            if (res.success) {
                $item.fadeOut(200, function () { $(this).remove(); });
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
            }
        });
    });

    // ====================================================
    // Remove parent relation (from object screen)
    // ====================================================
    $(document).on('click', '.vm-remove-relation', function () {
        if (!confirm(cfg.i18n.confirmRemove || 'Entfernen?')) return;
        const $btn = $(this);
        const data = {
            parent_type: $btn.data('parent-type'),
            parent_id:   $btn.data('parent-id'),
            child_type:  $btn.data('child-type'),
            child_id:    $btn.data('child-id'),
        };
        vmAjax('vm_remove_relation', data, function (res) {
            if (res.success) {
                $btn.closest('li').fadeOut(200, function () { $(this).remove(); });
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
            }
        });
    });

    // ====================================================
    // Add relation button → opens search modal
    // ====================================================
    $(document).on('click', '.vm-add-relation, .vm-link-to-parent', function () {
        const $btn       = $(this);
        const $editor    = $btn.closest('.vm-relation-editor');
        const childType  = $btn.data('child-type') || '';
        const parentType = $btn.data('parent-type') || $editor.data('parent-type') || '';
        const parentId   = $editor.data('parent-id') || $editor.data('child-id') || 0;
        const isParentLink = $btn.hasClass('vm-link-to-parent');

        openSearchModal({
            childType:   isParentLink ? $editor.data('child-type') : childType,
            parentType:  isParentLink ? childType : parentType,
            parentId:    isParentLink ? 0 : parentId,
            isParentLink: isParentLink,
            $btn:        $btn,
            $editor:     $editor,
        });
    });

    // ====================================================
    // Search Modal
    // ====================================================
    function openSearchModal(ctx) {
        const searchType = ctx.isParentLink ? ctx.parentType : ctx.childType;

        const $modal = $('<div class="vm-search-modal">' +
            '<div class="vm-search-modal__inner">' +
            '<div class="vm-search-modal__header">' +
            '<input type="text" placeholder="' + (cfg.i18n.searchPlaceholder || 'Suche...') + '" autofocus>' +
            '<button class="vm-search-modal__close" type="button">&#10005;</button>' +
            '</div>' +
            '<div class="vm-search-modal__results"></div>' +
            '</div></div>');

        $('body').append($modal);
        $modal.find('input').focus();

        function close() { $modal.remove(); }

        $modal.on('click', function (e) { if ($(e.target).is('.vm-search-modal')) close(); });
        $modal.find('.vm-search-modal__close').on('click', close);

        $modal.find('input').on('input', debounce(function () {
            const q = $(this).val().trim();
            if (q.length < 1) { $modal.find('.vm-search-modal__results').html(''); return; }
            const $res = $modal.find('.vm-search-modal__results');
            $res.html('<p class="vm-search-empty">' + (cfg.i18n.saving || 'Suche...') + '</p>');

            vmAjax('vm_search_linkable', {
                search:      q,
                child_type:  ctx.isParentLink ? ctx.childType : ctx.childType,
                parent_type: ctx.isParentLink ? ctx.childType : ctx.parentType,
                parent_id:   ctx.parentId,
            }, function (data) {
                if (!data.success || !data.data.length) {
                    $res.html('<p class="vm-search-empty">' + (cfg.i18n.noResults || 'Keine Ergebnisse') + '</p>');
                    return;
                }
                const html = data.data.map(function (item) {
                    const thumb = item.thumb ? '<img src="' + item.thumb + '" alt="">' : '<span style="width:40px;display:inline-block"></span>';
                    const badge = item.already_linked ? '<span class="vm-res-badge">' + (cfg.i18n.alreadyAdded || 'Vorhanden') + '</span>' : '';
                    return '<div class="vm-search-result-item' + (item.already_linked ? ' vm-res--added' : '') + '" data-id="' + item.id + '" data-title="' + $('<div>').text(item.title).html() + '">' +
                        thumb +
                        '<div class="vm-res-info"><strong>' + $('<div>').text(item.title).html() + '</strong>' +
                        (item.usage_total ? '<small>In ' + item.usage_total + ' Verknüpfungen</small>' : '') +
                        '</div>' + badge + '</div>';
                }).join('');
                $res.html(html);
            });
        }, 250));

        $modal.on('click', '.vm-search-result-item:not(.vm-res--added)', function () {
            const $item  = $(this);
            const postId = $item.data('id');
            const title  = $item.data('title');
            const $list  = ctx.$editor.find('.vm-sortable-list[data-child-type="' + ctx.childType + '"]');

            vmAjax('vm_add_relation', {
                parent_type: ctx.isParentLink ? postId : ctx.parentType,
                parent_id:   ctx.isParentLink ? 0      : ctx.parentId,
                child_type:  ctx.isParentLink ? ctx.childType : ctx.childType,
                child_id:    ctx.isParentLink ? ctx.$editor.data('child-id') : postId,
                parent_id:   ctx.isParentLink ? postId : ctx.parentId,
            }, function (res) {
                if (res.success) {
                    const d    = res.data;
                    const icon = { object: '🎨', gallery: '🖼️', vitrine: '🗄️', room: '🚪' }[ctx.childType] || '📌';
                    const $new = $('<li class="vm-rel-item" data-relation-id="' + d.relation_id + '" data-post-id="' + d.post_id + '">' +
                        '<span class="dashicons dashicons-menu vm-drag-handle"></span>' +
                        '<span class="vm-rel-icon">' + icon + '</span>' +
                        '<a href="' + d.edit_url + '" class="vm-rel-title">' + $('<div>').text(d.title).html() + '</a>' +
                        '<span class="vm-rel-actions"><button type="button" class="button-link vm-remove-child-relation" data-relation-id="' + d.relation_id + '">🗑️</button></span>' +
                        '</li>');
                    if ($list.length) {
                        $list.append($new);
                    }
                    $item.addClass('vm-res--added');
                } else {
                    alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
                }
            });
        });
    }

    // ====================================================
    // Debounce helper
    // ====================================================
    function debounce(fn, delay) {
        let t;
        return function () {
            const ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

}(jQuery));
