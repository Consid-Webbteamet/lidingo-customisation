const ACCORDION_TOGGLE_SELECTOR = '.c-accordion [js-expand-button][href^="#"]';
const INITIALIZED_ATTRIBUTE = 'data-lidingo-accordion-hash-guard';

const shouldHandleClick = (event) => !event.defaultPrevented
    && event.button === 0
    && !event.metaKey
    && !event.ctrlKey
    && !event.shiftKey
    && !event.altKey;

const initAccordionHashGuard = () => {
    if (typeof document === 'undefined') {
        return;
    }

    document.querySelectorAll(ACCORDION_TOGGLE_SELECTOR).forEach((toggle) => {
        if (!(toggle instanceof HTMLAnchorElement) || toggle.hasAttribute(INITIALIZED_ATTRIBUTE)) {
            return;
        }

        toggle.setAttribute(INITIALIZED_ATTRIBUTE, 'true');
        toggle.addEventListener('click', (event) => {
            if (!shouldHandleClick(event)) {
                return;
            }

            event.preventDefault();
        });
    });
};

export default initAccordionHashGuard;
