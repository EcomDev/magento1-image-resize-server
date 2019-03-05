<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class Image implements FileFinderObserver, ResizeQueueObserver
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $source;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /** @var ImageObserver[] */
    private $observers = [];

    public function __construct(string $path, string $source, int $width, int $height)
    {
        $this->path = $path;
        $this->source = $source;
        $this->width = $width;
        $this->height = $height;
    }

    public function validate(FileFinder $finder, ImageObserver $observer): void
    {
        $this->observers[] = $observer;
        $finder->findFile($this->path, $this);
    }

    public function resize(ResizeQueue $resizeQueue): void
    {
        $resizeQueue->queue(
            $this->source,
            $this->path,
            $this->width,
            $this->height,
            $this
        );
    }

    public function handleFoundFile(FileFinder $finder, string $fileName): void
    {
        if ($fileName === $this->path) {
            $this->notifyImageDelivery();
        } elseif ($fileName === $this->source) {
            $this->requestImageResize();
        }

    }

    public function handleMissingFile(FileFinder $finder, string $fileName): void
    {
        if ($fileName === $this->path) {
            $finder->findFile($this->source, $this);
        } elseif ($fileName === $this->source) {
            $this->notifyMissingImage();
        }
    }

    private function notifyImageDelivery(): void
    {
        $imageObservers = $this->flushObservers();

        foreach ($imageObservers as $observer) {
            $observer->handleImageDelivery($this, $this->path);
        }
    }

    private function notifyMissingImage(): void
    {
        $imageObservers = $this->flushObservers();

        foreach ($imageObservers as $observer) {
            $observer->handleMissingImage($this, $this->source);
        }
    }

    /**
     * @return ImageObserver[]
     */
    private function flushObservers(): array
    {
        $flushedObservers = $this->observers;
        $this->observers = [];

        return $flushedObservers;
    }

    private function requestImageResize(): void
    {
        foreach ($this->observers as $observer) {
            $observer->handleImageResize($this);
        }
    }

    public function handleResizeComplete(string $targetPath, string $sourcePath)
    {
        if ($this->path === $targetPath) {
            $this->notifyImageDelivery();
        }
    }

    public function handleResizeError(string $targetPath, string $sourcePath)
    {
        if ($this->path === $targetPath) {
            $this->notifyMissingImage();
        }
    }
}
