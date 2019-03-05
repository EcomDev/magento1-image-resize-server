<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use PHPUnit\Framework\TestCase;

class InMemoryFileFinderTest extends TestCase implements FileFinderObserver
{
    /** @var InMemoryFileFinder */
    private $finder;

    /** @var boolean[] */
    private $fileResults = [];

    protected function setUp(): void
    {
        $this->finder = new InMemoryFileFinder();
    }

    /** @test */
    public function reportsFileIsFoundInFinderWhenItemIsAddedToFileFinder()
    {

        $this->finder->withFile('file1.txt')
            ->findFile('file1.txt', $this);

        $this->assertFileResult('file1.txt', true);
    }

    /** @test */
    public function reportsFileIsNotFoundInFinderWhenItemIsNot()
    {

        $this->finder->withFile('file1.txt')
            ->findFile('file2.txt', $this);

        $this->assertFileResult('file2.txt', false);
    }

    /** @test */
    public function reportsFileIsNotFoundAfterItWasRemovedFromFileList()
    {
        $this->finder->withFile('file3.txt')
            ->withoutFile('file3.txt')
            ->findFile('file3.txt', $this);

        $this->assertFileResult('file3.txt', false);
    }


    private function assertFileResult(string $filePath, $expectedResult)
    {
        $this->assertEquals([$filePath => $expectedResult], $this->fileResults);
    }


    public function handleFoundFile(FileFinder $finder, string $fileName): void
    {
        $this->fileResults[$fileName] = true;
    }

    public function handleMissingFile(FileFinder $finder, string $fileName): void
    {
        $this->fileResults[$fileName] = false;
    }


}
