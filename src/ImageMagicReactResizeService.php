<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

class ImageMagicReactResizeService implements ResizeService
{
    private const DEFAULT_CONCURRENCY = 3;

    /**
     * @var ImageMagicReactProcessBuilder
     */
    private $processBuilder;

    /**
     * @var LoopInterface
     */
    private $loop;

    /** @var array */
    private $resizeList = [];

    /** @var Process[]  */
    private $processes = [];

    /**
     * @var int
     */
    private $concurrency;

    public function __construct(ImageMagicReactProcessBuilder $processBuilder, LoopInterface $loop, int $concurrency)
    {
        $this->processBuilder = $processBuilder;
        $this->loop = $loop;
        $this->concurrency = $concurrency;
    }

    public static function createFromLoop(
        LoopInterface $loop,
        ImageMagicReactProcessBuilder $builder = null,
        int $concurrency = self::DEFAULT_CONCURRENCY
    ): self {
        $resizeService = new self(
            $builder ?? ImageMagicReactProcessBuilder::create(),
            $loop,
            $concurrency
        );

        $loop->addPeriodicTimer(0.001, $resizeService);
        return $resizeService;
    }

    public function resize(string $source, array $variations, ResizeServiceObserver $observer): void
    {
         $this->resizeList[] = [$source, $variations, $observer];
    }

    public function __invoke()
    {
        if (!$this->resizeList || count($this->processes) >= $this->concurrency) {
            return;
        }

        $builder = $this->processBuilder;
        $observers = [];


        while ($this->resizeList && count($this->processes) < $this->concurrency) {
            list($source, $variations, $observer) = array_shift($this->resizeList);

            foreach ($variations as $target => list($width, $height)) {
                $observers[$target] = $observer;
                $builder = $builder->withResize($source, $target, $width, $height);
            }

            if ($builder->isFull()) {
                $this->startProcess($builder, $observers);
                $builder = $this->processBuilder;
                $observers = [];
            }
        }

        if ($observers) {
            $this->startProcess($builder, $observers);
        }
    }

    /**
     * @param ResizeServiceObserver[] $observers
     *
     */
    private function startProcess(ImageMagicReactProcessBuilder $builder, array $observers): void
    {
        $process = $builder->build();
        $processId = spl_object_hash($process);

        $this->processes[$processId] = $process;
        $process->start($this->loop);

        $process->on(
            'exit',
            function ($exit) use ($observers, $processId) {
                unset($this->processes[$processId]);

                if ($exit === 0) {
                    foreach ($observers as $target => $observer) {
                        $observer->handleResizeComplete($target);
                    }
                    return;
                }

                foreach ($observers as $target => $observer) {
                    $observer->handleResizeFailed($target);
                }
            }
        );


    }
}
