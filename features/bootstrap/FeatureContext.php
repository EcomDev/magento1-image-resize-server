<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

use Behat\Behat\Context\Context;
use EcomDev\ImageResizeServer\BackgroundJob;
use EcomDev\ImageResizeServer\ReactApplicationBuilder;
use EcomDev\ImageResizeServer\TestDirectory;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    const PORT_FOR_TEST_SERVER = 8088;

    /** @var BackgroundJob */
    private static $applicationServer;

    /** @var Client */
    private $httpClient;

    /** @var \Psr\Http\Message\ResponseInterface */
    private $response;

    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => sprintf('http://localhost:%s/', self::PORT_FOR_TEST_SERVER),
            'http_errors' => false
        ]);
    }

    /** @BeforeSuite */
    public static function startServer()
    {
        self::$applicationServer = BackgroundJob::create(function () {
            $directory = TestDirectory::create();
            $directory->copyFrom(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tests/fixtures');

            ReactApplicationBuilder::create(self::PORT_FOR_TEST_SERVER)
                ->withBaseUrl('/images/cache')
                ->withUrlPattern(':width:x:height:/:image:')
                ->withSavePath($directory->resolvePath('images/cache'))
                ->withSourcePath($directory->resolvePath('images'))
                ->withResizeOptions([
                    'sampling' => '4:2:0',
                    'filter' => 'Hamming',
                    'quality' => 95,
                    'strip' => true,
                    'interlace' => 'JPEG'
                ])
                ->build()
                ->run();
        });
    }

    /** @AfterSuite */
    public static function shutdownBackgroundServer()
    {
        self::$applicationServer->sendSignal(SIGINT);
    }


    /**
     * @When I request :url on the server
     */
    public function visitUrl(string $url)
    {
        $this->response = $this->httpClient->get($url);
    }

    /**
     * @Then I see image with dimensions of :resultWidth by :resultHeight px
     */
    public function verifyResultImageDimensions(int $resultWidth, int $resultHeight)
    {
        Assert::assertEquals(200, $this->response->getStatusCode());
        list($width, $height) = getimagesizefromstring($this->response->getBody()->getContents());
        Assert::assertEquals($resultWidth, $width);
        Assert::assertEquals($resultHeight, $height);
    }
}
