<?php
namespace Mcustiel\Phiremock\Codeception\Module\Tests\Helpers;

use Mcustiel\Phiremock\Client\Factory;
use GuzzleHttp;
use Psr\Http\Client\ClientInterface;

class FactoryWithGuzzle7 extends Factory
{
    public function createRemoteConnection(): ClientInterface
    {
        return new GuzzleHttp\Client(
            [
                'allow_redirects' => false,
                'http_errors' => false,
                'verify'      => false,
            ]
        );
    }
}
