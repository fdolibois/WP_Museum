/**
 * Virtual Museum — Contextual Breadcrumb
 * Preserves vm_context parameter when navigating, stores in sessionStorage.
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'vm_context';

    // Read context from URL
    function getContextFromUrl() {
        return new URLSearchParams(window.location.search).get('vm_context') || '';
    }

    // Store context on page load if present
    const urlContext = getContextFromUrl();
    if (urlContext) {
        sessionStorage.setItem(STORAGE_KEY, urlContext);
    }

    // Augment internal links to museum pages with context
    document.addEventListener('DOMContentLoaded', function () {
        const context = urlContext || sessionStorage.getItem(STORAGE_KEY) || '';
        if (!context) return;

        document.querySelectorAll('a[href]').forEach(function (link) {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:')) return;

            // Only augment links to museum CPT URLs
            if (href.includes('/museum/')) {
                try {
                    const url = new URL(link.href);
                    if (!url.searchParams.has('vm_context')) {
                        url.searchParams.set('vm_context', context);
                        link.href = url.toString();
                    }
                } catch (e) {
                    // skip invalid URLs
                }
            }
        });
    });

    // Clear context when navigating away from museum pages
    window.addEventListener('beforeunload', function () {
        const isMuseumPage = window.location.pathname.includes('/museum/');
        if (!isMuseumPage) {
            sessionStorage.removeItem(STORAGE_KEY);
        }
    });

})();
