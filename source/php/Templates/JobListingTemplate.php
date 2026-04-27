<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

class JobListingTemplate
{
    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    /** Register job listing view hooks. */
    public function addHooks(): void
    {
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 20);
    }

    /** Prepend the local view path when rendering a single job listing. */
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

    /** Enable helper navigation before content on job listing singles. */
    public function customizeViewData(array $viewData): array
    {
        if (!is_singular('job-listing')) {
            return $viewData;
        }

        $viewData['helperNavBeforeContent'] = true;

        return $viewData;
    }
}
