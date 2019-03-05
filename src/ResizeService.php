<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


interface ResizeService
{
    public function resize(string $source, array $variations, ResizeServiceObserver $observer): void;
}
