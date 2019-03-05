<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class BackgroundResizeQueue implements ResizeQueue, ResizeServiceObserver
{
    private $pendingOperations = [];

    /**
     * Resize service
     *
     * @var ResizeService
     */
    private $resizeService;

    /** @var ResizeServiceObserver[] */
    private $observers = [];

    public function __construct(ResizeService $resizeService)
    {
        $this->resizeService = $resizeService;
    }

    public function queue(
        string $source,
        string $target,
        int $width,
        int $height,
        ResizeQueueObserver $observer
    ): void {

        if (isset($this->observers[$target])) {
            $this->observers[$target][] = [$observer, $source];
            return;
        }

        $this->pendingOperations[$source][] = [$target, $width, $height, $observer];
    }

    public function __invoke()
    {
        $pendingOperations = $this->pendingOperations;
        $this->pendingOperations = [];

        foreach ($pendingOperations as $source => $commands) {
            $variations = [];
            foreach ($commands as list($target, $width, $height, $observer)) {
                $variations[$target] = [$width, $height];
                $this->observers[$target][] = [$observer, $source];
            }

            $this->resizeService->resize($source, $variations, $this);
        }
    }

    public function handleResizeComplete(string $target)
    {
        foreach ($this->flushPendingTargetObservers($target) as list($observer, $source)) {
            /** @var ResizeQueueObserver $observer */
            $observer->handleResizeComplete($target, $source);
        }
    }

    public function handleResizeFailed(string $target)
    {
        foreach ($this->flushPendingTargetObservers($target) as list($observer, $source)) {
            /** @var ResizeQueueObserver $observer */
            $observer->handleResizeError($target, $source);
        }
    }

    private function flushPendingTargetObservers(string $target)
    {
        $observers = [];
        if (isset($this->observers[$target])) {
            $observers = $this->observers[$target];
            unset($this->observers[$target]);
        }

        return $observers;
    }
}
