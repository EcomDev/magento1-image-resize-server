<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use PHPUnit\Framework\TestCase;

class FakeResizeServiceTest extends TestCase implements ResizeServiceObserver
{
    /** @var FakeResizeService */
    private $resizeService;

    /** @var array */
    private $actions = [];

    protected function setUp(): void
    {
        $this->resizeService = new FakeResizeService();
    }

    /** @test */
    public function doesNotProcessAnyItemsWhenNotRequested()
    {
        $this->resizeService->resize('image1.jpg', ['image1-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);

        $this->assertActions();
    }

    /** @test */
    public function completesSingleItem()
    {
        $this->resizeService->resize('image1.jpg', ['image1-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);

        $this->resizeService->completeItem();

        $this->assertActions(['complete', 'image1-small.jpg']);
    }

    /** @test */
    public function completesMultipleItems()
    {
        $this->resizeService->resize('image1.jpg', ['image1-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);

        $this->resizeService->completeItem();
        $this->resizeService->completeItem();

        $this->assertActions(
            ['complete', 'image1-small.jpg'],
            ['complete', 'image2-small.jpg']
        );
    }


    /** @test */
    public function completesAllVariationsItem()
    {
        $this->resizeService->resize(
            'image1.jpg',
            [
                'image1-small.jpg' => [100, 100],
                'image1-medium.jpg' => [200, 200],
                'image1-big.jpg' => [300, 300],
            ],
            $this
        );

        $this->resizeService->completeItem();

        $this->assertActions(
            ['complete', 'image1-small.jpg'],
            ['complete', 'image1-medium.jpg'],
            ['complete', 'image1-big.jpg']
        );
    }

    /** @test */
    public function ignoresEmptyCommandsEvenWhenCompleteIsInvoked()
    {
        $this->resizeService->completeItem();

        $this->assertActions();
    }

    /** @test */
    public function failsSingleItem()
    {
        $this->resizeService->resize('image1.jpg', ['image1-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);

        $this->resizeService->failItem();

        $this->assertActions(['failed', 'image1-small.jpg']);
    }

    /** @test */
    public function failsMultipleItems()
    {
        $this->resizeService->resize('image1.jpg', ['image1-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);

        $this->resizeService->failItem();
        $this->resizeService->failItem();

        $this->assertActions(
            ['failed', 'image1-small.jpg'],
            ['failed', 'image2-small.jpg']
        );
    }


    /** @test */
    public function failsAllSingleItemVariations()
    {
        $this->resizeService->resize(
            'image1.jpg',
            [
                'image1-small.jpg' => [100, 100],
                'image1-medium.jpg' => [200, 200],
                'image1-big.jpg' => [300, 300],
            ],
            $this
        );

        $this->resizeService->failItem();

        $this->assertActions(
            ['failed', 'image1-small.jpg'],
            ['failed', 'image1-medium.jpg'],
            ['failed', 'image1-big.jpg']
        );
    }

    /** @test */
    public function listsPendingCommands()
    {
        $this->resizeService->resize('image1.jpg', ['image1-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);


        $this->assertEquals(
            [
                ['image1.jpg', ['image1-small.jpg' => [100, 100]], $this],
                ['image2.jpg', ['image2-small.jpg' => [100, 100]], $this]
            ],
            $this->resizeService->listPendingCommands()
        );
    }

    /** @test */
    public function removesCompleteCommandsFromPendingList()
    {
        $this->resizeService->resize('image1.jpg', ['image1-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);

        $this->resizeService->completeItem();


        $this->assertEquals(
            [
                ['image2.jpg', ['image2-small.jpg' => [100, 100]], $this]
            ],
            $this->resizeService->listPendingCommands()
        );
    }

    /** @test */
    public function removesFailedCommandsFromPendingList()
    {
        $this->resizeService->resize('image2.jpg', ['image2-small.jpg' => [100, 100]], $this);
        $this->resizeService->resize('image3.jpg', ['image3-small.jpg' => [100, 100]], $this);

        $this->resizeService->failItem();


        $this->assertEquals(
            [
                ['image3.jpg', ['image3-small.jpg' => [100, 100]], $this]
            ],
            $this->resizeService->listPendingCommands()
        );
    }

    public function handleResizeComplete(string $target)
    {
        $this->actions[] = ['complete', $target];
    }

    public function handleResizeFailed(string $target)
    {
        $this->actions[] = ['failed', $target];
    }

    private function assertActions(array ...$actions)
    {
        $this->assertEquals($actions, $this->actions);
    }
}
