const HEADER_SELECTOR = '.site-header.c-header.c-header--flexible';
const SCROLLED_CLASS = 'is-scrolled';
const SCROLL_ENTER_THRESHOLD = 12;
const SCROLL_EXIT_THRESHOLD = 2;
const SCROLL_REENTER_AFTER_TOP_RELEASE = 56;

const initScrolledHeaderState = () => {
    const headers = Array.from(document.querySelectorAll(HEADER_SELECTOR));

    if (headers.length === 0) {
        return;
    }

    let frameRequested = false;
    let isScrolled = false;
    let reentryLockedFromTop = false;
    let hasAppliedState = false;
    let appliedState = false;

    const applyScrolledState = () => {
        headers.forEach((header) => {
            header.classList.toggle(SCROLLED_CLASS, isScrolled);
        });
    };

    const syncScrolledState = () => {
        const scrollY = Math.max(window.scrollY, 0);

        if (!isScrolled && scrollY >= SCROLL_ENTER_THRESHOLD) {
            if (!reentryLockedFromTop) {
                isScrolled = true;
            }
        } else if (isScrolled && scrollY <= SCROLL_EXIT_THRESHOLD) {
            isScrolled = false;
            reentryLockedFromTop = true;
        }

        if (!isScrolled && reentryLockedFromTop && scrollY >= SCROLL_REENTER_AFTER_TOP_RELEASE) {
            isScrolled = true;
            reentryLockedFromTop = false;
        }

        if (!hasAppliedState) {
            applyScrolledState();
            appliedState = isScrolled;
            hasAppliedState = true;
            return;
        }

        if (appliedState !== isScrolled) {
            applyScrolledState();
            appliedState = isScrolled;
        }
    };

    const onScroll = () => {
        if (frameRequested) {
            return;
        }

        frameRequested = true;

        window.requestAnimationFrame(() => {
            frameRequested = false;
            syncScrolledState();
        });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    syncScrolledState();
};

export default initScrolledHeaderState;
