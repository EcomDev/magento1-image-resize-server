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
use React\Stream\ReadableStreamInterface;

class ReactFileAdapterTest extends TestCase implements FileFinderObserver, FileReaderObserver
{

    /** @var LoopInterface */
    private $loop;

    /** @var boolean[] */
    private $fileResults = [];

    /** @var TestDirectory */
    private $tmpDir;

    /** @var ReactFileAdapterFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->loop = $this->createUntilResultsEmptyLoop();
        $this->tmpDir = TestDirectory::create();
        $this->tmpDir->copyFrom(__DIR__ . '/fixtures');
        $this->factory = ReactFileAdapterFactory::createFromLoop($this->loop);
    }

    /**
     * @test
     * @testWith ["images/landscape.jpg"]
     *           ["images/portrait.jpg"]
     *           ["images/square.jpg"]
     */
    public function reportsFoundFile(string $path)
    {
        $file = $this->tmpDir->resolvePath($path);

        $fileFinder = $this->factory->createFinder();
        $fileFinder->findFile($file, $this);

        $this->assertFileResult($file, true);
    }

    /**
     * @test
     */
    public function createsMissingContainingDirectoryWhenFileDoesNotExists()
    {
        $filePath = $this->tmpDir->resolvePath('images/cache/1/file.jpg');
        $directory = $this->tmpDir->resolvePath('images/cache/1');
        $fileFinder = $this->factory->createFinder();
        $fileFinder->findFile($filePath, $this);
        $this->loop->run();

        $this->assertDirectoryExists($directory);
    }

    /** @test */
    public function reportsNotFoundFile()
    {
        $file = $this->tmpDir->resolvePath('image/unknown.jpg');
        $fileFinder = $this->factory->createFinder();
        $fileFinder->findFile($file, $this);

        $this->assertFileResult($file, false);
    }

    /**
     * @test
     * @testWith ["images/landscape.jpg", 75200]
     *           ["images/portrait.jpg", 63776]
     *           ["images/square.jpg", 82916]
     */
    public function opensFileForRead(string $path, int $expectedSize)
    {
        $file = $this->tmpDir->resolvePath($path);
        $fileFinder = $this->factory->createReader();
        $fileFinder->readFile($file, $this);

        $this->assertFileResultWithAssertion(
            $file,
            function ($info) use ($expectedSize) {
                list($size, $fileStream) = $info;
                $this->assertEquals($expectedSize, $size);
                $this->assertInstanceOf(ReadableStreamInterface::class, $fileStream);
            }
        );
    }

    /**
     * @test
     */
    public function reportsNotReadableFile()
    {
        $file = $this->tmpDir->resolvePath('image/unknown.jpg');
        $fileFinder = $this->factory->createReader();
        $fileFinder->readFile($file, $this);

        $this->assertFileResult($file, false);
    }

    private function assertFileResult(string $filePath, $expectedResult)
    {
        $this->assertFileResultWithAssertion($filePath, function ($value) use ($expectedResult) {
            $this->assertEquals($expectedResult, $value);
        });
    }

    private function assertFileResultWithAssertion(string $filePath, callable $assertion)
    {
        $this->loop->run();

        $this->assertArrayHasKey($filePath, $this->fileResults);

        $assertion($this->fileResults[$filePath]);
    }

    private function createUntilResultsEmptyLoop()
    {
        return LoopFactory::create()
            ->createConditionRunLoop(function () {
                return !empty($this->fileResults);
            });
    }

    public function handleFoundFile(FileFinder $finder, string $fileName): void
    {
        $this->fileResults[$fileName] = true;
    }

    public function handleMissingFile(FileFinder $finder, string $fileName): void
    {
        $this->fileResults[$fileName] = false;
    }


    /**
     * Handle open file for read
     *s
     * @param resource $readStream
     */
    public function handleFileRead(string $fileName, int $size, $readStream): void
    {
        $this->fileResults[$fileName] = [$size, $readStream];
    }

    /**
     * Handle failed to read file
     *
     */
    public function handleFileReadError(string $fileName): void
    {
        $this->fileResults[$fileName] = false;
    }
}
