/**
 * Virtual Museum — Lazy Loading
 * - Infinite scroll / Load More for archive grids
 * - Deferred AJAX loading for room objects section (loads on viewport entry)
 */
(function () {
    'use strict';

    const cfg  = typeof vmPublic !== 'undefined' ? vmPublic : { ajaxUrl: '', lazy: {} };
    const lazy = cfg.lazy || {};

    // ====================================================
    // Infinite scroll for archive grid
    // ====================================================
    const archiveGrid = document.querySelector('[data-vm-lazy-grid]');
    const sentinel    = document.getElementById('vm-load-more-sentinel');
    const loadMoreBtn = document.getElementById('vm-load-more-btn');

    if (archiveGrid && sentinel) {
        let currentPage = parseInt(archiveGrid.dataset.currentPage || '1', 10);
        const totalPages = parseInt(archiveGrid.dataset.totalPages || '1', 10);
        let loading = false;

        function loadNextPage() {
            if (loading || currentPage >= totalPages) {
                hidePager();
                return;
            }
            loading = true;
            currentPage++;
            if (loadMoreBtn) loadMoreBtn.disabled = true;

            showSkeletons(archiveGrid, 4);

            const params = new URLSearchParams({
                action:      'vm_load_objects_page',
                nonce:       lazy.nonce || '',
                page:        currentPage,
                per_page:    archiveGrid.dataset.perPage || lazy.perPage || 12,
                parent_type: archiveGrid.dataset.parentType || '',
                parent_id:   archiveGrid.dataset.parentId   || '',
                child_type:  archiveGrid.dataset.childType  || 'object',
            });

            fetch(cfg.ajaxUrl, { method: 'POST', body: params })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    removeSkeletons(archiveGrid);
                    if (data.success && data.data.html) {
                        archiveGrid.insertAdjacentHTML('beforeend', data.data.html);
                        archiveGrid.dataset.currentPage = currentPage;
                    }
                    if (currentPage >= totalPages) hidePager();
                })
                .catch(function () {
                    removeSkeletons(archiveGrid);
                    currentPage--;
                })
                .finally(function () {
                    loading = false;
                    if (loadMoreBtn) loadMoreBtn.disabled = false;
                });
        }

        function hidePager() {
            if (sentinel)    sentinel.hidden    = true;
            if (loadMoreBtn) loadMoreBtn.hidden = true;
        }

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) loadNextPage();
            }, { rootMargin: '300px' });
            io.observe(sentinel);
        }

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadNextPage);
        }
    }

    // ====================================================
    // Deferred section loading (room/vitrine objects section)
    // ====================================================
    document.querySelectorAll('[data-vm-lazy-section]').forEach(function (section) {
        initLazySection(section);
    });

    function initLazySection(section) {
        const grid       = section.querySelector('.vm-grid');
        const perPage    = parseInt(section.dataset.perPage || lazy.perPage || 12, 10);
        const parentType = section.dataset.parentType || 'room';
        const parentId   = section.dataset.parentId   || '';
        const childType  = section.dataset.childType  || 'object';

        if (!grid || !parentId) return;

        // Count already server-rendered cards
        const preRendered = grid.querySelectorAll('.vm-card:not(.vm-card--skeleton)').length;

        // If first batch was server-rendered, calculate how many total pages remain
        // and only set up a pager for the remaining pages
        if (preRendered > 0) {
            // Fetch metadata (page 1) to find total_pages without re-rendering content
            const params = new URLSearchParams({
                action:      'vm_load_objects_page',
                nonce:       lazy.nonce || '',
                page:        1,
                per_page:    perPage,
                parent_type: parentType,
                parent_id:   parentId,
                child_type:  childType,
                meta_only:   1,
            });
            fetch(cfg.ajaxUrl, { method: 'POST', body: params })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.data.total_pages > 1) {
                        attachSectionPager(section, grid, parentType, parentId, childType, perPage, 1, data.data.total_pages);
                    }
                });
            return;
        }

        // No server-rendered content — load via AJAX (fallback / shortcode usage)
        if (!('IntersectionObserver' in window)) {
            loadSection(section);
            return;
        }
        const io = new IntersectionObserver(function (entries, observer) {
            if (entries[0].isIntersecting) {
                observer.unobserve(section);
                loadSection(section);
            }
        }, { rootMargin: '400px' });
        io.observe(section);
    }

    function loadSection(section) {
        const grid       = section.querySelector('.vm-grid');
        const parentType = section.dataset.parentType || 'room';
        const parentId   = section.dataset.parentId   || '';
        const childType  = section.dataset.childType  || 'object';
        const perPage    = parseInt(section.dataset.perPage || lazy.perPage || 12, 10);

        if (!grid || !parentId) return;

        showSkeletons(grid, parseInt(section.dataset.skeletonCount || '4', 10));

        fetchPage(parentType, parentId, childType, 1, perPage, function (data) {
            removeSkeletons(grid);
            if (data && data.html) {
                grid.innerHTML = data.html;
                if (data.total_pages > 1) {
                    attachSectionPager(section, grid, parentType, parentId, childType, perPage, 1, data.total_pages);
                }
            }
        }, function () {
            removeSkeletons(grid);
        });
    }

    function attachSectionPager(section, grid, parentType, parentId, childType, perPage, startPage, totalPages) {
        const pagerSentinel = document.createElement('div');
        pagerSentinel.className = 'vm-section-load-sentinel';
        section.querySelector('.vm-section-body').appendChild(pagerSentinel);

        let page    = startPage;
        let loading = false;

        const io = new IntersectionObserver(function (entries, observer) {
            if (!entries[0].isIntersecting || loading || page >= totalPages) return;
            loading = true;
            page++;
            showSkeletons(grid, 4);

            fetchPage(parentType, parentId, childType, page, perPage, function (data) {
                removeSkeletons(grid);
                if (data && data.html) grid.insertAdjacentHTML('beforeend', data.html);
                if (page >= totalPages) {
                    observer.unobserve(pagerSentinel);
                    pagerSentinel.remove();
                }
                loading = false;
            }, function () {
                removeSkeletons(grid);
                page--;
                loading = false;
            });
        }, { rootMargin: '200px' });

        io.observe(pagerSentinel);
    }

    // ====================================================
    // Core fetch helper
    // ====================================================
    function fetchPage(parentType, parentId, childType, page, perPage, onSuccess, onError) {
        const params = new URLSearchParams({
            action:      'vm_load_objects_page',
            nonce:       lazy.nonce || '',
            page:        page,
            per_page:    perPage,
            parent_type: parentType,
            parent_id:   parentId,
            child_type:  childType,
        });

        fetch(cfg.ajaxUrl, { method: 'POST', body: params })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    onSuccess(data.data);
                } else {
                    onError();
                }
            })
            .catch(onError);
    }

    // ====================================================
    // Skeleton helpers
    // ====================================================
    function showSkeletons(grid, count) {
        for (var i = 0; i < count; i++) {
            var sk = document.createElement('div');
            sk.className = 'vm-card vm-card--skeleton';
            sk.setAttribute('aria-hidden', 'true');
            sk.innerHTML =
                '<div class="vm-card__thumb vm-skeleton-block"></div>' +
                '<div class="vm-card__body">' +
                '<div class="vm-skeleton-line"></div>' +
                '<div class="vm-skeleton-line vm-skeleton-line--short"></div>' +
                '</div>';
            grid.appendChild(sk);
        }
    }

    function removeSkeletons(grid) {
        grid.querySelectorAll('.vm-card--skeleton').forEach(function (sk) { sk.remove(); });
    }

})();
