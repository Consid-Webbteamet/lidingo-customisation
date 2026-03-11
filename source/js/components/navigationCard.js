const CARD_SELECTOR = '[data-js-navigation-card]';
const TOGGLE_SELECTOR = '[data-js-navigation-card-toggle]';
const PANEL_SELECTOR = '[data-js-navigation-card-panel]';
const INITIALIZED_ATTRIBUTE = 'data-navigation-card-initialized';

const toggleCard = (button, panel) => {
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    const nextExpanded = !isExpanded;

    button.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
    panel.hidden = !nextExpanded;
};

const initCard = (card) => {
    if (!(card instanceof HTMLElement) || card.hasAttribute(INITIALIZED_ATTRIBUTE)) {
        return;
    }

    const button = card.querySelector(TOGGLE_SELECTOR);
    const panel = card.querySelector(PANEL_SELECTOR);

    if (!(button instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
        return;
    }

    card.setAttribute(INITIALIZED_ATTRIBUTE, 'true');
    panel.hidden = button.getAttribute('aria-expanded') !== 'true';

    button.addEventListener('click', () => {
        toggleCard(button, panel);
    });
};

const initNavigationCard = () => {
    if (typeof document === 'undefined') {
        return;
    }

    document.querySelectorAll(CARD_SELECTOR).forEach((card) => {
        initCard(card);
    });
};

export default initNavigationCard;
