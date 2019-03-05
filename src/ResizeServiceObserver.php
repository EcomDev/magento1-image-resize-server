<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


interface ResizeServiceObserver
{
    public function handleResizeComplete(string $target);

    public function handleResizeFailed(string $target);
}
