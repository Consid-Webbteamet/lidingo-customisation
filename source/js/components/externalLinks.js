const LINK_SELECTOR = 'a[href]';
const TEXT_LINK_EXCLUDED_SELECTOR = [
    '.button',
    '.c-button',
    '[class*="__card"]',
    '[class*="__card-link"]',
    '.c-breadcrumb__link',
    '.c-pagination-block__link',
    '.navigation-card__link'
].join(',');
const NON_TEXTUAL_CONTENT_SELECTOR = [
    'img',
    'picture',
    'svg',
    '.c-icon',
    '.material-symbols-rounded',
    '[class*="__icon"]'
].join(',');

const NEW_TAB_LINK_SELECTOR = 'a[target="_blank"]';
const EXTERNAL_LINK_TEXT_CLASS = 'lidingo-external-link-text';
const EXTERNAL_LINK_TEXT = 'Extern webbplats';
const NEW_TAB_TEXT_CLASS = 'lidingo-external-link-new-tab-text';
const NEW_TAB_TEXT = 'Öppnas i ny flik';

const isExternalLink = (link) => {
    try {
        const url = new URL(link.href, window.location.href);

        return ['http:', 'https:'].includes(url.protocol) && url.origin !== window.location.origin;
    } catch {
        return false;
    }
};

const isTextLink = (link) => (
    !link.matches(TEXT_LINK_EXCLUDED_SELECTOR)
    && !link.querySelector(NON_TEXTUAL_CONTENT_SELECTOR)
    && link.textContent.trim() !== ''
);

const syncExternalText = (link, isExternal) => {
    const existingText = link.querySelector(`.${EXTERNAL_LINK_TEXT_CLASS}`);

    if (!isExternal) {
        existingText?.remove();

        return;
    }

    if (existingText instanceof HTMLElement) {
        return;
    }

    const text = document.createElement('span');
    text.className = `sr-only ${EXTERNAL_LINK_TEXT_CLASS}`;
    text.textContent = ` (${EXTERNAL_LINK_TEXT})`;
    link.append(text);
};

const syncNewTabText = (link) => {
    const existingText = link.querySelector(`.${NEW_TAB_TEXT_CLASS}`);

    if (link.target !== '_blank') {
        existingText?.remove();

        return;
    }

    if (existingText instanceof HTMLElement) {
        return;
    }

    const text = document.createElement('span');
    text.className = `sr-only ${NEW_TAB_TEXT_CLASS}`;
    text.textContent = ` (${NEW_TAB_TEXT})`;
    link.append(text);
};

export default function initExternalLinks() {
    document.querySelectorAll(LINK_SELECTOR).forEach((link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        const isExternal = isExternalLink(link);
        const isExternalText = isExternal && isTextLink(link);

        link.toggleAttribute('data-external-link', isExternal && !isExternalText);
        link.toggleAttribute('data-external-text-link', isExternalText);
        syncExternalText(link, isExternal);
    });

    document.querySelectorAll(NEW_TAB_LINK_SELECTOR).forEach((link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        syncNewTabText(link);
    });
}
