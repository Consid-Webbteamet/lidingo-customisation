/**
 * Frontend entry for Lidingo Customisation.
 */

import initNavCurrentBadge from './components/navCurrentBadge';
import initNavHoverIndicator from './components/navHoverIndicator';
import initScrolledHeaderState from './components/scrolledHeader';

if (import.meta.env.DEV) {
    import('../sass/style.scss');
}

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.add('lidingo-customisation-loaded');
    initNavCurrentBadge();
    initScrolledHeaderState();
    initNavHoverIndicator();
});
