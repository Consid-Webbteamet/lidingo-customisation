<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

class JobPostingTemplate
{
    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views/v3'
        );
    }

    public function addHooks(): void
    {
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
    }

    public function addViewPath(array $viewPaths): array
    {
        if (!is_singular('job-listing')) {
            return $viewPaths;
        }

        if (!in_array($this->viewPath, $viewPaths, true)) {
            array_unshift($viewPaths, $this->viewPath);
        }

        return $viewPaths;
    }
}

