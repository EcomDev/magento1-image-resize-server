<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use React\ChildProcess\Process;

interface ReactChildProcessBuilder
{
    public function withResize(
        string $source,
        string $target,
        int $width,
        int $height
    ): ReactChildProcessBuilder;

    public function build(): Process;

    public function isFull(): bool;
}
