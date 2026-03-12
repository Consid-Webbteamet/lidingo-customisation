<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

class EventTemplateOverrides
{
    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views/event-integration'
        );
    }

    public function addHooks(): void
    {
        add_action('template_redirect', [$this, 'registerViewPath'], 20);
    }

    public function registerViewPath(): void
    {
        if (!is_singular('event')) {
            return;
        }

        add_filter('Municipio/viewPaths', [$this, 'prependViewPath'], 20);
    }

    public function prependViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            array_unshift($viewPaths, $this->viewPath);
        }

        return $viewPaths;
    }
}
