// Adds a single floating hover indicator for drawer submenus at depth 2 and deeper.
// The indicator follows the pointer vertically, hides outside submenus, and can
// extend beyond the drawer edge because it is rendered in the document body.
const DRAWER_SELECTOR = ".c-drawer";
const SUBMENU_SELECTOR =
  "ul.c-nav.c-nav--depth-2, ul.c-nav.c-nav--depth-3, ul.c-nav.c-nav--depth-4, ul.c-nav.c-nav--depth-5";
const INDICATOR_CLASS = "lidingo-nav-hover-indicator";
const INDICATOR_VISIBLE_ATTRIBUTE = "data-lidingo-nav-indicator-visible";
const POSITION_PROPERTY = "--lidingo-nav-indicator-y";
const LEFT_PROPERTY = "--lidingo-nav-indicator-left";
const SUBMENU_LEFT_PROPERTY = "--lidingo-nav-indicator-submenu-left";
const SUPPORTS_HOVER_QUERY = "(hover: hover)";
const FALLBACK_INDICATOR_HEIGHT = 8;

const initializedDrawers = new WeakSet();

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

// Checks whether a value is a DOM element before using element-specific APIs.
const isElement = (value) => value instanceof Element;

// Limits the feature to environments where hover interactions are available.
const isHoverCapable = () =>
  typeof window !== "undefined" &&
  typeof document !== "undefined" &&
  window.matchMedia(SUPPORTS_HOVER_QUERY).matches;

// Creates the floating indicator element that is shared by one drawer.
const createIndicator = () => {
  const indicator = document.createElement("span");
  indicator.className = INDICATOR_CLASS;
  indicator.setAttribute("aria-hidden", "true");
  indicator.style.setProperty(SUBMENU_LEFT_PROPERTY, "0px");
  indicator.style.setProperty(LEFT_PROPERTY, "0px");
  indicator.setAttribute(INDICATOR_VISIBLE_ATTRIBUTE, "false");

  document.body.append(indicator);

  return indicator;
};

// Updates the visibility state used by CSS to fade the indicator in or out.
const setIndicatorVisible = (indicator, isVisible) => {
  indicator.setAttribute(
    INDICATOR_VISIBLE_ATTRIBUTE,
    isVisible ? "true" : "false",
  );
};

// Reads the rendered indicator height, with a fallback before the first paint.
const getIndicatorHeight = (indicator) => {
  const { height } = indicator.getBoundingClientRect();

  return height || FALLBACK_INDICATOR_HEIGHT;
};

// Finds the active depth 2+ submenu under the pointer within the current drawer.
const getHoveredSubmenu = (drawer, target) => {
  if (!isElement(target)) {
    return null;
  }

  const submenu = target.closest(SUBMENU_SELECTOR);

  if (!(submenu instanceof HTMLElement) || !drawer.contains(submenu)) {
    return null;
  }

  return submenu;
};

// Calculates and writes the floating indicator position for the hovered submenu.
const positionIndicator = (submenu, indicator, pointerClientY) => {
  const submenuBounds = submenu.getBoundingClientRect();
  const indicatorHeight = getIndicatorHeight(indicator);
  const halfIndicatorHeight = indicatorHeight / 2;
  const relativeSubmenuY = pointerClientY - submenuBounds.top;
  const clampedSubmenuY = clamp(
    relativeSubmenuY,
    halfIndicatorHeight,
    Math.max(halfIndicatorHeight, submenuBounds.height - halfIndicatorHeight),
  );
  const viewportY = submenuBounds.top + clampedSubmenuY;
  const indicatorLeft =
    window.getComputedStyle(submenu).getPropertyValue(LEFT_PROPERTY).trim() ||
    "0px";

  indicator.style.setProperty(POSITION_PROPERTY, `${viewportY}px`);
  indicator.style.setProperty(SUBMENU_LEFT_PROPERTY, `${submenuBounds.left}px`);
  indicator.style.setProperty(LEFT_PROPERTY, indicatorLeft);
};

// Initializes one drawer only once and attaches its hover controller.
const initializeDrawer = (drawer) => {
  if (!(drawer instanceof HTMLElement) || initializedDrawers.has(drawer)) {
    return;
  }

  initializedDrawers.add(drawer);
  createDrawerController(drawer);
};

// Manages one drawer's hover state, animation frame scheduling, and cleanup behavior.
const createDrawerController = (drawer) => {
  const indicator = createIndicator();

  let activeSubmenu = null;
  let queuedPointerY = 0;
  let frameRequested = false;

  // Hides the indicator when the pointer is outside a valid submenu context.
  const hideIndicator = () => {
    activeSubmenu = null;
    setIndicatorVisible(indicator, false);
  };

  // Applies the latest queued pointer position in the next animation frame.
  const flushIndicatorPosition = () => {
    frameRequested = false;

    if (
      !(activeSubmenu instanceof HTMLElement) ||
      !drawer.contains(activeSubmenu)
    ) {
      hideIndicator();
      return;
    }

    positionIndicator(activeSubmenu, indicator, queuedPointerY);
    setIndicatorVisible(indicator, true);
  };

  // Coalesces repeated pointer events into a single DOM update per frame.
  const scheduleIndicatorPosition = (submenu, pointerClientY) => {
    activeSubmenu = submenu;
    queuedPointerY = pointerClientY;

    if (frameRequested) {
      return;
    }

    frameRequested = true;
    window.requestAnimationFrame(flushIndicatorPosition);
  };

  // Tracks pointer movement and either updates or hides the indicator.
  const handlePointerMove = (event) => {
    if (event.pointerType === "touch") {
      return;
    }

    const submenu = getHoveredSubmenu(drawer, event.target);

    if (submenu === null) {
      hideIndicator();
      return;
    }

    scheduleIndicatorPosition(submenu, event.clientY);
  };

  // Resets the indicator when the drawer structure changes after open/close actions.
  const handleDrawerMutation = () => {
    hideIndicator();
  };

  drawer.addEventListener("pointermove", handlePointerMove, { passive: true });
  drawer.addEventListener("pointerleave", hideIndicator);

  const observer = new MutationObserver(handleDrawerMutation);

  observer.observe(drawer, { childList: true, subtree: true });
};

// Scans a root node for drawers and initializes any new ones that appear.
const observeDrawers = (root) => {
  if (!isElement(root) && root !== document) {
    return;
  }

  if (isElement(root) && root.matches(DRAWER_SELECTOR)) {
    initializeDrawer(root);
  }

  root.querySelectorAll(DRAWER_SELECTOR).forEach((drawer) => {
    initializeDrawer(drawer);
  });
};

// Boots the feature on load and watches for drawers added later in the DOM.
const initNavHoverIndicator = () => {
  if (!isHoverCapable()) {
    return;
  }

  observeDrawers(document);

  const bodyObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!isElement(node)) {
          return;
        }

        observeDrawers(node);
      });
    });
  });

  bodyObserver.observe(document.body, { childList: true, subtree: true });
};

export default initNavHoverIndicator;
