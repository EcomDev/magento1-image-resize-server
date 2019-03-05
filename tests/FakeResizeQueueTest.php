<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use PHPUnit\Framework\TestCase;

class FakeResizeQueueTest extends TestCase implements ResizeQueueObserver
{
    /** @var FakeResizeQueue */
    private $resizeQueue;

    /** @var array */
    private $actions = [];

    protected function setUp(): void
    {
        $this->resizeQueue = new FakeResizeQueue();
    }

    /** @test */
    public function hangsQueuedItemWhenNotCompleted()
    {
        $this->resizeQueue->queue('image1.jpg', 'cache/image1.jpg', 300, 300, $this);

        $this->assertActions();
    }

    /** @test */
    public function reportsCompletedResize()
    {
        $this->resizeQueue->queue('image1.jpg', 'cache/image1.jpg', 300, 300, $this);
        $this->resizeQueue->complete('cache/image1.jpg');

        $this->assertActions(['complete', 'cache/image1.jpg', 'image1.jpg']);
    }

    /** @test */
    public function reportsFailedResize()
    {
        $this->resizeQueue->queue('image2.jpg', 'cache/image2.jpg', 300, 300, $this);
        $this->resizeQueue->fail('cache/image2.jpg');

        $this->assertActions(['error', 'cache/image2.jpg', 'image2.jpg']);
    }


    public function handleResizeComplete(string $targetPath, string $sourcePath)
    {
        $this->actions[] = ['complete', $targetPath, $sourcePath];
    }

    public function handleResizeError(string $targetPath, string $sourcePath)
    {
        $this->actions[] = ['error', $targetPath, $sourcePath];
    }

    private function assertActions(array... $actions): void
    {
        $this->assertEquals(
            $actions,
            $this->actions
        );
    }
}
