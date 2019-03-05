<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

class BackgroundJob
{

    private $processId;

    public function __construct(int $processId)
    {
        $this->processId = $processId;
    }

    public static function create(callable $job): self
    {
        $forkedProcessId = \pcntl_fork();

        if ($forkedProcessId === 0) {
            $job();
            exit(0);
        }

        return new self($forkedProcessId);
    }

    public function sendSignal(int $signal)
    {
        \posix_kill($this->processId, $signal);
    }

    public function wait(): int
    {
        $exitStatus = 0;
        \pcntl_waitpid($this->processId, $exitStatus);

        return $exitStatus;
    }
}
