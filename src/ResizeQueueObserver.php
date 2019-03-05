<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

interface ResizeQueueObserver
{
    public function handleResizeComplete(string $targetPath, string $sourcePath);

    public function handleResizeError(string $targetPath, string $sourcePath);
}
