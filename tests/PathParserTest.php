<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use PHPUnit\Framework\TestCase;

class PathParserTest extends TestCase
{

    /**
     * @test
     * @testWith ["/cache/300x/image1.jpg", 300]
     *           ["/cache/400x/image1.jpg", 400]
     *           ["/cache/500x400/image1.jpg", 500]
     */
    public function parsesWidthFromUrlInImage($path, int $width)
    {
        $parser = PathParser::create('/cache/:width:x:height:/:image:');
        $this->assertEquals($width, $parser->parse($path)['width']);
    }

    /**
     * @test
     * @testWith ["/cache/x400/image1.jpg", 400]
     *           ["/cache/400x300/image1.jpg", 300]
     *           ["/cache/500x360/image1.jpg", 360]
     */
    public function parsesHeightFromUrlInImage($path, int $height)
    {
        $parser = PathParser::create('/cache/:width:x:height:/:image:');
        $this->assertEquals($height, $parser->parse($path)['height']);
    }

    /**
     * @test
     * @testWith ["/cache/x400/image1.jpg", "image1.jpg"]
     *           ["/cache/400x200/i/m/image1.jpg", "i/m/image1.jpg"]
     *           ["/cache/500x360/i/m/1/2/image1.jpg", "i/m/1/2/image1.jpg"]
     */
    public function parsesImagePathFromRestOfUrl($path, string $image)
    {
        $parser = PathParser::create('/cache/:width:x:height:/:image:');
        $this->assertEquals($image, $parser->parse($path)['source']);
    }


    /**
     * @test
     * @testWith ["/thumbnail/200x300/some_directory/image1.jpg", "image1.jpg"]
     *           ["/thumbnail/400x500/some_directory/image2.jpg", "image2.jpg"]
     *           ["/thumbnail/400x500/some_directory/1/2/12_image2.jpg", "1/2/12_image2.jpg"]
     */
    public function allowsToUseAnyDirectoryMatcher($path, string $expectedImage)
    {
        $parser = PathParser::create('/thumbnail/:width:x:height:/:any_dir:/:image:');
        $this->assertEquals($expectedImage, $parser->parse($path)['source']);
    }

    /**
     * @test
     * @testWith ["/thumbnail/200x300/some_directory/image1.jpg", "thumbnail/200x300/some_directory/image1.jpg"]
     *           ["/thumbnail/400x500/some_directory/image2.jpg", "thumbnail/400x500/some_directory/image2.jpg"]
     */
    public function providesRelativeExpectedImageFilePath($path, string $expectedImage)
    {
        $parser = PathParser::create('/thumbnail/:width:x:height:/:any_dir:/:image:');
        $this->assertEquals($expectedImage, $parser->parse($path)['path']);
    }



    /**
     * @test
     * @testWith ["/media/catalog/product/cache/200x300/some_directory/image1.jpg", "cache/200x300/some_directory/image1.jpg"]
     *           ["/media/catalog/product/cache/400x500/some_directory/image2.jpg", "cache/400x500/some_directory/image2.jpg"]
     */
    public function allowsToSpecifyBaseUrlAndTrimsItFromExpectedImageFilePath($path, string $expectedImage)
    {
        $parser = PathParser::create(
            'cache/:width:x:height:/:any_dir:/:image:',
            '/media/catalog/product'
        );
        $this->assertEquals($expectedImage, $parser->parse($path)['path']);
    }

    /**
     * @test
     * @testWith ["/media/catalog/product/cache/200x300/some_directory/image1.jpg", "cache/200x300/some_directory/image1.jpg"]
     *           ["/media/catalog/product/cache/400x500/some_directory/image2.jpg", "cache/400x500/some_directory/image2.jpg"]
     */
    public function ignoresRedundantSlashesFromBaseUrlAndPattern($path, string $expectedImage)
    {
        $parser = PathParser::create(
            '/cache/:width:x:height:/:any_dir:/:image:',
            '/media/catalog/product/'
        );
        $this->assertEquals($expectedImage, $parser->parse($path)['path']);
    }



    /**
     * @test
     * @testWith ["/cache/200x400/some_directory/image1.jpg"]
     *           ["/thumbnail/200x400x/some_directory/image1.jpg"]
     *           ["/thumbnail/200/some_directory/image1.jpg"]
     */
    public function rejectsPathThatDoesNotMatchPattern(string $wrongPath)
    {
        $parser = PathParser::create('/thumbnail/:width:x:height:/:any_dir:/:image:');
        $this->expectException(NotValidPathException::class);
        $parser->parse($wrongPath);
    }

    /**
     * @test
     * @testWith ["/cache/200x400/../../image1.jpg"]
     *           ["/cache/200x400/././image1.jpg"]
     *           ["/cache/200x400/;/image1.jpg"]
     *           ["/cache/200x400/someth:ing/image1.jpg"]
     *           ["/cache/200x400/someth;ing/image1.jpg"]
     *           ["/cache/200x400/something/image\\dir\\image1.jpg"]
     */
    public function rejectsImagePathWithInvalidCharacters(string $wrongPath)
    {
        $parser = PathParser::create('/cache/:width:x:height:/:image:');
        $this->expectException(NotValidPathException::class);
        $parser->parse($wrongPath);
    }
}
