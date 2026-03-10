// Add a single "Du är här" badge to the current page item in the mobile drawer nav.
// The script matches the current URL against nav links, prefers the deepest match,
// and keeps the badge in sync when async-loaded drawer content is added.
const DRAWER_SELECTOR = ".c-drawer";
const ITEM_SELECTOR = ".c-nav__item";
const OWN_LINK_SELECTOR = ":scope > .c-nav__item-wrapper > .c-nav__link";
const OWN_TEXT_SELECTOR = ":scope > .c-nav__item-wrapper .c-nav__text";
const LABEL_COPY_CLASS = "c-nav__label-copy";
const BADGE_CLASS = "c-nav__current-badge";
const ACTIVE_BADGE_ITEM_CLASS = "c-nav__item--current-page-badge";
const DRAWER_INITIALIZED_ATTRIBUTE =
  "data-lidingo-nav-current-badge-initialized";

// Normalize pathnames so URL matching is stable across trailing slashes.
const normalizePathname = (value) => {
  if (typeof value !== "string") {
    return "";
  }

  const trimmedValue = value.trim();

  if (trimmedValue === "") {
    return "";
  }

  const normalizedValue = trimmedValue.replace(/\/+$/, "");

  return normalizedValue === "" ? "/" : normalizedValue;
};

// Read and normalize the pathname from a link element.
const getLinkPathname = (link) => {
  if (!(link instanceof HTMLAnchorElement)) {
    return "";
  }

  return normalizePathname(link.pathname);
};

// Get the direct link element for a nav item.
const getOwnLink = (item) => item.querySelector(OWN_LINK_SELECTOR);

// Get the direct text element used for a nav item label.
const getOwnTextElement = (item) => item.querySelector(OWN_TEXT_SELECTOR);

// Resolve the normalized pathname for a nav item's own link.
const getOwnLinkPathname = (item) => getLinkPathname(getOwnLink(item));

// Read the nav depth from data attributes or fallback depth classes.
const getItemDepth = (item) => {
  const depthAttribute = item.getAttribute("data-depth");

  if (depthAttribute !== null) {
    const parsedDepth = Number.parseInt(depthAttribute, 10);

    if (!Number.isNaN(parsedDepth)) {
      return parsedDepth;
    }
  }

  const depthClassMatch = item.className.match(/c-nav__item--depth-(\d+)/);

  if (depthClassMatch !== null) {
    return Number.parseInt(depthClassMatch[1], 10);
  }

  return 0;
};

// Create the badge element shown next to the current page label.
const createBadge = () => {
  const badge = document.createElement("span");
  badge.className = BADGE_CLASS;
  badge.setAttribute("aria-hidden", "true");
  badge.textContent = "Du är här";

  return badge;
};

// Restore the original text structure by removing the label wrapper.
const unwrapLabelCopy = (textElement) => {
  const labelCopy = textElement.querySelector(`:scope > .${LABEL_COPY_CLASS}`);

  if (!(labelCopy instanceof HTMLElement)) {
    return;
  }

  while (labelCopy.firstChild) {
    textElement.insertBefore(labelCopy.firstChild, labelCopy);
  }

  labelCopy.remove();
};

// Remove badge-related classes and markup from a nav item.
const cleanupItem = (item) => {
  const textElement = getOwnTextElement(item);

  item.classList.remove(ACTIVE_BADGE_ITEM_CLASS);

  if (!(textElement instanceof HTMLElement)) {
    return;
  }

  const badge = textElement.querySelector(`:scope > .${BADGE_CLASS}`);
  const labelCopy = textElement.querySelector(`:scope > .${LABEL_COPY_CLASS}`);

  if (!(badge instanceof HTMLElement) && !(labelCopy instanceof HTMLElement)) {
    return;
  }

  badge?.remove();
  unwrapLabelCopy(textElement);
};

// Add badge-related classes and markup to the selected nav item.
const decorateItem = (item) => {
  const textElement = getOwnTextElement(item);

  if (!(textElement instanceof HTMLElement)) {
    return;
  }

  item.classList.add(ACTIVE_BADGE_ITEM_CLASS);

  if (
    !(
      textElement.querySelector(`:scope > .${LABEL_COPY_CLASS}`) instanceof
      HTMLElement
    )
  ) {
    const labelCopy = document.createElement("span");
    labelCopy.className = LABEL_COPY_CLASS;

    while (textElement.firstChild) {
      labelCopy.appendChild(textElement.firstChild);
    }

    textElement.appendChild(labelCopy);
  }

  if (
    !(
      textElement.querySelector(`:scope > .${BADGE_CLASS}`) instanceof
      HTMLElement
    )
  ) {
    textElement.appendChild(createBadge());
  }
};

// Find all items in a drawer whose link exactly matches the current page URL.
const getMatchingItems = (drawer, currentPathname) => {
  if (currentPathname === "") {
    return [];
  }

  return Array.from(drawer.querySelectorAll(ITEM_SELECTOR)).filter(
    (item) =>
      item instanceof HTMLElement &&
      getOwnLinkPathname(item) === currentPathname,
  );
};

// Pick the best match by preferring the deepest item and then the last match.
const getWinningItem = (drawer, currentPathname) => {
  const matchingItems = getMatchingItems(drawer, currentPathname);

  if (matchingItems.length === 0) {
    return null;
  }

  return matchingItems.reduce((winningItem, item) => {
    if (!(winningItem instanceof HTMLElement)) {
      return item;
    }

    return getItemDepth(item) >= getItemDepth(winningItem) ? item : winningItem;
  }, null);
};

// Keep exactly one current-page badge in the drawer.
const updateDrawer = (drawer, currentPathname) => {
  const winningItem = getWinningItem(drawer, currentPathname);

  drawer.querySelectorAll(ITEM_SELECTOR).forEach((item) => {
    if (item instanceof HTMLElement && item !== winningItem) {
      cleanupItem(item);
    }
  });

  if (winningItem instanceof HTMLElement) {
    decorateItem(winningItem);
  }
};

// Observe a drawer so async-loaded nav levels can be updated automatically.
const observeDrawer = (drawer, currentPathname) => {
  if (
    !(drawer instanceof HTMLElement) ||
    drawer.hasAttribute(DRAWER_INITIALIZED_ATTRIBUTE)
  ) {
    return;
  }

  drawer.setAttribute(DRAWER_INITIALIZED_ATTRIBUTE, "true");
  updateDrawer(drawer, currentPathname);

  const observer = new MutationObserver(() => {
    updateDrawer(drawer, currentPathname);
  });

  observer.observe(drawer, { childList: true, subtree: true });
};

// Initialize badge handling for existing and dynamically added drawers.
const initNavCurrentBadge = () => {
  if (typeof window === "undefined" || typeof document === "undefined") {
    return;
  }

  const currentPathname = normalizePathname(window.location.pathname);

  document.querySelectorAll(DRAWER_SELECTOR).forEach((drawer) => {
    observeDrawer(drawer, currentPathname);
  });

  const bodyObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) {
          return;
        }

        if (node.matches(DRAWER_SELECTOR)) {
          observeDrawer(node, currentPathname);
        }

        node.querySelectorAll?.(DRAWER_SELECTOR).forEach((drawer) => {
          observeDrawer(drawer, currentPathname);
        });
      });
    });
  });

  bodyObserver.observe(document.body, { childList: true, subtree: true });
};

export default initNavCurrentBadge;
