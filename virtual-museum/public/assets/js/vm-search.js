/**
 * Virtual Museum — Live Search
 */
(function () {
    'use strict';

    const input     = document.getElementById('vm-live-search');
    const container = document.getElementById('vm-search-results');
    if (!input || !container) return;

    const ajaxUrl = (typeof vmPublic !== 'undefined') ? vmPublic.ajaxUrl : '';
    const i18n    = (typeof vmPublic !== 'undefined') ? vmPublic.i18n : {};
    let timer;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) {
            container.hidden = true;
            container.innerHTML = '';
            return;
        }
        container.innerHTML = '<p class="vm-search-loading">' + (i18n.loading || 'Suche...') + '</p>';
        container.hidden = false;

        timer = setTimeout(function () {
            const fd = new FormData();
            fd.append('action', 'vm_search');
            fd.append('q', q);
            fd.append('type', 'objects');

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success || !data.data.results.length) {
                        container.innerHTML = '<p class="vm-search-empty">' + (i18n.noResults || 'Keine Ergebnisse') + '</p>';
                        return;
                    }
                    const html = data.data.results.map(function (item) {
                        const thumb = item.thumb ? '<img src="' + item.thumb + '" alt="">' : '';
                        return '<a href="' + item.url + '" class="vm-search-result">' +
                            '<span class="vm-search-result__thumb">' + thumb + '</span>' +
                            '<span class="vm-search-result__info">' +
                            '<strong>' + item.title + '</strong>' +
                            (item.year ? ' <span class="vm-year">' + item.year + '</span>' : '') +
                            '</span></a>';
                    }).join('');
                    container.innerHTML = html;
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
