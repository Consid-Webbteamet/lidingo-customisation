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
const CLOSE_DELAY_MS = 380;

const syncPanelHeight = (panel) => {
    panel.style.setProperty(PANEL_MAX_HEIGHT_CSS_VAR, `${panel.scrollHeight}px`);
};

const clearPendingClose = (panel) => {
    if (typeof panel.__navigationCardCloseTimeout === 'number') {
        window.clearTimeout(panel.__navigationCardCloseTimeout);
        panel.__navigationCardCloseTimeout = null;
    }
};

const syncButtonLabel = (button, isExpanded) => {
    const label = button.querySelector(TOGGLE_LABEL_SELECTOR);

    if (!(label instanceof HTMLElement)) {
        return;
    }

    if (!button.dataset.navigationCardCollapsedLabel) {
        button.dataset.navigationCardCollapsedLabel = label.textContent?.trim() ?? '';
    }

    const expandedLabel = button.dataset.navigationCardExpandedLabel ?? EXPANDED_LABEL;

    label.textContent = isExpanded
        ? expandedLabel
        : button.dataset.navigationCardCollapsedLabel;
};

const openPanel = (button, panel) => {
    const queuedFrame = panel.__navigationCardToggleFrame;

    if (typeof queuedFrame === 'number') {
        cancelAnimationFrame(queuedFrame);
    }

    clearPendingClose(panel);
    panel.hidden = false;
    syncPanelHeight(panel);
    button.setAttribute('aria-expanded', 'true');
    syncButtonLabel(button, true);
    panel.__navigationCardToggleFrame = requestAnimationFrame(() => {
        panel.classList.add(PANEL_OPEN_CLASS);
        panel.__navigationCardToggleFrame = null;
    });
};

const closePanel = (button, panel) => {
    const queuedFrame = panel.__navigationCardToggleFrame;

    if (typeof queuedFrame === 'number') {
        cancelAnimationFrame(queuedFrame);
        panel.__navigationCardToggleFrame = null;
    }

    panel.classList.remove(PANEL_OPEN_CLASS);
    button.setAttribute('aria-expanded', 'false');
    syncButtonLabel(button, false);
    clearPendingClose(panel);
    panel.__navigationCardCloseTimeout = window.setTimeout(() => {
        if (button.getAttribute('aria-expanded') !== 'true') {
            panel.hidden = true;
        }

        panel.__navigationCardCloseTimeout = null;
    }, CLOSE_DELAY_MS);
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

    panel.hidden = !isExpanded;

    if (isExpanded) {
        syncPanelHeight(panel);
    }

    panel.classList.toggle(PANEL_OPEN_CLASS, isExpanded);
    syncButtonLabel(button, isExpanded);

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
