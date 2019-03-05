<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use PHPUnit\Framework\TestCase;

class ImageGatewayTest extends TestCase
{
    /**
     * @var ImageGateway
     */
    private $gateway;

    /** @var ImageGatewayBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = ImageGatewayBuilder::createDefault()
            ->withSourcePath('/image/root/')
            ->withPathPattern('/cache/:width:x:height:/:image:')
            ->withBaseUrl('/media/catalog/product');
    }

    /** @test */
    public function resolvesImageWithWidthAndHeight()
    {
        $this->assertEquals(
            new Image(
                '/image/root/cache/300x400/image1.jpg',
                '/image/root/image1.jpg',
                300,
                400
            ),
            $this->builder->build()->findImage('/media/catalog/product/cache/300x400/image1.jpg')
        );
    }

    /** @test */
    public function resolvesImageWithWidthOnly()
    {
        $this->assertEquals(
            new Image(
                '/image/root/cache/400x/image1.jpg',
                '/image/root/image1.jpg',
                400,
                400
            ),
            $this->builder->build()->findImage('/media/catalog/product/cache/400x/image1.jpg')
        );
    }

    /** @test */
    public function resolvesImageWithCustomSavePath()
    {
        $this->assertEquals(
            new Image(
                '/image/tmp/cache/400x/image1.jpg',
                '/image/root/image1.jpg',
                400,
                400
            ),
            $this->builder->withSavePath('/image/tmp/')->build()
                ->findImage('/media/catalog/product/cache/400x/image1.jpg')
        );
    }
}
