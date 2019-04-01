<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use EcomDev\ImageResizeServer\BackgroundJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;

class ReactApplicationTest extends TestCase
{

    /** @var TestDirectory */
    private $testDirectory;

    /** @var Client */
    private $httpClient;

    /** @var BackgroundJob */
    private $backgroundJob;

    protected function setUp(): void
    {



        $this->testDirectory = TestDirectory::create();
        $this->testDirectory->copyFrom(__DIR__ . '/fixtures');

        $savePath = $this->testDirectory->resolvePath('cache');
        $sourcePath = $this->testDirectory->resolvePath('images');

        $builder = ReactApplicationBuilder::create(8888)
            ->withSourcePath($sourcePath)
            ->withSavePath($savePath)
            ->withBaseUrl('/cache/')
            ->withUrlPattern(':width:x:height:/:any_dir:/:image:');

        $this->backgroundJob = BackgroundJob::create(function () use ($builder) {
            $builder->build()->run();
        });

        $this->httpClient = new Client(['base_uri' => 'http://127.0.0.1:8888/', 'http_errors' => false]);

    }

    /** @test */
    public function returnsNotFoundResponseWhenImageDoesNotExists()
    {
        $this->assertResponse(
            404,
            ['Content-Type' => 'text/plain'],
            $this->httpClient->request('GET', 'images/not-a-file.jpg')
        );
    }

    /** @test */
    public function doesNotAllowToAccessFileDirectly()
    {
        $this->assertResponse(
            404,
            ['Content-Type' => 'text/plain'],
            $this->httpClient->request('GET', 'images/landscape.jpg')
        );
    }

    /** @test */
    public function resizesImageThatMatchesUrlPattern()
    {
        $response = $this->httpClient->request('GET', 'cache/100x100/small/landscape.jpg');

        $this->assertResponse(
            200,
            ['Content-Type' => 'image/jpeg'],
            $response
        );

        $this->assertImageDimensions(
            $response,
            100,
            75
        );
    }

    /** @test */
    public function resizesConcurrentlySameImageAndDeliversAllOfThem()
    {

        $request = new Request('GET', 'cache/100x100/small/landscape.jpg');

        $responses = Pool::batch($this->httpClient, array_fill(0, 200, $request));
        foreach ($responses as $response) {
            $this->assertResponse(
                200,
                ['Content-Type' => 'image/jpeg'],
                $response
            );

            $this->assertImageDimensions(
                $response,
                100,
                75
            );
        }
    }


    protected function tearDown(): void
    {
        $this->backgroundJob->sendSignal(SIGINT);
        $this->backgroundJob->wait();
    }

    private function assertResponse(int $expectedStatus, array $expectedHeaders, Response $response)
    {
        $this->assertEquals($expectedStatus, $response->getStatusCode(), 'Server returned wrong HTTP status code');
        foreach ($expectedHeaders as $headerName => $headerValue) {
            $this->assertEquals(
                $headerValue,
                $response->getHeaderLine($headerName),
                sprintf('Server returned wrong header value for %s', $headerName)
            );
        }
    }

    private function assertImageDimensions(ResponseInterface $response, int $expectedWidth, int $expectedHeight)
    {
        list($width, $height) = getimagesizefromstring($response->getBody()->getContents());

        $this->assertEquals([$expectedWidth, $expectedHeight], [$width, $height], 'Image dimensions does not match');
    }
}
