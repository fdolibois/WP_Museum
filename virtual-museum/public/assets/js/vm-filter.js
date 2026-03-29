/**
 * Virtual Museum — Filter Bar
 */
(function () {
    'use strict';

    const filterBar = document.getElementById('vm-filter-bar');
    if (!filterBar) return;

    const resetBtn = document.getElementById('vm-filter-reset');
    const selects  = filterBar.querySelectorAll('.vm-filter-select');

    // Simple client-side filter (cards already on page)
    function applyFilter() {
        const filters = {};
        selects.forEach(function (sel) {
            if (sel.value) filters[sel.dataset.filter] = sel.value;
        });

        const cards = document.querySelectorAll('.vm-card--object');
        cards.forEach(function (card) {
            let show = true;

            if (filters.media_type) {
                const cardType = card.classList.contains('vm-card--' + filters.media_type);
                if (!cardType) show = false;
            }

            card.style.display = show ? '' : 'none';
        });
    }

    selects.forEach(function (sel) {
        sel.addEventListener('change', applyFilter);
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            selects.forEach(function (sel) { sel.value = ''; });
            document.querySelectorAll('.vm-card--object').forEach(function (c) { c.style.display = ''; });
        });
    }

})();
