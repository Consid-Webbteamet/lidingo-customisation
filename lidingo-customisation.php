<?php

declare(strict_types=1);

/**
 * Plugin Name:       Lidingo Customisation
 * Description:       Custom CSS/JS overrides for Municipio using Vite.
 * Version:           1.0.0
 * Author:            Consid Webbteamet
 * Text Domain:       lidingo-customisation
 * Domain Path:       /languages
 */

namespace LidingoCustomisation;

if (!defined('ABSPATH')) {
    exit;
}

define('LIDINGO_CUSTOMISATION_PATH', plugin_dir_path(__FILE__));
define('LIDINGO_CUSTOMISATION_URL', plugin_dir_url(__FILE__));
define('LIDINGO_CUSTOMISATION_VERSION', '1.0.0');

$autoload = LIDINGO_CUSTOMISATION_PATH . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

add_action('plugins_loaded', static function (): void {
    if (!class_exists(App::class)) {
        return;
    }

    new App();
});
