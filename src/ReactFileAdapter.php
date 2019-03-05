<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Stream\ReadableStream;
use React\Stream\ReadableStreamInterface;

class ReactFileAdapter implements FileFinder, FileReader
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public static function createFromLoop(LoopInterface $loop): self
    {
        return new self(Filesystem::create($loop));
    }

    public function findFile(string $fileName, FileFinderObserver $observer): void
    {
        $this->filesystem->file($fileName)->exists()->then(
            function () use ($fileName, $observer) {
                $observer->handleFoundFile($this, $fileName);
            },
            function () use ($fileName, $observer) {
                $directory = $this->filesystem->dir(dirname($fileName));
                $reportMissingFile = function () use ($observer, $fileName) {
                    $observer->handleMissingFile($this, $fileName);
                };

                $directory->stat()
                    ->then(
                        $reportMissingFile,
                        function () use ($observer, $fileName, $directory, $reportMissingFile) {
                            $directory->createRecursive()->then($reportMissingFile, $reportMissingFile);
                        }
                    )
                ;
            }
        );
    }

    public function readFile(string $fileName, FileReaderObserver $observer): void
    {
        $file = $this->filesystem->file($fileName);
        $onError = function () use ($fileName, $observer) {
            $observer->handleFileReadError($fileName);
        };

        $file->size()
            ->then(
                function ($size) use ($fileName, $observer, $file, $onError) {
                    $file->open('r')->then(
                        function (ReadableStreamInterface $stream) use ($fileName, $observer, $size) {
                            $observer->handleFileRead($fileName, $size, $stream);
                        },
                        $onError
                    );
                },
                $onError
            );
    }
}
