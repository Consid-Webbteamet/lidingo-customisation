# AGENTS.md

## Scope
- This repo is the source repo for `lidingo-customisation`.
- Use this package for project-specific CSS, JS, and small PHP overrides for Lidingo.
- Treat `dist/` as build output only.

## Source of truth
- `packages/lidingo-customisation/` is the canonical source for this plugin.
- `wp-content/plugins/lidingo-customisation/` is only the installed copy.
- Do not treat files in `wp-content/plugins/` as the editable source of truth.
- Do not manually copy CSS or JS from this repo into `wp-content/plugins/lidingo-customisation/` unless the task explicitly asks for that local sync.
- If the task explicitly asks for a local installed-copy sync, it is allowed to `rsync` this built package into `wp-content/plugins/lidingo-customisation/` for local verification. Keep all real source edits in this repo, and use Composer-based sync for deployment/prod.

## When to work here
- Use this repo for local presentation tweaks and project-bound frontend behavior.
- Use this repo when the change is intentionally specific to Lidingo and should not be pushed into a shared standalone plugin.

## When to sync elsewhere
- If an override in this repo fixes shared behavior in a standalone plugin, mirror the change back to that plugin's source repo under `packages/`.
- For `modularity-navigation-card`, use `packages/modularity-navigation-card/` as the source repo for lasting module behavior.
- Do not leave shared module logic only in `lidingo-customisation` unless the task explicitly requires a temporary local override.

## Development workflow
- Run `npm install` after a fresh checkout or whenever `package.json` changes.
- Main frontend entrypoints are `source/js/main.js` and `source/sass/style.scss`.
- Admin entrypoints are `source/js/admin.js` and `source/sass/admin.scss`.
- Run `composer dump-autoload` after adding or moving PHP classes under `source/php/`.
- Build with `npm run build`.
- Use `php build.php` for package/CI builds; it runs Composer and npm install steps before `npm run build`.
- Use `php build.php --no-composer` when Composer dependencies are already installed, and `php build.php --cleanup` for packaging runs that should strip build-time source files.
- Use `npm run dev` for Vite-driven local development.
- `npm run dev` auto-enables the Vite dev server in `WP_ENV=development` when `http://localhost:5173/@vite/client` is reachable.
- The Vite dev server is pinned to `http://localhost:5173`; keep the origin and HMR settings aligned in `vite.config.js` if you change local tooling.
- Use `npm run watch` for continuous production-build output without dev server/HMR.
- Keep local development on HTTP when using the Vite dev server.
- In local HTTP development mode (`WP_ENV=development` with `home_url()` on `http://`), the plugin strips `upgrade-insecure-requests` and `block-all-mixed-content` CSP directives even when the Vite dev server is off.
- If the Vite dev server is unreachable, the plugin falls back to built `dist/` assets.
- Use a mobile-first approach when working with responsive styles and breakpoints.
- Reuse breakpoint variables from `source/sass/abstracts/_variables.scss` instead of hardcoded breakpoint values in Sass.

## Delivery workflow
- `municipio-deployment` is the consuming repo where this plugin is required, installed, and loaded through Composer.
- Commit changes in this repo when the work belongs here.
- If shared plugin code was changed in a package repo under `packages/`, commit there first, then update the deployment repo as needed.
