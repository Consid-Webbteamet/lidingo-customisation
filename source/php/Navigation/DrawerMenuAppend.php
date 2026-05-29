<?php

declare(strict_types=1);

namespace LidingoCustomisation\Navigation;

class DrawerMenuAppend
{
    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views/v3'
        );
    }

    /** Register view overrides for the mobile drawer navigation. */
    public function addHooks(): void
    {
        add_filter('Municipio/viewPaths', [$this, 'prependViewPath']);
    }

    /** Prefer local v3 partials when they exist, while keeping Municipio as fallback. */
    public function prependViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            array_unshift($viewPaths, $this->viewPath);
        }

        return $viewPaths;
    }
}
