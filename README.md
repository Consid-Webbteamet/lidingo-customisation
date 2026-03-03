# Lidingo Customisation

WordPress-plugin for CSS/JS overrides in Municipio. Assets are built with Vite and loaded after Municipio custom code output.

## Development

```bash
npm install
npm run build
composer dump-autoload
```

## Behavior

- Frontend CSS output: `wp_head` priority `1001`
- Frontend JS output: `wp_footer` priority `1001` with `defer`
- Admin assets are disabled by default and can be enabled with filter:

```php
add_filter('lidingo_customisation/should_load_admin', '__return_true');
```

## Filters

- `lidingo_customisation/should_load_frontend` (default `true`)
- `lidingo_customisation/should_load_admin` (default `false`)

## Workflow

- Write new CSS/JS in this plugin repository.
- Build with Vite for release/deploy.
- Keep Municipio theme custom CSS/JS unchanged.
