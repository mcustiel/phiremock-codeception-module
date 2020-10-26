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

use Codeception\Lib\ModuleContainer;
use Codeception\Module as CodeceptionModule;
use Codeception\TestInterface;
use Mcustiel\Phiremock\Client\Utils\ConditionsBuilder;
use Mcustiel\Phiremock\Client\Utils\Http\Scheme;
use Mcustiel\Phiremock\Codeception\Util\Config;
use Mcustiel\Phiremock\Codeception\Util\ExpectationAnnotationParser;
use Mcustiel\Phiremock\Domain\Expectation;

class Phiremock extends CodeceptionModule
{
    /** @var array */
    protected $config = Config::DEFAULT_CONFIG;

    /** @var \Mcustiel\Phiremock\Client\Phiremock */
    private $phiremock;

    /** @var ExpectationAnnotationParser */
    private $expectationsParser;

    /** @var Config */
    private $moduleConfig;

    /** @var Phiremock[] */
    private $extraConnections = [];

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        parent::__construct($moduleContainer, $config);
        $this->moduleConfig = new Config($this->config);
        foreach ($this->moduleConfig->getExtraConnectionsConfigs() as $name => $connectionConfig) {
            $this->extraConnections[$name] = new self($this->moduleContainer, $connectionConfig->asArray());
        }
    }

    public function _beforeSuite($settings = [])
    {
        $this->config = array_merge($this->moduleConfig->asArray(), $settings);
        $this->moduleConfig = new Config($this->config);

        $this->phiremock = $this->moduleConfig->getClientFactory()->createPhiremockClient(
            $this->moduleConfig->getHost(),
            $this->moduleConfig->getPort(),
            new Scheme($this->moduleConfig->isSecure() ? Scheme::HTTPS: Scheme::HTTP)
        );
        $this->expectationsParser = new ExpectationAnnotationParser(
            $this->moduleConfig->getExpectationsPath()
        );
        foreach ($this->extraConnections as $module) {
            $module->_beforeSuite($settings);
        }
    }

    public function _before(TestInterface $test)
    {
        if ($this->moduleConfig->isResetBeforeEachTest()) {
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

    public function takeConnection(string $name): Phiremock
    {
        if ($name === 'default') {
            return $this;
        }
        if (!isset($this->extraConnections[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Connection %s does not exist', $name)
            );
        }
        return $this->extraConnections[$name];
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

    /** @deprecated Name is confusing, sounds like an assertion */
    public function didNotReceiveRequestsInRemoteService(): void
    {
        $this->dontHaveLoggedRequestsToRemoteService();
    }

    public function dontHaveLoggedRequestsToRemoteService(): void
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
}
