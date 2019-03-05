<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use PHPUnit\Framework\TestCase;

class BackgroundResizeQueueTest extends TestCase implements ResizeQueueObserver
{
    /** @var FakeResizeService */
    private $resizeService;

    /** @var BackgroundResizeQueue */
    private $resizeQueue;

    /** @var array */
    private $actions = [];

    protected function setUp(): void
    {
        $this->resizeService = new FakeResizeService();
        $this->resizeQueue = new BackgroundResizeQueue($this->resizeService);
    }

    /** @test */
    public function doesNotExecuteResizeCommandsInForeground()
    {
        $this->resizeQueue->queue('image1.jpg', 'image1-small.jpg', 150, 150, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 150, $this);
        $this->assertEquals([], $this->resizeService->listPendingCommands());
    }

    /** @test */
    public function addsCommandsIntoResizeServiceOnBackgroundJobExecution()
    {
        $this->resizeQueue->queue('image1.jpg', 'image1-small.jpg', 150, 150, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 150, $this);
        ($this->resizeQueue)();

        $this->assertEquals(
            [
                ['image1.jpg', ['image1-small.jpg' => [150, 150]], $this->resizeQueue],
                ['image2.jpg', ['image2-small.jpg' => [150, 150]], $this->resizeQueue],
            ],
            $this->resizeService->listPendingCommands()
        );
    }

    /** @test */
    public function doesNotAddCommandSecondTimeBackgroundJobIsInvokedCommandIntoResizeServiceOnBackgroundJobExecution()
    {
        $this->resizeQueue->queue('image1.jpg', 'image1-small.jpg', 150, 150, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 150, $this);
        ($this->resizeQueue)();
        ($this->resizeQueue)();

        $this->assertEquals(
            [
                ['image1.jpg', ['image1-small.jpg' => [150, 150]], $this->resizeQueue],
                ['image2.jpg', ['image2-small.jpg' => [150, 150]], $this->resizeQueue],
            ],
            $this->resizeService->listPendingCommands()
        );
    }

    /** @test */
    public function groupsSameSourceIntoSingleResizeCommand()
    {
        $this->resizeQueue->queue('image1.jpg', 'image1-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-medium.jpg', 250, 200, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-big.jpg', 450, 400, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-medium.jpg', 250, 200, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->assertEquals(
            [
                [
                    'image1.jpg',
                    [
                        'image1-small.jpg' => [150, 100],
                        'image1-medium.jpg' => [250, 200],
                        'image1-big.jpg' => [450, 400],
                    ],
                    $this->resizeQueue
                ],
                [
                    'image2.jpg',
                    [
                        'image2-small.jpg' => [150, 100],
                        'image2-medium.jpg' => [250, 200],
                        'image2-big.jpg' => [450, 400],
                    ],
                    $this->resizeQueue
                ],
            ],
            $this->resizeService->listPendingCommands()
        );
    }

    /** @test */
    public function notifiesOfCompletedResizeOperationsForEveryTarget()
    {
        $this->resizeQueue->queue('image1.jpg', 'image1-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-medium.jpg', 250, 200, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->resizeService->completeItem();

        $this->assertActions(
            ['complete', 'image1-small.jpg', 'image1.jpg'],
            ['complete', 'image1-medium.jpg', 'image1.jpg'],
            ['complete', 'image1-big.jpg', 'image1.jpg']
        );
    }

    /** @test */
    public function notifiesOfFailedResizeOperationsForEveryTarget()
    {
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-medium.jpg', 250, 200, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->resizeService->failItem();

        $this->assertActions(
            ['error', 'image2-small.jpg', 'image2.jpg'],
            ['error', 'image2-medium.jpg', 'image2.jpg'],
            ['error', 'image2-big.jpg', 'image2.jpg']
        );
    }
    
    /** @test */
    public function skipsQueueingOfNewCommandWhenTargetResizeIsInProgress()
    {
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->assertEquals(
            [
                [
                    'image2.jpg',
                    [
                        'image2-small.jpg' => [150, 100],
                        'image2-big.jpg' => [450, 400],
                    ],
                    $this->resizeQueue
                ],
            ],
            $this->resizeService->listPendingCommands()
        );
    }
    
    /** @test */
    public function notifiesSkippedQueueItemWhenResizeIsComplete()
    {
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->resizeService->completeItem();

        $this->assertActions(
            ['complete', 'image2-small.jpg', 'image2.jpg'],
            ['complete', 'image2-big.jpg', 'image2.jpg'],
            ['complete', 'image2-big.jpg', 'image2.jpg']
        );
    }

    /** @test */
    public function notifiesSkippedQueueItemWhenResizeHasFailed()
    {
        $this->resizeQueue->queue('image2.jpg', 'image2-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();
        $this->resizeQueue->queue('image2.jpg', 'image2-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->resizeService->failItem();

        $this->assertActions(
            ['error', 'image2-small.jpg', 'image2.jpg'],
            ['error', 'image2-big.jpg', 'image2.jpg'],
            ['error', 'image2-big.jpg', 'image2.jpg']
        );
    }
    
    /** @test */
    public function notifiesOfCompletedActionOnlyOncePerCycle()
    {
        $this->resizeQueue->queue('image1.jpg', 'image1-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-medium.jpg', 250, 200, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->resizeService->completeItem();

        $this->resizeService->resize(
            'image1.jpg',
            [
                'image1-small.jpg' => [150, 100],
                'image1-medium.jpg' => [250, 200],
                'image1-big.jpg' => [450, 400],
            ],
            $this->resizeQueue
        );

        $this->resizeService->completeItem();

        $this->assertActions(
            ['complete', 'image1-small.jpg', 'image1.jpg'],
            ['complete', 'image1-medium.jpg', 'image1.jpg'],
            ['complete', 'image1-big.jpg', 'image1.jpg']
        );
    }

    /** @test */
    public function notifiesOfFailedActionOnlyOncePerCycle()
    {
        $this->resizeQueue->queue('image1.jpg', 'image1-small.jpg', 150, 100, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-medium.jpg', 250, 200, $this);
        $this->resizeQueue->queue('image1.jpg', 'image1-big.jpg', 450, 400, $this);
        ($this->resizeQueue)();

        $this->resizeService->failItem();

        $this->resizeService->resize(
            'image1.jpg',
            [
                'image1-small.jpg' => [150, 100],
                'image1-medium.jpg' => [250, 200],
                'image1-big.jpg' => [450, 400],
            ],
            $this->resizeQueue
        );

        $this->resizeService->failItem();

        $this->assertActions(
            ['error', 'image1-small.jpg', 'image1.jpg'],
            ['error', 'image1-medium.jpg', 'image1.jpg'],
            ['error', 'image1-big.jpg', 'image1.jpg']
        );
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
