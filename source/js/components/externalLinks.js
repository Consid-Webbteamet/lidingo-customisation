const EXTERNAL_LINK_SELECTOR = [
    'a.button[href]',
    'a.c-button[href]',
    '.modularity-mod-contact-card a[href]',
    '.block-modularity-mod-contact-card a[href]',
    '.modularity-mod-section-full .c-segment__text a[href]',
    '.block-modularity-mod-section-full .c-segment__text a[href]',
    '.modularity-mod-manual-input a[href]',
    '.block-modularity-mod-manual-input a[href]',
    '.modularity-mod-manualinput a[href]',
    '.block-modularity-mod-manualinput a[href]'
].join(',');

const NEW_TAB_LINK_SELECTOR = 'a[target="_blank"]';
const NEW_TAB_TEXT_CLASS = 'lidingo-external-link-new-tab-text';
const NEW_TAB_TEXT = 'Öppnas i ny flik';

const shouldShowExternalIcon = (link) => link.target === '_blank';

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
    document.querySelectorAll(EXTERNAL_LINK_SELECTOR).forEach((link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        link.toggleAttribute('data-external-link', shouldShowExternalIcon(link));
    });

    document.querySelectorAll(NEW_TAB_LINK_SELECTOR).forEach((link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        syncNewTabText(link);
    });
}
