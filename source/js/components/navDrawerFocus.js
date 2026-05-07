const DRAWER_SELECTOR = ".c-drawer";
const MENU_TRIGGER_SELECTOR = ".mobile-menu-trigger";
const CLOSE_BUTTON_SELECTOR = ".c-drawer__header .c-button";

const focusDrawerCloseButton = () => {
  const closeButton = document.querySelector(
    `${DRAWER_SELECTOR} ${CLOSE_BUTTON_SELECTOR}`
  );

  if (!(closeButton instanceof HTMLElement)) {
    return;
  }

  closeButton.focus({ preventScroll: true });
};

const scheduleCloseButtonFocus = () => {
  window.requestAnimationFrame(() => {
    window.requestAnimationFrame(focusDrawerCloseButton);
  });
};

const initNavDrawerFocus = () => {
  document.addEventListener("click", (event) => {
    const trigger = event.target?.closest?.(MENU_TRIGGER_SELECTOR);

    if (!(trigger instanceof HTMLElement)) {
      return;
    }

    scheduleCloseButtonFocus();
  });
};

export default initNavDrawerFocus;
