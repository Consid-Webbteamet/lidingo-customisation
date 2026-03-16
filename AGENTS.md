# AGENTS.md

## Scope
- This repo is the source repo for `lidingo-customisation`.
- Use this package for project-specific CSS, JS, and small PHP overrides for Lidingo.
- Treat `dist/` as build output only.

## Source of truth
- `packages/lidingo-customisation/` is the canonical source for this plugin.
- `wp-content/plugins/lidingo-customisation/` is only the installed copy.
- Do not treat files in `wp-content/plugins/` as the editable source of truth.

## When to work here
- Use this repo for local presentation tweaks and project-bound frontend behavior.
- Use this repo when the change is intentionally specific to Lidingo and should not be pushed into a shared standalone plugin.

## When to sync elsewhere
- If an override in this repo fixes shared behavior in a standalone plugin, mirror the change back to that plugin's source repo under `packages/`.
- For `modularity-navigation-card`, use `packages/modularity-navigation-card/` as the source repo for lasting module behavior.
- Do not leave shared module logic only in `lidingo-customisation` unless the task explicitly requires a temporary local override.

## Development workflow
- Main frontend entrypoints are `source/js/main.js` and `source/sass/style.scss`.
- Admin entrypoints are `source/js/admin.js` and `source/sass/admin.scss`.
- Build with `npm run build`.
- Use `npm run dev` for Vite-driven local development.
- Use `npm run watch` for continuous production-build output without dev server/HMR.
- Keep local development on HTTP when using the Vite dev server.
- Use a mobile-first approach when working with responsive styles and breakpoints.
- Reuse breakpoint variables from `source/sass/abstracts/_variables.scss` instead of hardcoded breakpoint values in Sass.

## Delivery workflow
- `municipio-deployment` is the consuming repo where this plugin is required, installed, and loaded through Composer.
- Commit changes in this repo when the work belongs here.
- If shared plugin code was changed in a package repo under `packages/`, commit there first, then update the deployment repo as needed.
