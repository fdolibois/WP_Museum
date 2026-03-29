/**
 * Virtual Museum — Lightbox
 */
(function () {
    'use strict';

    const settings = (typeof vmPublic !== 'undefined') ? vmPublic.settings : {};
    if (!settings.lightbox) return;

    const i18n = (typeof vmPublic !== 'undefined') ? vmPublic.i18n : {};

    // Build overlay DOM
    const overlay = document.createElement('div');
    overlay.className = 'vm-lightbox-overlay';
    overlay.innerHTML = `
        <button class="vm-lightbox__prev" aria-label="previous">&#8592;</button>
        <div class="vm-lightbox">
            <button class="vm-lightbox__close" aria-label="${i18n.close || 'Close'}">&#10005;</button>
            <img src="" alt="">
            <p class="vm-lightbox__caption"></p>
        </div>
        <button class="vm-lightbox__next" aria-label="next">&#8594;</button>`;
    document.body.appendChild(overlay);

    const img     = overlay.querySelector('img');
    const caption = overlay.querySelector('.vm-lightbox__caption');
    const btnClose = overlay.querySelector('.vm-lightbox__close');
    const btnPrev  = overlay.querySelector('.vm-lightbox__prev');
    const btnNext  = overlay.querySelector('.vm-lightbox__next');

    let items   = [];
    let current = 0;

    function open(index) {
        current = index;
        const item = items[current];
        img.src     = item.src;
        img.alt     = item.title || '';
        caption.textContent = item.title || '';
        overlay.classList.add('vm-lightbox--open');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        overlay.classList.remove('vm-lightbox--open');
        document.body.style.overflow = '';
    }

    function goTo(dir) {
        current = (current + dir + items.length) % items.length;
        open(current);
    }

    btnClose.addEventListener('click', close);
    btnPrev.addEventListener('click', function () { goTo(-1); });
    btnNext.addEventListener('click', function () { goTo(1); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

    document.addEventListener('keydown', function (e) {
        if (!overlay.classList.contains('vm-lightbox--open')) return;
        if (e.key === 'Escape')      close();
        if (e.key === 'ArrowLeft')  goTo(-1);
        if (e.key === 'ArrowRight') goTo(1);
    });

    // Collect all lightbox triggers on page
    function initTriggers() {
        const triggers = document.querySelectorAll('.vm-lightbox-trigger');
        items = Array.from(triggers).map(function (el) {
            return { src: el.href || el.dataset.src || '', title: el.querySelector('img') ? el.querySelector('img').alt : '' };
        });
        triggers.forEach(function (el, i) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                open(i);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTriggers);
    } else {
        initTriggers();
    }

})();
