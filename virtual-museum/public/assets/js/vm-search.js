/**
 * Virtual Museum — Live Search
 * B004: XSS-Fix: DOM-API statt innerHTML + String-Konkatenation für Suchergebnisse
 */
(function () {
    'use strict';

    const input     = document.getElementById('vm-live-search');
    const container = document.getElementById('vm-search-results');
    if (!input || !container) return;

    const ajaxUrl = (typeof vmPublic !== 'undefined') ? vmPublic.ajaxUrl : '';
    const i18n    = (typeof vmPublic !== 'undefined') ? vmPublic.i18n : {};
    let timer;

    // B004: Sichere Hilfsfunktion – erstellt DOM-Elemente statt HTML-Strings
    function buildResultItem(item) {
        const a = document.createElement('a');
        // Nur http/https-URLs zulassen
        const url = String(item.url || '');
        if (/^https?:\/\//i.test(url)) {
            a.href = url;
        }
        a.className = 'vm-search-result';

        const thumbSpan = document.createElement('span');
        thumbSpan.className = 'vm-search-result__thumb';
        if (item.thumb && /^https?:\/\//i.test(String(item.thumb))) {
            const img = document.createElement('img');
            img.src = item.thumb;
            img.alt = '';
            thumbSpan.appendChild(img);
        }
        a.appendChild(thumbSpan);

        const infoSpan = document.createElement('span');
        infoSpan.className = 'vm-search-result__info';

        const strong = document.createElement('strong');
        strong.textContent = item.title || ''; // textContent escaped automatisch
        infoSpan.appendChild(strong);

        if (item.year) {
            const yearSpan = document.createElement('span');
            yearSpan.className = 'vm-year';
            yearSpan.textContent = ' ' + item.year;
            infoSpan.appendChild(yearSpan);
        }

        a.appendChild(infoSpan);
        return a;
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) {
            container.hidden = true;
            container.innerHTML = '';
            return;
        }

        // Loading-Zustand mit textContent statt innerHTML
        container.innerHTML = '';
        const loadingP = document.createElement('p');
        loadingP.className = 'vm-search-loading';
        loadingP.textContent = i18n.loading || 'Suche...';
        container.appendChild(loadingP);
        container.hidden = false;

        timer = setTimeout(function () {
            const fd = new FormData();
            fd.append('action', 'vm_search');
            fd.append('q', q);
            fd.append('type', 'objects');

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    container.innerHTML = '';
                    if (!data.success || !data.data.results.length) {
                        const p = document.createElement('p');
                        p.className = 'vm-search-empty';
                        p.textContent = i18n.noResults || 'Keine Ergebnisse';
                        container.appendChild(p);
                        return;
                    }
                    data.data.results.forEach(function (item) {
                        container.appendChild(buildResultItem(item));
                    });
                })
                .catch(function () {
                    container.hidden = true;
                });
        }, 300);
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !container.contains(e.target)) {
            container.hidden = true;
        }
    });

})();
