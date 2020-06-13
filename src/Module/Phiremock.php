<?php
/**
 * This file is part of phiremock-codeception-module.
 *
 * phiremock-codeception-module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * phiremock-codeception-module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with phiremock-codeception-module.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Codeception\Module;

use Codeception\Module as CodeceptionModule;
use Codeception\TestInterface;
use Codeception\Util\ExpectationAnnotationParser;
use GuzzleHttp\Client;
use Mcustiel\Phiremock\Client\Phiremock as PhiremockClient;
use Mcustiel\Phiremock\Client\Utils\RequestBuilder;
use Mcustiel\Phiremock\Domain\Expectation;
use Mcustiel\Phiremock\Client\Connection\Host;
use Mcustiel\Phiremock\Client\Connection\Port;
use Mcustiel\Phiremock\Client\Factory;
use Codeception\Exception\ConfigurationException;
use Mcustiel\Phiremock\Client\Utils\ConditionsBuilder;

class Phiremock extends CodeceptionModule
{
    private const EXPECTATIONS_PATH = 'phiremock-expectations';
    private const EXPECTATIONS_PATH_CONFIG = 'expectationsPath';

    /** @var array */
    protected $config = [
        'host'                => 'localhost',
        'port'                => 8086,
        'resetBeforeEachTest' => false,
        self::EXPECTATIONS_PATH_CONFIG => ''
    ];

    /** @var \Mcustiel\Phiremock\Client\Phiremock */
    private $phiremock;

    /** @var ExpectationAnnotationParser */
    private $expectationsParser;

    public function _beforeSuite($settings = [])
    {
        $this->config = array_merge($this->config, $settings);

        $this->setExpectationsPathConfiguration();

        $this->phiremock = Factory::createDefault()->createPhiremockClient(
            new Host($this->config['host']),
            new Port($this->config['port'])
        );
        $this->expectationsParser = new ExpectationAnnotationParser(
            $this->config[self::EXPECTATIONS_PATH_CONFIG]
        );
    }

    public function _before(TestInterface $test)
    {
        if ($this->config['resetBeforeEachTest']) {
            $this->haveACleanSetupInRemoteService();
        }
        $expectations = $this->expectationsParser->getExpectations($test);
        if (!empty($expectations)) {
            foreach ($expectations as $expectation) {
                $this->phiremock->createExpectationFromJson(
                    file_get_contents($expectation)
                );
            }
        }
        parent::_before($test);
    }

    public function expectARequestToRemoteServiceWithAResponse(Expectation $expectation): void
    {
        $this->phiremock->createExpectation($expectation);
    }

    public function haveACleanSetupInRemoteService(): void
    {
        $this->phiremock->reset();
    }

    public function dontExpectRequestsInRemoteService(): void
    {
        $this->phiremock->clearExpectations();
        $this->phiremock->resetRequestsCounter();
    }

    public function haveCleanScenariosInRemoteService(): void
    {
        $this->phiremock->resetScenarios();
    }

    public function didNotReceiveRequestsInRemoteService(): void
    {
        $this->phiremock->resetRequestsCounter();
    }

    /** @throws \Exception */
    public function seeRemoteServiceReceived(int $times, ConditionsBuilder $builder): void
    {
        $requests = $this->phiremock->countExecutions($builder);
        if ($times !== $requests) {
            throw new \Exception(
                "Request expected to be executed $times times, called $requests times instead"
            );
        }
    }

    public function grabRequestsMadeToRemoteService(ConditionsBuilder $builder): array
    {
        return $this->phiremock->listExecutions($builder);
    }

    private function setExpectationsPathConfiguration(): void
    {
        $configuredPath = $this->config[self::EXPECTATIONS_PATH_CONFIG];
        if (empty($configuredPath)) {
            $defaultPath = codecept_data_dir(self::EXPECTATIONS_PATH);
            if (!is_dir($defaultPath)) {
                $created = mkdir($defaultPath);
                if (!$created) {
                    throw new \RuntimeException('Could not create directory ' . $defaultPath);
                }
            }
            $this->config[self::EXPECTATIONS_PATH_CONFIG] = $defaultPath;
        } elseif (!is_dir($configuredPath)) {
            $configuredPath = codecept_absolute_path($configuredPath);
            if (!is_dir($configuredPath)) {
                throw new ConfigurationException('Could not find the configured expectations path: ' . $configuredPath);
            }
            $this->config[self::EXPECTATIONS_PATH_CONFIG] = $configuredPath;
        }
    }
}
