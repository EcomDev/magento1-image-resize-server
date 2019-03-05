<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class FakeResizeService implements ResizeService
{
    private $commands = [];

    public function resize(string $source, array $variations, ResizeServiceObserver $observer): void
    {
        $this->commands[] = [$source, $variations, $observer];
    }

    public function completeItem(): void
    {
        $this->executeCommand(function ($file, ResizeServiceObserver $observer) {
            $observer->handleResizeComplete($file);
        });
    }

    public function listPendingCommands(): array
    {
        return $this->commands;
    }

    public function failItem()
    {
        $this->executeCommand(function ($file, ResizeServiceObserver $observer) {
            $observer->handleResizeFailed($file);
        });
    }

    private function executeCommand(callable $action)
    {
        if (!$this->commands) {
            return;
        }

        /** @var $observer ResizeServiceObserver */
        list(, $variations, $observer) = array_shift($this->commands);
        foreach (array_keys($variations) as $file) {
            $action($file, $observer);
        }
    }
}
