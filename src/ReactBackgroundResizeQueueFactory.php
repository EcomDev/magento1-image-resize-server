<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use React\EventLoop\LoopInterface;

class ReactBackgroundResizeQueueFactory
{
    public function createForLoop(LoopInterface $loop, ResizeService $service): ResizeQueue
    {
        $resizeQueue = new BackgroundResizeQueue($service);
        $loop->addPeriodicTimer(0.1, $resizeQueue);

        return $resizeQueue;
    }
}
