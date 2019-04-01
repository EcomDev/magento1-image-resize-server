<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use PHPUnit\Framework\TestCase;
use React\ChildProcess\Process;

class ImageMagicReactProcessBuilderTest extends TestCase
{
    /** @var ImageMagicReactProcessBuilderFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new ImageMagicReactProcessBuilderFactory();
    }

    /** @test */
    public function createsEmptyProcessWhenNothingIsProvided()
    {
        $this->assertEquals(
            new Process('convert null:'),
            $this->factory->create(10)
                ->build()
        );
    }

    /** @test */
    public function resizeCommandFromSingleSource()
    {
        $this->assertEquals(
            new Process(
                'convert \\( original.jpg -colorspace sRGB -resize 300x400 -write original300x400.jpg \\) null:'
            ),
            $this->factory->create(10)
                ->withResize('original.jpg', 'original300x400.jpg', 300, 400)
                ->build()
        );
    }

    /** @test */
    public function resizeCommandFromMultipleSource()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original1.jpg -colorspace sRGB -resize 200x300 -write original1200x300.jpg \\) '
                . '\\( original2.jpg -colorspace sRGB -resize 300x400 -write original2300x400.jpg \\) null:'
            ),
            $this->factory->create(10)
                ->withResize('original1.jpg', 'original1200x300.jpg', 200, 300)
                ->withResize('original2.jpg', 'original2300x400.jpg', 300, 400)
                ->build()
        );
    }

    /** @test */
    public function groupsSameSourceResizeTogether()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original1.jpg -colorspace sRGB -resize 600x700 -write original1-big.jpg '
                    . '-resize 300x400 -write original1-small.jpg '
                    . '-resize 150x150 -write original1-thumb.jpg \\) '
                . '\\( original2.jpg -colorspace sRGB -resize 150x150 -write original2-thumb.jpg \\) null:'
            ),
            $this->factory->create(10)
                ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
                ->withResize('original2.jpg', 'original2-thumb.jpg', 150, 150)
                ->withResize('original1.jpg', 'original1-small.jpg', 300, 400)
                ->withResize('original1.jpg', 'original1-thumb.jpg', 150, 150)
                ->build()
        );
    }

    /** @test */
    public function sortsImageSizesFromBiggerToSmaller()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original3.jpg -colorspace sRGB -resize 600x700 -write original3-big.jpg '
                . '-resize 500x600 -write original3-medium.jpg '
                . '-resize 300x400 -write original3-small.jpg '
                . '-resize 300x400 -write original3-small-second.jpg '
                . '-resize 150x150 -write original3-thumb.jpg \\) '
                . '\\( original2.jpg -colorspace sRGB -resize 600x700 -write original2-big.jpg '
                . '-resize 150x150 -write original2-thumb.jpg \\) null:'
            ),
            $this->factory->create(10)
                ->withResize('original3.jpg', 'original3-big.jpg', 600, 700)
                ->withResize('original3.jpg', 'original3-thumb.jpg', 150, 150)
                ->withResize('original3.jpg', 'original3-small.jpg', 300, 400)
                ->withResize('original3.jpg', 'original3-small-second.jpg', 300, 400)
                ->withResize('original3.jpg', 'original3-medium.jpg', 500, 600)
                ->withResize('original2.jpg', 'original2-thumb.jpg', 150, 150)
                ->withResize('original2.jpg', 'original2-big.jpg', 600, 700)
                ->build()
        );
    }

    /** @test */
    public function removesPrefixFromImagePathWhenProvidedInOptions()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original3.jpg -colorspace sRGB -resize 600x700 -write original3-big.jpg '
                . '-resize 300x400 -write original3-small.jpg '
                . '-resize 150x150 -write original3-thumb.jpg \\) '
                . '\\( /top/directory2/original3.jpg -colorspace sRGB -resize 500x600 -write /top/directory2/original3-medium.jpg \\) null:',
                '/top/directory'
            ),
            $this->factory->create(2, ['path' => '/top/directory'])
                ->withResize('/top/directory/original3.jpg', '/top/directory/original3-big.jpg', 600, 700)
                ->withResize('/top/directory/original3.jpg', '/top/directory/original3-thumb.jpg', 150, 150)
                ->withResize('/top/directory/original3.jpg', '/top/directory/original3-small.jpg', 300, 400)
                ->withResize('/top/directory2/original3.jpg', '/top/directory2/original3-medium.jpg', 500, 600)
                ->build()
        );
    }

    /** @test */
    public function notFullWhenLimitIsNotReached()
    {
        $builder = $this->factory->create(5)
            ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
            ->withResize('original2.jpg', 'original2-thumb.jpg', 150, 150)
            ->withResize('original3.jpg', 'original3-small.jpg', 300, 400)
            ->withResize('original4.jpg', 'original4-small.jpg', 300, 400)
        ;

        $this->assertFalse($builder->isFull());
    }

    /** @test */
    public function notifiesFullWhenDefaultLimitIsReached()
    {
        $builder = $this->factory->create(5)
            ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
            ->withResize('original2.jpg', 'original2-thumb.jpg', 150, 150)
            ->withResize('original3.jpg', 'original3-small.jpg', 300, 400)
            ->withResize('original4.jpg', 'original4-small.jpg', 300, 400)
            ->withResize('original5.jpg', 'original5-small.jpg', 300, 400)
        ;

        $this->assertTrue($builder->isFull());
    }

    /** @test */
    public function allowsToSpecifyQualityOption()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original1.jpg -colorspace sRGB -resize 600x700 -quality 95 -write original1-big.jpg '
                . '-resize 300x400 -quality 95 -write original1-small.jpg '
                . '-resize 150x150 -quality 95 -write original1-thumb.jpg \\) null:'
            ),
            $this->factory->create(1, ['quality' => 95])
                ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
                ->withResize('original1.jpg', 'original1-small.jpg', 300, 400)
                ->withResize('original1.jpg', 'original1-thumb.jpg', 150, 150)
                ->build()
        );
    }

    /** @test */
    public function allowsToSpecifyInterlaceOption()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original1.jpg -colorspace sRGB -resize 600x700 -interlace \'JPEG\' -write original1-big.jpg '
                . '-resize 300x400 -interlace \'JPEG\' -write original1-small.jpg '
                . '-resize 150x150 -interlace \'JPEG\' -write original1-thumb.jpg \\) null:'
            ),
            $this->factory->create(1, ['interlace' => 'JPEG'])
                ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
                ->withResize('original1.jpg', 'original1-small.jpg', 300, 400)
                ->withResize('original1.jpg', 'original1-thumb.jpg', 150, 150)
                ->build()
        );
    }

    /** @test */
    public function allowsToSpecifySamplingFactoryOption()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original1.jpg -colorspace sRGB -resize 600x700 -sampling-factor \'4:2:0\' -write original1-big.jpg '
                . '-resize 300x400 -sampling-factor \'4:2:0\' -write original1-small.jpg '
                . '-resize 150x150 -sampling-factor \'4:2:0\' -write original1-thumb.jpg \\) null:'
            ),
            $this->factory->create(1, ['sampling' => '4:2:0'])
                ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
                ->withResize('original1.jpg', 'original1-small.jpg', 300, 400)
                ->withResize('original1.jpg', 'original1-thumb.jpg', 150, 150)
                ->build()
        );
    }

    /** @test */
    public function allowsToSpecifyStripOption()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original1.jpg -colorspace sRGB -resize 600x700 -strip -write original1-big.jpg '
                . '-resize 300x400 -strip -write original1-small.jpg '
                . '-resize 150x150 -strip -write original1-thumb.jpg \\) null:'
            ),
            $this->factory->create(1, ['strip' => true])
                ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
                ->withResize('original1.jpg', 'original1-small.jpg', 300, 400)
                ->withResize('original1.jpg', 'original1-thumb.jpg', 150, 150)
                ->build()
        );
    }

    /** @test */
    public function allowsToSpecifyFilterOption()
    {
        $this->assertEquals(
            new Process(
                'convert '
                . '\\( original1.jpg -colorspace sRGB -resize 600x700 -filter \'Hamming\' -write original1-big.jpg '
                . '-resize 300x400 -filter \'Hamming\' -write original1-small.jpg '
                . '-resize 150x150 -filter \'Hamming\' -write original1-thumb.jpg \\) null:'
            ),
            $this->factory->create(1, ['filter' => 'Hamming'])
                ->withResize('original1.jpg', 'original1-big.jpg', 600, 700)
                ->withResize('original1.jpg', 'original1-small.jpg', 300, 400)
                ->withResize('original1.jpg', 'original1-thumb.jpg', 150, 150)
                ->build()
        );
    }
}
