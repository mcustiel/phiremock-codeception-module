<?php

namespace Mcustiel\Phiremock\Codeception\Util;

use Codeception\Exception\ConfigurationException;
use Mcustiel\Phiremock\Client\Connection\Host;
use Mcustiel\Phiremock\Client\Connection\Port;
use Mcustiel\Phiremock\Client\Factory;

class Config
{
    public const EXPECTATIONS_PATH_CONFIG = 'expectations_path';

    public const DEFAULT_CONFIG = [
        'host'                         => 'localhost',
        'port'                         => 8086,
        'reset_before_each_test'       => false,
        'client_factory'               => 'default',
        self::EXPECTATIONS_PATH_CONFIG => null,
        'extra_connections'            => [],
        'secure'                       => false,
    ];
    private const EXPECTATIONS_PATH = 'phiremock-expectations';

    /** @var Host */
    private $host;
    /** @var Port */
    private $port;
    /** @var bool */
    private $resetBeforeEachTest;
    /** @var FactoryClass */
    private $clientFactory;
    /** @var DirectoryPath */
    private $expectationsPath;
    /** @var array */
    private $extraConnections;
    /** @var bool */
    private $secure;
    /** @var callable */
    private $output;

    public function __construct(array $config, callable $output)
    {
        $this->output = $output;
        $this->setResetBeforeEachTest($config);
        $this->expectationsPath = $this->getExpectationsPathConfiguration($config);
        $this->host = new Host($config['host']);
        $this->port = new Port($config['port']);
        $this->clientFactory = $this->getFactoryClass($config);
        $this->initExtraConnections($config);
        $this->secure = $config['secure'];

    }

    /** @return Config[] */
    public function getExtraConnectionsConfigs(): array
    {
        return $this->extraConnections;
    }

    public function getHost(): Host
    {
        return $this->host;
    }

    public function getPort(): Port
    {
        return $this->port;
    }

    public function isResetBeforeEachTest(): bool
    {
        return $this->resetBeforeEachTest;
    }

    public function getClientFactory(): Factory
    {
        return $this->clientFactory->getInstance();
    }

    public function getExpectationsPath(): string
    {
        return $this->expectationsPath->asString();
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function asArray(): array
    {
        return [
            'host'                         => $this->host->asString(),
            'port'                         => $this->port->asInt(),
            'reset_before_each_test'       => $this->resetBeforeEachTest,
            'client_factory'               => $this->clientFactory->asString(),
            self::EXPECTATIONS_PATH_CONFIG => $this->expectationsPath->asString(),
            'extra_connections'            => [],
            'secure'                       => $this->secure,
        ];
    }

    /** @throws ConfigurationException */
    private function getFactoryClass(array $config): FactoryClass
    {
        if (!isset($config['client_factory'])) {
            return new FactoryClass('default');
        }
        return new FactoryClass($config['client_factory']);
    }

    private function getExpectationsPathConfiguration(array $config): DirectoryPath
    {
        if (isset($config['expectationsPath'])) {
            call_user_func(
                $this->output,
                'Phiremock/DEPRECATION: expectationsPath option is deprecated and will be removed. Please use expectations_path.'
            );
            $config[self::EXPECTATIONS_PATH_CONFIG] = $config['expectationsPath'];
        }
        $configuredPath = $config[self::EXPECTATIONS_PATH_CONFIG] ?? null;
        if (empty($configuredPath)) {
            $defaultPath = codecept_data_dir(self::EXPECTATIONS_PATH);
            return DirectoryPath::createAndGetInstance($defaultPath);
        }
        return DirectoryPath::createAbsoluteOrRelativeToCodeceptionDir($configuredPath);
    }

    private function setResetBeforeEachTest($config)
    {
        if (isset($config['resetBeforeEachTest'])) {
            call_user_func(
                $this->output,
                'Phiremock/DEPRECATION: resetBeforeEachTest option is deprecated and will be removed. Please use reset_before_each_test.'
            );
            $config['reset_before_each_test'] = $config['resetBeforeEachTest'];
        }
        $this->resetBeforeEachTest = $config['reset_before_each_test'];
    }

    private function initExtraConnections(array $config): void
    {
        $this->extraConnections = [];
        if (isset($config['extra_connections'])) {
            foreach ($config['extra_connections'] as $connectionName => $extraConnection) {
                $connectionConfig = $extraConnection + self::DEFAULT_CONFIG;
                unset($connectionConfig['extra_connections']);
                $this->extraConnections[$connectionName] = new Config($connectionConfig, $this->output);
            }
        }
    }
}
