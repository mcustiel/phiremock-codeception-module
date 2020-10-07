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

use Codeception\Exception\ConfigurationException;
use Codeception\Lib\ModuleContainer;
use Codeception\Module as CodeceptionModule;
use Codeception\TestInterface;
use Codeception\Util\ExpectationAnnotationParser;
use Mcustiel\Phiremock\Client\Connection\Host;
use Mcustiel\Phiremock\Client\Connection\Port;
use Mcustiel\Phiremock\Client\Factory;
use Mcustiel\Phiremock\Client\Utils\ConditionsBuilder;
use Mcustiel\Phiremock\Domain\Expectation;

class Phiremock extends CodeceptionModule
{
    private const EXPECTATIONS_PATH = 'phiremock-expectations';
    private const EXPECTATIONS_PATH_CONFIG = 'expectations_path';

    /** @var array */
    protected $config = [
        'host'                         => 'localhost',
        'port'                         => 8086,
        'reset_before_each_test'       => false,
        'client_factory'               => 'default',
        self::EXPECTATIONS_PATH_CONFIG => null,
    ];

    /** @var \Mcustiel\Phiremock\Client\Phiremock */
    private $phiremock;

    /** @var ExpectationAnnotationParser */
    private $expectationsParser;

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        parent::__construct($moduleContainer, $config);
    }

    public function _beforeSuite($settings = [])
    {
        $this->config = array_merge($this->config, $settings);

        if (isset($this->config['resetBeforeEachTest'])) {
            $this->debug('Phiremock/DEPRECATION: resetBeforeEachTest option is deprecated and will be removed. Please use reset_before_each_test.');
            $this->config['reset_before_each_test'] = $this->config['resetBeforeEachTest'];
        }
        if (isset($this->config['expectationsPath'])) {
            $this->debug('Phiremock/DEPRECATION: expectationsPath option is deprecated and will be removed. Please use expectations_path.');
            $this->config[self::EXPECTATIONS_PATH_CONFIG] = $this->config['expectationsPath'];
        }

        $this->setExpectationsPathConfiguration();

        $this->phiremock = $this->createFactory()->createPhiremockClient(
            new Host($this->config['host']),
            new Port($this->config['port'])
        );
        $this->expectationsParser = new ExpectationAnnotationParser(
            $this->config[self::EXPECTATIONS_PATH_CONFIG]
        );
    }

    public function _before(TestInterface $test)
    {
        if ($this->config['reset_before_each_test']) {
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

    public function setScenarioState(string $name, string $state): void
    {
        $this->phiremock->setScenarioState($name, $state);
    }

    private function createFactory(): Factory
    {
        if (isset($this->config['client_factory'])) {
            $factoryClassConfig = $this->config['client_factory'];
            if ($factoryClassConfig !== 'default') {
                $factoryClassName = $this->config['client_factory'];
                if (!is_a($factoryClassName, Factory::class, true)) {
                    throw new ConfigurationException(
                        sprintf('%s does not extend %s', $factoryClassName, Factory::class)
                    );
                }
                return $factoryClassName::createDefault();
            }
        }
        return Factory::createDefault();
    }

    private function setExpectationsPathConfiguration(): void
    {
        $configuredPath = $this->config[self::EXPECTATIONS_PATH_CONFIG] ?? null;
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
