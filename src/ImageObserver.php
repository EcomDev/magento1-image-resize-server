<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


interface ImageObserver
{
    public function handleImageDelivery(Image $image, string $filePath);

    public function handleMissingImage(Image $image, string $filePath);

    public function handleImageResize(Image $image);
}
