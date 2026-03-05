<?php

declare(strict_types=1);

namespace LidingoCustomisation\Infrastructure;

class AssetRenderer
{
    private bool $hasPrintedViteClient = false;

    public function __construct(
        private AssetManifest $assetManifest,
        private DevServer $devServer
    ) {
    }

    public function printFrontendStylesheet(): void
    {
        if ($this->devServer->shouldUseDevServer()) {
            return;
        }

        $href = $this->assetManifest->getAssetUrl('source/sass/style.scss', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($href === null) {
            return;
        }

        printf(
            '<link rel="stylesheet" id="lidingo-customisation-style" href="%s" media="all" />' . "\n",
            esc_url($href)
        );
    }

    public function printFrontendScript(): void
    {
        if ($this->devServer->shouldUseDevServer()) {
            $this->printDevModuleScript('source/js/main.js', 'lidingo-customisation-main-js-dev');
            return;
        }

        $src = $this->assetManifest->getAssetUrl('source/js/main.js', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($src === null) {
            return;
        }

        printf(
            '<script id="lidingo-customisation-main-js" src="%s" defer></script>' . "\n",
            esc_url($src)
        );
    }

    public function printAdminStylesheet(): void
    {
        if ($this->devServer->shouldUseDevServer()) {
            return;
        }

        $href = $this->assetManifest->getAssetUrl('source/sass/admin.scss', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($href === null) {
            return;
        }

        printf(
            '<link rel="stylesheet" id="lidingo-customisation-admin-style" href="%s" media="all" />' . "\n",
            esc_url($href)
        );
    }

    public function printAdminScript(): void
    {
        if ($this->devServer->shouldUseDevServer()) {
            $this->printDevModuleScript('source/js/admin.js', 'lidingo-customisation-admin-js-dev');
            return;
        }

        $src = $this->assetManifest->getAssetUrl('source/js/admin.js', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($src === null) {
            return;
        }

        printf(
            '<script id="lidingo-customisation-admin-js" src="%s" defer></script>' . "\n",
            esc_url($src)
        );
    }

    private function printDevModuleScript(string $entryPath, string $id): void
    {
        $this->printViteClientScript();

        printf(
            '<script type="module" id="%s" src="%s"></script>' . "\n",
            esc_attr($id),
            esc_url($this->devServer->getOrigin() . '/' . ltrim($entryPath, '/'))
        );
    }

    private function printViteClientScript(): void
    {
        if ($this->hasPrintedViteClient) {
            return;
        }

        $this->hasPrintedViteClient = true;

        printf(
            '<script type="module" id="lidingo-customisation-vite-client" src="%s"></script>' . "\n",
            esc_url($this->devServer->getOrigin() . '/@vite/client')
        );
    }
}
