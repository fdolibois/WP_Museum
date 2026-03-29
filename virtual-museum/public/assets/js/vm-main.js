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

    // ---- Gallery Slider ----
    document.querySelectorAll('.vm-gallery').forEach(function (gallery) {
        const thumbs   = gallery.querySelectorAll('.vm-gallery__thumb');
        const active   = gallery.querySelector('#vm-gallery-active');
        const btnPrev  = gallery.querySelector('.vm-gallery__prev');
        const btnNext  = gallery.querySelector('.vm-gallery__next');
        let current    = 0;

        if (!thumbs.length || !active) return;

        function goTo(index) {
            if (index < 0) index = thumbs.length - 1;
            if (index >= thumbs.length) index = 0;
            current = index;

            const thumb = thumbs[current];
            const src   = thumb.dataset.src;
            const title = thumb.dataset.title || '';
            const imgEl = active.querySelector('img');
            const cap   = active.querySelector('.vm-gallery__active-caption h2');

            if (imgEl && src) imgEl.src = src;
            if (cap) cap.textContent = title;

            thumbs.forEach(function (t, i) {
                t.classList.toggle('vm-gallery__thumb--active', i === current);
            });

            // Scroll filmstrip
            thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        thumbs.forEach(function (thumb, i) {
            thumb.addEventListener('click', function () { goTo(i); });
        });

        if (btnPrev) btnPrev.addEventListener('click', function () { goTo(current - 1); });
        if (btnNext) btnNext.addEventListener('click', function () { goTo(current + 1); });

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') goTo(current - 1);
            if (e.key === 'ArrowRight') goTo(current + 1);
        });
    });

})();
