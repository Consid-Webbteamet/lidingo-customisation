const TOC_ROOT_SELECTOR = '.modularity-mod-toc';
const TOC_LINK_SELECTOR = '.c-toc__link';
const TOC_ITEM_SELECTOR = '.c-toc__item';

const deduplicateTocItems = (tocRoot) => {
    const seenTargets = new Set();
    const links = tocRoot.querySelectorAll(TOC_LINK_SELECTOR);

    links.forEach((link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        const target = link.getAttribute('href');

        if (!target || !target.startsWith('#')) {
            return;
        }

        if (seenTargets.has(target)) {
            const item = link.closest(TOC_ITEM_SELECTOR);

            if (item instanceof HTMLElement) {
                item.remove();
            }

            return;
        }

        seenTargets.add(target);
    });
};

const initModularityTocDeduplicate = () => {
    document.querySelectorAll(TOC_ROOT_SELECTOR).forEach((tocRoot) => {
        if (!(tocRoot instanceof HTMLElement)) {
            return;
        }

        let cleanupFrame = null;
        const scheduleCleanup = () => {
            if (cleanupFrame !== null) {
                cancelAnimationFrame(cleanupFrame);
            }

            cleanupFrame = window.requestAnimationFrame(() => {
                deduplicateTocItems(tocRoot);
                cleanupFrame = null;
            });
        };

        scheduleCleanup();

        const observer = new MutationObserver(() => {
            scheduleCleanup();
        });

        observer.observe(tocRoot, {
            childList: true,
            subtree: true
        });
    });
};

export default initModularityTocDeduplicate;
