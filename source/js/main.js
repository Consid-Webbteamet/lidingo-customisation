/**
 * Frontend entry for Lidingo Customisation.
 */

import initNavCurrentBadge from './components/navCurrentBadge';
import initNavigationCard from './components/navigationCard';
import initScrolledHeaderState from './components/scrolledHeader';
import initModularityTocDeduplicate from './components/modularityTocDeduplicate';
import initModularityTocOffset from './components/modularityTocOffset';

if (import.meta.env.DEV) {
    import('../sass/style.scss');
}

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.add('lidingo-customisation-loaded');
    initNavCurrentBadge();
    initNavigationCard();
    initScrolledHeaderState();
    initModularityTocDeduplicate();
    initModularityTocOffset();
});
