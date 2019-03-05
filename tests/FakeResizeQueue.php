<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class FakeResizeQueue implements ResizeQueue
{
    /** @var array  */
    private $queueData = [];

    /** @var ResizeQueueObserver[][] */
    private $queueObservers = [];

    public function queue(
        string $source,
        string $target,
        int $width,
        int $height,
        ResizeQueueObserver $observer
    ): void {
        $this->queueData[$target] = [$source, $width, $height];
        $this->queueObservers[$target][] = $observer;
    }

    public function complete(string $target): void
    {
        if (!isset($this->queueData[$target])) {
            return;
        }
        
        list($source) = $this->queueData[$target];

        foreach ($this->queueObservers[$target] as $observer) {
            $observer->handleResizeComplete($target, $source);
        }

        $this->clearQueueForTarget($target);
    }

    public function fail(string $target): void
    {
        if (!isset($this->queueData[$target])) {
            return;
        }

        list($source) = $this->queueData[$target];

        foreach ($this->queueObservers[$target] as $observer) {
            $observer->handleResizeError($target, $source);
        }

        $this->clearQueueForTarget($target);
    }

    /**
     * @param string $target
     *
     */
    private function clearQueueForTarget(string $target): void
    {
        unset($this->queueObservers[$target], $this->queueData[$target]);
    }

}
