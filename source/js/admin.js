/**
 * Admin entry for Lidingo Customisation.
 */

if (import.meta.env.DEV) {
    import('../sass/admin.scss');
}

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.add('lidingo-customisation-admin-loaded');
});
