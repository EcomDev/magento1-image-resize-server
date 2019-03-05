<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase implements ImageObserver
{
    /**
     * @var InMemoryFileFinder
     */
    private $fileFinder;

    /** @var array */
    private $actions = [];

    /**
     * @param array $action
     */
    private function assertActions(array... $actions): void
    {
        $this->assertEquals(
            $actions,
            $this->actions
        );
    }

    protected function setUp(): void
    {
        $this->fileFinder = new InMemoryFileFinder();
    }

    /** @test */
    public function requestsImageDeliveryWhenCacheImageExists()
    {
        $image = new Image('cache/image1.jpg', 'image1.jpg', 300, 400);
        $image->validate($this->fileFinder->withFile('cache/image1.jpg'), $this);

        $this->assertActions(['deliver', $image, 'cache/image1.jpg']);
    }

    /** @test */
    public function requestsImageResizeWhenCacheFileDoesNotExists()
    {
        $image = new Image('cache/image2.jpg', 'image2.jpg', 300, 400);

        $image->validate($this->fileFinder->withFile('image2.jpg'), $this);

        $this->assertActions(['resize', $image]);
    }

    /** @test */
    public function reportsMissingImageWhenCacheAndSourceFilesAreMissing()
    {
        $image = new Image('cache/image3.jpg', 'image3.jpg', 300, 400);

        $image->validate($this->fileFinder, $this);

        $this->assertActions(['missing', $image, 'image3.jpg']);
    }

    /** @test */
    public function requestsImageDeliveryWhenImageHasBeenResized()
    {
        $image = new Image('cache/image4.jpg', 'image4.jpg', 300, 400);

        $resizeQueue = new FakeResizeQueue();
        $image->validate($this->fileFinder->withFile('image4.jpg'), $this);
        $image->resize($resizeQueue);
        $resizeQueue->complete('cache/image4.jpg');

        $this->assertActions(
            ['resize', $image],
            ['deliver', $image, 'cache/image4.jpg']
        );
    }

    /** @test */
    public function reportsMissingImageWhenResizeFails()
    {
        $image = new Image('cache/image5.jpg', 'image5.jpg', 300, 400);

        $resizeQueue = new FakeResizeQueue();
        $image->validate($this->fileFinder->withFile('image5.jpg'), $this);
        $image->resize($resizeQueue);
        $resizeQueue->fail('cache/image5.jpg');

        $this->assertActions(
            ['resize', $image],
            ['missing', $image, 'image5.jpg']
        );
    }

    /** @test */
    public function hangsDeliveryWhenResizeIsNotComplete()
    {
        $image = new Image('cache/image4.jpg', 'image4.jpg', 300, 400);

        $resizeQueue = new FakeResizeQueue();
        $image->validate($this->fileFinder->withFile('image4.jpg'), $this);
        $image->resize($resizeQueue);

        $this->assertActions(
            ['resize', $image]
        );
    }

    /** @test */
    public function ignoresCompletedResizeForDifferentImage()
    {
        $image = new Image('cache/image4.jpg', 'image4.jpg', 300, 400);

        $resizeQueue = new FakeResizeQueue();

        $image->validate($this->fileFinder->withFile('image4.jpg'), $this);
        $image->resize($resizeQueue);

        $resizeQueue->queue('image2.jpg', 'cache/image2.jpg', 300, 300, $image);
        $resizeQueue->complete('cache/image2.jpg');

        $this->assertActions(
            ['resize', $image]
        );
    }

    public function handleImageDelivery(Image $image, string $filePath)
    {
        $this->actions[] = ['deliver', $image, $filePath];
    }

    public function handleMissingImage(Image $image, string $filePath)
    {
        $this->actions[] = ['missing', $image, $filePath];
    }

    public function handleImageResize(Image $image)
    {
        $this->actions[] = ['resize', $image];
    }
}
