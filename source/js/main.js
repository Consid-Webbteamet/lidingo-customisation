/**
 * Frontend entry for Lidingo Customisation.
 */

import initNavCurrentBadge from './components/navCurrentBadge';
import initDateFieldPicker from './components/dateFieldPicker';
import initNavHoverIndicator from './components/navHoverIndicator';
import initNavDrawerFocus from './components/navDrawerFocus';
import initHeaderFocusOrder from './components/headerFocusOrder';
import initNavigationCard from './components/navigationCard';
import initScrolledHeaderState from './components/scrolledHeader';
import initModularityTocDeduplicate from './components/modularityTocDeduplicate';
import initModularityTocOffset from './components/modularityTocOffset';
import initExternalLinks from './components/externalLinks';
import initAccordionHashGuard from './components/accordionHashGuard';

if (import.meta.env.DEV) {
    import('../sass/style.scss');
}

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.add('lidingo-customisation-loaded');
    initDateFieldPicker();
    initNavCurrentBadge();
    initNavHoverIndicator();
    initNavDrawerFocus();
    initHeaderFocusOrder();
    initNavigationCard();
    initScrolledHeaderState();
    initModularityTocDeduplicate();
    initModularityTocOffset();
    initExternalLinks();
    initAccordionHashGuard();
});
