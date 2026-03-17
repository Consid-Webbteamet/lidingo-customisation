const TOC_LINK_SELECTOR = '.modularity-mod-toc .c-toc__link';
const HEADER_SELECTOR = '.site-header.c-header.c-header--flexible, .site-header.c-header, .c-header';
const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

const getScrollOffset = (heading) => {
    if (heading instanceof HTMLElement) {
        const scrollMarginTop = window.getComputedStyle(heading).scrollMarginTop;
        const parsedScrollMarginTop = Number.parseFloat(scrollMarginTop);

        if (Number.isFinite(parsedScrollMarginTop) && parsedScrollMarginTop > 0) {
            return parsedScrollMarginTop;
        }
    }

    const header = document.querySelector(HEADER_SELECTOR);

    return header instanceof HTMLElement ? header.getBoundingClientRect().height : 0;
};

const shouldReduceMotion = () => window.matchMedia(REDUCED_MOTION_QUERY).matches;

const scrollToHeading = (heading) => {
    const offset = getScrollOffset(heading);
    const top = heading.getBoundingClientRect().top + window.scrollY - offset;

    window.scrollTo({
        top,
        behavior: shouldReduceMotion() ? 'auto' : 'smooth'
    });

    history.pushState(null, '', `#${heading.id}`);
    heading.setAttribute('tabindex', '-1');
    heading.focus({ preventScroll: true });
};

const initModularityTocOffset = () => {
    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element
            ? event.target.closest(TOC_LINK_SELECTOR)
            : null;

        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        const hash = link.hash || link.getAttribute('href');

        if (!hash || !hash.startsWith('#')) {
            return;
        }

        const heading = document.getElementById(hash.slice(1));

        if (!(heading instanceof HTMLElement)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        scrollToHeading(heading);
    }, true);
};

export default initModularityTocOffset;
