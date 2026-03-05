/**
 * Frontend entry for Lidingo Customisation.
 */

import initScrolledHeaderState from './components/scrolledHeader';

if (import.meta.env.DEV) {
    import('../sass/style.scss');
}

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.add('lidingo-customisation-loaded');
    initScrolledHeaderState();
});
