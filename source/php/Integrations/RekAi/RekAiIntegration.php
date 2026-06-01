<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\RekAi;

class RekAiIntegration
{
    /** Rek.ai tracking should run without local blockers. */
    public function addHooks(): void
    {
        // Intentionally left blank. Rek.ai should be allowed to track page views.
    }
}
