const CARD_SELECTOR = '[data-js-navigation-card]';
const TOGGLE_SELECTOR = '[data-js-navigation-card-toggle]';
const PANEL_SELECTOR = '[data-js-navigation-card-panel]';
const BODY_SELECTOR = '.navigation-card__body';
const TOGGLE_LABEL_SELECTOR = '.navigation-card__toggle-label';
const INITIALIZED_ATTRIBUTE = 'data-navigation-card-initialized';
const PANEL_OPEN_CLASS = 'is-open';
const PANEL_INLINE_CLASS = 'navigation-card__hidden--inline';
const EXPANDED_LABEL = 'Dölj';
const PANEL_MAX_HEIGHT_CSS_VAR = '--navigation-card-panel-max-height';

const syncPanelHeight = (panel) => {
    panel.style.setProperty(PANEL_MAX_HEIGHT_CSS_VAR, `${panel.scrollHeight}px`);
};

const syncButtonLabel = (button, isExpanded) => {
    const label = button.querySelector(TOGGLE_LABEL_SELECTOR);

    if (!(label instanceof HTMLElement)) {
        return;
    }

    if (!button.dataset.navigationCardCollapsedLabel) {
        button.dataset.navigationCardCollapsedLabel = label.textContent?.trim() ?? '';
    }

    if (!button.dataset.navigationCardCollapsedAriaLabel) {
        button.dataset.navigationCardCollapsedAriaLabel = button.getAttribute('aria-label') ?? '';
    }

    const expandedLabel = button.dataset.navigationCardExpandedLabel ?? EXPANDED_LABEL;
    const expandedAriaLabel = button.dataset.navigationCardExpandedAriaLabel ?? expandedLabel;

    label.textContent = isExpanded
        ? expandedLabel
        : button.dataset.navigationCardCollapsedLabel;

    button.setAttribute(
        'aria-label',
        isExpanded
            ? expandedAriaLabel
            : button.dataset.navigationCardCollapsedAriaLabel,
    );
};

const openPanel = (button, panel) => {
    syncPanelHeight(panel);
    panel.removeAttribute('aria-hidden');
    panel.inert = false;
    button.setAttribute('aria-expanded', 'true');
    syncButtonLabel(button, true);
    requestAnimationFrame(() => {
        panel.classList.add(PANEL_OPEN_CLASS);
    });
};

const closePanel = (button, panel) => {
    panel.classList.remove(PANEL_OPEN_CLASS);
    panel.setAttribute('aria-hidden', 'true');
    panel.inert = true;
    button.setAttribute('aria-expanded', 'false');
    syncButtonLabel(button, false);
};

const toggleCard = (button, panel) => {
    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
        closePanel(button, panel);
        return;
    }

    openPanel(button, panel);
};

const initCard = (card) => {
    if (!(card instanceof HTMLElement) || card.hasAttribute(INITIALIZED_ATTRIBUTE)) {
        return;
    }

    const button = card.querySelector(TOGGLE_SELECTOR);
    const panel = card.querySelector(PANEL_SELECTOR);
    const body = card.querySelector(BODY_SELECTOR);

    if (!(button instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
        return;
    }

    if (body instanceof HTMLElement && panel.parentElement !== body) {
        body.append(panel);
        panel.classList.add(PANEL_INLINE_CLASS);
    }

    card.setAttribute(INITIALIZED_ATTRIBUTE, 'true');
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    panel.hidden = false;
    syncPanelHeight(panel);
    panel.classList.toggle(PANEL_OPEN_CLASS, isExpanded);
    panel.inert = !isExpanded;
    syncButtonLabel(button, isExpanded);

    if (!isExpanded) {
        panel.setAttribute('aria-hidden', 'true');
    } else {
        panel.removeAttribute('aria-hidden');
    }

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
