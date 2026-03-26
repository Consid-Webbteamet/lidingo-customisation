<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

class EventPageTemplate
{
    private string $viewPath;
    private string $controllerPath;

    public function __construct(?string $viewPath = null, ?string $controllerPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views/v3'
        );
        $this->controllerPath = $controllerPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/php/Overrides/Municipio/Controller/SingularEvent.php';
    }

    public function addHooks(): void
    {
        spl_autoload_register([$this, 'autoloadSingularEventController'], true, true);
        add_action('after_setup_theme', [$this, 'prioritizeSingularEventControllerAutoloader'], 999);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('Municipio/blade/controller', [$this, 'useSingularEventControllerPath']);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 20);
    }

    public function autoloadSingularEventController(string $className): void
    {
        if (
            $className !== 'Municipio\\Controller\\SingularEvent'
            || !file_exists($this->controllerPath)
        ) {
            return;
        }

        require_once $this->controllerPath;
    }

    public function prioritizeSingularEventControllerAutoloader(): void
    {
        spl_autoload_unregister([$this, 'autoloadSingularEventController']);
        spl_autoload_register([$this, 'autoloadSingularEventController'], true, true);
    }

    public function useSingularEventControllerPath(string $controllerPath): string
    {
        if (basename($controllerPath) !== 'SingularEvent.php' || !file_exists($this->controllerPath)) {
            return $controllerPath;
        }

        return $this->controllerPath;
    }

    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            array_unshift($viewPaths, $this->viewPath);
        }

        return $viewPaths;
    }

    public function customizeViewData(array $viewData): array
    {
        if (!is_singular('evenemang')) {
            return $viewData;
        }

        $viewData['hasSideMenu'] = false;
        $viewData['showSidebars'] = false;
        $viewData['helperNavBeforeContent'] = true;
        $viewData['skipToMainContentLink'] = '#main-content';

        return $viewData;
    }
}
