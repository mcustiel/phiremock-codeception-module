<?php

namespace Codeception\Util;

use Mcustiel\Phiremock\Client\Connection\Host;
use Mcustiel\Phiremock\Client\Connection\Port;
use Mcustiel\Phiremock\Client\Factory;
use Mcustiel\Phiremock\Client\Phiremock;
use Mcustiel\Phiremock\Factory as PhiremockFactory;
use Psr\Http\Client\ClientInterface;

class FactoryWithConfigurableHttpClient extends Factory
{
    /** @var ClientInterface */
    private $clientInterface;

    public function __construct(PhiremockFactory $factory, ClientInterface $clientInterface)
    {
        parent::__construct($factory);
        $this->clientInterface = $clientInterface;
    }

    public static function createWithClient(ClientInterface $clientInterface): self
    {
        return new self(new PhiremockFactory(), $clientInterface);
    }

    public function createPhiremockClient(Host $host, Port $port)
    {
        return new Phiremock(
            $host,
            $port,
            $this->clientInterface ?? $this->createRemoteConnection(),
            $this->getPhiremockFactory()->createV2UtilsFactory()->createExpectationToArrayConverter(),
            $this->getPhiremockFactory()->createV2UtilsFactory()->createArrayToExpectationConverter(),
            $this->getPhiremockFactory()->createV2UtilsFactory()->createScenarioStateInfoToArrayConverter()
        );
    }
}
