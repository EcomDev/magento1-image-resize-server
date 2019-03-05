<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

interface ResizeQueue
{
    public function queue(
        string $source,
        string $target,
        int $width,
        int $height,
        ResizeQueueObserver $observer
    ): void;
}
