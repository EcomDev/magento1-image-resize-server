<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class ImageFactory
{
    public function create(string $path, string $source, int $width, int $height): Image
    {
        return new Image($path, $source, $width, $height);
    }
}
