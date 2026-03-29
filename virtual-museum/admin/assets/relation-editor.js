/**
 * Virtual Museum — Drag & Drop Relation Editor
 * Uses native HTML5 Drag & Drop API
 */
(function () {
    'use strict';

    const cfg = typeof vmAdmin !== 'undefined' ? vmAdmin : { ajaxUrl: '', nonce: '' };

    function initSortable(list) {
        if (!list) return;

        let dragSrc = null;

        list.querySelectorAll('.vm-rel-item').forEach(function (item) {
            item.draggable = true;

            item.addEventListener('dragstart', function (e) {
                dragSrc = this;
                this.classList.add('vm-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', function () {
                this.classList.remove('vm-dragging');
                list.querySelectorAll('.vm-rel-item').forEach(function (i) { i.classList.remove('vm-drag-over'); });
                saveOrder(list);
            });

            item.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (this !== dragSrc) {
                    this.classList.add('vm-drag-over');
                }
                return false;
            });

            item.addEventListener('dragleave', function () {
                this.classList.remove('vm-drag-over');
            });

            item.addEventListener('drop', function (e) {
                e.stopPropagation();
                if (dragSrc !== this) {
                    // Determine position
                    const items = Array.from(list.querySelectorAll('.vm-rel-item'));
                    const srcIdx  = items.indexOf(dragSrc);
                    const destIdx = items.indexOf(this);
                    if (srcIdx < destIdx) {
                        list.insertBefore(dragSrc, this.nextSibling);
                    } else {
                        list.insertBefore(dragSrc, this);
                    }
                }
                this.classList.remove('vm-drag-over');
                return false;
            });
        });
    }

    function saveOrder(list) {
        const $editor   = list.closest('.vm-relation-editor');
        const parentType = $editor ? $editor.dataset.parentType : '';
        const parentId   = $editor ? $editor.dataset.parentId : 0;
        if (!parentType || !parentId) return;

        const orderedIds = Array.from(list.querySelectorAll('.vm-rel-item'))
            .map(function (item) { return item.dataset.relationId; })
            .filter(Boolean);

        fetch(cfg.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action:               'vm_reorder_relations',
                nonce:                cfg.nonce,
                parent_type:          parentType,
                parent_id:            parentId,
                'ordered_relation_ids[]': '',
            }).toString() + orderedIds.map(function (id, i) {
                return '&ordered_relation_ids[' + i + ']=' + encodeURIComponent(id);
            }).join(''),
        }).catch(function () {});
    }

    // Init all sortable lists on the page
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.vm-sortable-list').forEach(initSortable);
    });

    // Re-init after new items are added (called from admin.js via custom event)
    document.addEventListener('vm:relation-added', function (e) {
        if (e.detail && e.detail.list) {
            initSortable(e.detail.list);
        }
    });

})();
