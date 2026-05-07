const HEADER_SELECTOR = '.site-header.c-header.c-header--flexible';
const AREA_SELECTOR = [
    '.c-header__upper-left',
    '.c-header__upper-center',
    '.c-header__upper-right',
    '.c-header__lower-left',
    '.c-header__lower-center',
    '.c-header__lower-right',
].join(', ');
const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

const isVisible = (element) => {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    if (element.closest('[aria-hidden="true"]')) {
        return false;
    }

    const style = window.getComputedStyle(element);

    return style.display !== 'none'
        && style.visibility !== 'hidden'
        && element.getClientRects().length > 0;
};

const getElementOrder = (element) => {
    const computedOrder = Number.parseInt(window.getComputedStyle(element).order, 10);

    return Number.isNaN(computedOrder) ? 0 : computedOrder;
};

const getOrderedWrappers = (header) => Array.from(header.querySelectorAll(AREA_SELECTOR))
    .filter((area) => area instanceof HTMLElement)
    .flatMap((area) => Array.from(area.children))
    .filter((wrapper) => wrapper instanceof HTMLElement && isVisible(wrapper))
    .map((wrapper, index) => ({
        wrapper,
        index,
        order: getElementOrder(wrapper),
    }))
    .sort((left, right) => {
        if (left.order !== right.order) {
            return left.order - right.order;
        }

        return left.index - right.index;
    })
    .map(({ wrapper }) => wrapper);

const getFocusableElements = (header) => getOrderedWrappers(header)
    .flatMap((wrapper) => Array.from(wrapper.querySelectorAll(FOCUSABLE_SELECTOR)))
    .filter((element) => element instanceof HTMLElement && isVisible(element));

const hasExpandedHeaderUi = (header) => {
    if (header.querySelector('.collapsible-search-form.is-open')) {
        return true;
    }

    if (header.querySelector('.site-language-menu.is-expanded')) {
        return true;
    }

    const drawer = document.querySelector('#drawer');

    return drawer instanceof HTMLElement && drawer.getAttribute('aria-hidden') === 'false';
};

const handleHeaderTabOrder = (event, header) => {
    if (event.key !== 'Tab' || event.defaultPrevented || hasExpandedHeaderUi(header)) {
        return;
    }

    const focusableElements = getFocusableElements(header);

    if (focusableElements.length < 2) {
        return;
    }

    const currentIndex = focusableElements.findIndex((element) => element === document.activeElement);

    if (currentIndex === -1) {
        return;
    }

    const nextIndex = event.shiftKey ? currentIndex - 1 : currentIndex + 1;

    if (nextIndex < 0 || nextIndex >= focusableElements.length) {
        return;
    }

    event.preventDefault();
    focusableElements[nextIndex].focus();
};

const initHeaderFocusOrder = () => {
    document.querySelectorAll(HEADER_SELECTOR).forEach((header) => {
        if (!(header instanceof HTMLElement)) {
            return;
        }

        header.addEventListener('keydown', (event) => {
            handleHeaderTabOrder(event, header);
        });
    });
};

export default initHeaderFocusOrder;
