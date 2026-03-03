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
- Admin assets are disabled by default and can be enabled with filter:

```php
add_filter('lidingo_customisation/should_load_admin', '__return_true');
```

## Filters

- `lidingo_customisation/should_load_frontend` (default `true`)
- `lidingo_customisation/should_load_admin` (default `false`)

## Workflow

- Write new CSS/JS in this plugin repository.
- Start Vite with `npm run dev` (default: `http://localhost:5173`).
- Open the WordPress site over HTTP in local development (`http://municipio-deployment.test`).
- If the site is opened over HTTPS while Vite runs over HTTP, the browser may block assets (mixed content).
- Build with Vite for release/deploy.
- Keep Municipio theme custom CSS/JS unchanged.
