<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use EcomDev\ReactTestUtil\LoopFactory;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;

class ImageMagicReactResizeServiceTest extends TestCase implements ResizeServiceObserver
{

    /** @var TestDirectory */
    private $tmpDir;

    /** @var array */
    private $actions = [];

    /** @var int */
    private $minimumActions;

    /** @var LoopInterface */
    private $loop;

    /** @var ImageMagicReactResizeServiceBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->tmpDir = TestDirectory::create();
        $this->tmpDir->copyFrom(__DIR__ . '/fixtures');
        $this->loop = LoopFactory::create()->createConditionRunLoopWithTimeout(
            function () {
                return count($this->actions) === $this->minimumActions;
            },
            12.0
        );

        $this->builder = (new ImageMagicReactResizeServiceBuilder($this->loop, new ImageMagicReactProcessBuilderFactory()))
            ->withBaseDirectory(
                $this->tmpDir->resolvePath('images')
            )
            ->withWorkerImageLimit(1)
            ->withWorkerLimit(2)
        ;
    }

    /**
     * @test
     */
    public function resizeSingleImage()
    {
        $image = $this->tmpDir->resolvePath('images/square.jpg');

        $this->builder->build()->resize(
            $image,
            [
                $this->tmpDir->resolvePath('images/square100x100.jpg') => [100, 100]
            ],
            $this
        );

        $this->runLoop();

        $this->assertImageDimensions('images/square100x100.jpg', 100, 100);
    }

    /**
     * @test
     */
    public function resizesMultipleSourceImageVariations()
    {
        $image = $this->tmpDir->resolvePath('images/square.jpg');
        $this->builder->build()->resize(
            $image,
            [
                $this->tmpDir->resolvePath('images/square100x100.jpg') => [100, 100],
                $this->tmpDir->resolvePath('images/square150x200.jpg') => [150, 200],
            ],
            $this
        );

        $this->runLoop(2);

        $this->assertImageDimensions('images/square100x100.jpg', 100, 100);
        $this->assertImageDimensions('images/square150x200.jpg', 150, 150);
    }

    /** @test */
    public function notifiesCompletedImageResize()
    {
        $image = $this->tmpDir->resolvePath('images/square.jpg');
        $this->builder->build()->resize(
            $image,
            [
                $this->tmpDir->resolvePath('images/square100x100.jpg') => [100, 100],
                $this->tmpDir->resolvePath('images/square150x200.jpg') => [150, 200],
            ],
            $this
        );

        $this->runLoop(2);

        $this->assertActions(
            ['complete', $this->tmpDir->resolvePath('images/square100x100.jpg')],
            ['complete', $this->tmpDir->resolvePath('images/square150x200.jpg')]
        );
    }

    /** @test */
    public function notifiesFailedImageResize()
    {
        $image = $this->tmpDir->resolvePath('images/square.jpg');
        $this->builder->build()->resize(
            $image,
            [
                $this->tmpDir->resolvePath('images/fail-dir/square100x100.jpg') => [100, 100],
                $this->tmpDir->resolvePath('images/square150x200.jpg') => [150, 200],
            ],
            $this
        );

        $this->runLoop(2);

        $this->assertActions(
            ['failed', $this->tmpDir->resolvePath('images/fail-dir/square100x100.jpg')],
            ['failed', $this->tmpDir->resolvePath('images/square150x200.jpg')]
        );
    }

    /** @test */
    public function limitsExecutionQueueToProcessesPerRun()
    {
        $service = $this->builder->build();

        $service->resize(
            $this->tmpDir->resolvePath('images/square.jpg'),
            [$this->tmpDir->resolvePath('images/square150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/portrait.jpg'),
            [$this->tmpDir->resolvePath('images/portrait150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/landscape.jpg'),
            [$this->tmpDir->resolvePath('images/landscape150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/square.jpg'),
            [$this->tmpDir->resolvePath('images/square100x100.jpg') => [100, 100]],
            $this
        );

        $this->runLoop(2);

        $this->assertActions(
            ['complete', $this->tmpDir->resolvePath('images/square150x200.jpg')],
            ['complete', $this->tmpDir->resolvePath('images/portrait150x200.jpg')]
        );
    }

    /** @test */
    public function executesOperationsWhenProcessLimitIsNotReached()
    {
        $service = $this->builder->withWorkerImageLimit(10)
            ->build();

        $service->resize(
            $this->tmpDir->resolvePath('images/square.jpg'),
            [$this->tmpDir->resolvePath('images/square150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/portrait.jpg'),
            [$this->tmpDir->resolvePath('images/portrait150x200.jpg') => [150, 200]],
            $this
        );

        $this->runLoop(2);

        $this->assertActions(
            ['complete', $this->tmpDir->resolvePath('images/square150x200.jpg')],
            ['complete', $this->tmpDir->resolvePath('images/portrait150x200.jpg')]
        );
    }

    /** @test */
    public function killsHangResizeProcesses()
    {
        $service = (new ImageMagicReactResizeServiceBuilder($this->loop, new HangReactChildProcessBuilderFactoryStub()))
            ->withBaseDirectory(
                $this->tmpDir->resolvePath('images')
            )
            ->withWorkerImageLimit(1)
            ->withWorkerLimit(1)
            ->build();

        $service->resize(
            $this->tmpDir->resolvePath('images/square.jpg'),
            [$this->tmpDir->resolvePath('images/square150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/portrait.jpg'),
            [$this->tmpDir->resolvePath('images/portrait150x200.jpg') => [150, 200]],
            $this
        );

        $this->runLoop(2);

        $this->assertActions(
            ['failed', $this->tmpDir->resolvePath('images/square150x200.jpg')],
            ['failed', $this->tmpDir->resolvePath('images/portrait150x200.jpg')]
        );
    }

    /** @test */
    public function finishesAllResizeGivenEnoughTimeProvided()
    {
        $service = $this->builder->build();

        $service->resize(
            $this->tmpDir->resolvePath('images/square.jpg'),
            [$this->tmpDir->resolvePath('images/square150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/portrait.jpg'),
            [$this->tmpDir->resolvePath('images/portrait150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/landscape.jpg'),
            [$this->tmpDir->resolvePath('images/landscape150x200.jpg') => [150, 200]],
            $this
        );

        $service->resize(
            $this->tmpDir->resolvePath('images/square.jpg'),
            [$this->tmpDir->resolvePath('images/square100x100.jpg') => [100, 100]],
            $this
        );

        $this->runLoop(4);

        $this->assertActions(
            ['complete', $this->tmpDir->resolvePath('images/square150x200.jpg')],
            ['complete', $this->tmpDir->resolvePath('images/portrait150x200.jpg')],
            ['complete', $this->tmpDir->resolvePath('images/landscape150x200.jpg')],
            ['complete', $this->tmpDir->resolvePath('images/square100x100.jpg')]
        );
    }

    private function assertActions(array ...$actions)
    {
        // Sort actions as they are async
        $sort = function ($left, $right) {
            return strcmp($left[1], $right[1]);
        };

        usort($actions, $sort);
        usort($this->actions, $sort);

        $this->assertEquals($actions, $this->actions);
    }

    public function handleResizeComplete(string $target)
    {
        $this->actions[] = ['complete', $target];
    }

    public function handleResizeFailed(string $target)
    {
        $this->actions[] = ['failed', $target];
    }

    private function assertImageDimensions(string $image, int $expectedWidth, int $expectedHeight)
    {
        list($width, $height) = getimagesize($this->tmpDir->resolvePath($image));

        $this->assertEquals(
            [$expectedWidth, $expectedHeight],
            [$width, $height]
        );
    }

    private function runLoop(int $numberOfActions = 1): void
    {
        $this->minimumActions = $numberOfActions;
        $this->loop->run();
    }
}
