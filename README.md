# Lidingo Customisation

WordPress-plugin for CSS/JS overrides in Municipio. Assets are built with Vite and loaded after Municipio custom code output.

## Development

```bash
npm install
npm run dev
npm run build
composer dump-autoload
```

## Behavior

- Frontend CSS output: `wp_head` priority `1001`
- Frontend JS output: `wp_footer` priority `1001` with `defer`
- Vite dev server is used automatically in `WP_ENV=development` when reachable
- Dev-only CSP domains are appended through `WpSecurity/Csp` for:
  - `script-src`: `localhost:5173`
  - `style-src`: `localhost:5173`
  - `connect-src`: `localhost:5173` and `ws://localhost:5173`
- Dev HTTP-mode CSP override removes `upgrade-insecure-requests` and `block-all-mixed-content` when:
  - Vite dev server is active, or
  - local development runs in `WP_ENV=development` with `home_url()` on `http://`
- `Website/HTML/output` CSP hook is intentionally argumentless because earlier callbacks in the filter chain may return `null`
- Admin assets are disabled by default and can be enabled with filter:

```php
add_filter('lidingo_customisation/should_load_admin', '__return_true');
```

## Filters

- `lidingo_customisation/should_load_frontend` (default `true`)
- `lidingo_customisation/should_load_admin` (default `false`)
- `lidingo_customisation/article_post_types` (default `post`, `news`, `nyheter`)
  - Add a new singular post type here if it should use the shared article layout.

```php
add_filter('lidingo_customisation/article_post_types', function (array $postTypes): array {
    $postTypes[] = 'pressrelease';

    return array_values(array_unique($postTypes));
});
```

## Workflow

- Write new CSS/JS in this plugin repository.
- Start Vite with `npm run dev` (default: `http://localhost:5173`).
- Open the WordPress site over HTTP in local development (`http://municipio-deployment.test`).
- If the site is opened over HTTPS while Vite runs over HTTP, the browser may block assets (mixed content).
- Build with Vite for release/deploy.
- Keep Municipio theme custom CSS/JS unchanged.

## Local HTTP mode

- Local development is expected to run in HTTP mode end-to-end.
- Keep the site URL as `http://municipio-deployment.test` when using Vite on `http://localhost:5173`.
- If `force-ssl` is network-activated locally, disable it locally so WordPress does not rewrite theme/plugin assets to `https://` while browsing over `http://`.
- In local HTTP development mode (`WP_ENV=development` + `home_url()` with `http://`), this plugin strips CSP directives `upgrade-insecure-requests` and `block-all-mixed-content` even when Vite dev server is off.

## Dev vs build

- `npm run dev`:
  - serves assets from Vite dev server
  - injects `@vite/client` and enables HMR
  - requires local CORS/CSP setup (already handled in this plugin)
- `npm run build`:
  - writes production assets to `dist/`
  - does not use the Vite dev server
  - is used by deployment/build pipelines
- When the dev server is not reachable, the plugin falls back to `dist/` assets.

## Push and deploy flow

- Work in this repository: `/Users/jonasnasman/municipio-plugins/lidingo-customisation`.
- Commit and push to `Consid-Webbteamet/lidingo-customisation` (`dev` branch).
- In the WordPress deployment repository, run:
  - `composer update consid-webbteamet/lidingo-customisation`
- Commit updated `composer.lock` in the deployment repository.
- Production deploy runs `composer install`, which installs the exact commit reference from `composer.lock`.

## Build artifacts

- `dist/` is gitignored in this repository and should not be committed here.
- Deployment packaging is expected to run root `build.php`, which runs child plugin `build.php` and builds `dist/` during CI/CD.
