<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use DateTimeImmutable;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

class ImageMagicReactResizeService implements ResizeService, FileFinderObserver
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

    /**
     * @var FileFinder
     */
    private $fileFinder;

    /** @var ResizeServiceObserver[][] */
    private $failedFileList = [];

    public function __construct(
        ReactChildProcessBuilder $processBuilder,
        LoopInterface $loop,
        int $concurrency,
        FileFinder $fileFinder
    ) {
        $this->processBuilder = $processBuilder;
        $this->loop = $loop;
        $this->concurrency = $concurrency;
        $this->fileFinder = $fileFinder;
    }

    public function resize(string $source, array $variations, ResizeServiceObserver $observer): void
    {
         $this->resizeList[] = [$source, $variations, $observer];
    }

    public function __invoke()
    {
        if (count($this->processes) >= $this->concurrency) {
            $this->cleanUp();
        }

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
    private function startProcess(ReactChildProcessBuilder $builder, array $observers): void
    {
        $process = $builder->build();
        $processId = spl_object_hash($process);

        $startTime = new DateTimeImmutable();

        $this->processes[$processId] = [$startTime, $process];
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
                    $this->failedFileList[$target][] = $observer;
                    $this->fileFinder->findFile($target, $this);
                }
            }
        );
    }

    private function cleanUp()
    {
        $currentTime = new DateTimeImmutable();

        foreach ($this->processes as $processId => list($startTime, $process)) {
            if (($currentTime->getTimestamp() - $startTime->getTimestamp()) > 4) {
                $process->terminate();
            }
        }
    }

    public function handleFoundFile(FileFinder $finder, string $fileName): void
    {
        $observers = $this->failedFileList[$fileName] ?? [];
        foreach ($observers as $observer) {
            $observer->handleResizeComplete($fileName);
        }

        unset($this->failedFileList[$fileName]);
    }

    public function handleMissingFile(FileFinder $finder, string $fileName): void
    {
        $observers = $this->failedFileList[$fileName] ?? [];

        foreach ($observers as $observer) {
            $observer->handleResizeFailed($fileName);
        }

        unset($this->failedFileList[$fileName]);
    }
}
