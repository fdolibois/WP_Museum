/**
 * Virtual Museum — Main JS
 */
(function () {
    'use strict';

    // ---- Section Toggle ----
    document.querySelectorAll('.vm-section-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', String(!expanded));
            const body = this.closest('.vm-section-title').nextElementSibling;
            if (body) {
                body.style.display = expanded ? 'none' : '';
            }
        });
    });


})();
