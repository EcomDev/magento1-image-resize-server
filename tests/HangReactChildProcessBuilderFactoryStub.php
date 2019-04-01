<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use React\ChildProcess\Process;

class HangReactChildProcessBuilderFactoryStub implements ReactChildProcessBuilderFactory
{

    public function create(int $resizeLimit, array $imageOptions = []): ReactChildProcessBuilder
    {
        return new class implements ReactChildProcessBuilder
        {
            public function withResize(string $source, string $target, int $width, int $height): ReactChildProcessBuilder
            {
                return $this;
            }

            public function build(): Process
            {
                return new Process('sleep 1000');
            }

            public function isFull(): bool
            {
                return false;
            }
        };
    }
}
