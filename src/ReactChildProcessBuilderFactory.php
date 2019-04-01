<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


interface ReactChildProcessBuilderFactory
{
    public function create(int $resizeLimit, array $imageOptions = []): ReactChildProcessBuilder;
}
