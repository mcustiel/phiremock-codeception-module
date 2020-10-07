# phiremock-codeception-module

This codeception module allows you to connect to a Phiremock Server and to interact with it in a semantic way through the codeception actor in your tests.

## Installation

### Composer:

```json
    "require-dev": {
        "mcustiel/phiremock-codeception-module": "v1.0",
        "guzzlehttp/guzzle": "^6.0"
    }
```

## Configuration
You need to enable Phiremock module in your suite's configuration file:

```yaml
modules:
    enabled:
        - Phiremock:
            host: phiremock-myhost.dev # Defaults to localhost
            port: 18080 # Defaults to 8086
            reset_before_each_test: false # if set to true, executes `$I->haveACleanSetupInRemoteService` before each test. Defaults to false
            expectations_path: /path/to/my/expectation/json/files # Defaults to codeception_dir/_data/phiremock-expectations
            client_factory: \My\ClientFactory # Defaults to 'default'
```

### Options

#### host
Specifies the host where phiremock-server is listening for requests.
**Default:** localhost

#### port
Specifies the port where phiremock-server is listening for requests.
**Default:** 8086

#### reset_before_each_test
Determines whether or not phiremock-server must be reset before each test.
**Default:** false

#### expectations_path
Specifies the path where expectation json files are located. The files can then be referenced using annotations and will be loaded automatically.
**Default:** codeception_dir/_data/phiremock-expectations

#### client_factory
Specifies the fully qualified class name of a class which overrides default phiremock-client factory. This is useful if you want to avoid using Guzzle HttpClient v6 (the one supported by default in phiremock-client).
**Default:** default

##### Example
```json
"require-dev": {
    "mcustiel/phiremock-codeception-module": "v1.0",
    "guzzlehttp/guzzle": "^7.0"
```

The you can create a factory as follows and provide the fully qualified class name in the module configuration:

```php
<?php
namespace My\Namespace;

use Mcustiel\Phiremock\Client\Factory;
use GuzzleHttp;
use Psr\Http\Client\ClientInterface;

class FactoryWithGuzzle7 extends Factory
{
    public function createRemoteConnection(): ClientInterface
    {
        return new GuzzleHttp\Client(['allow_redirects' => false]);
    }
}
```
Any http client implementing psr18 can be provided.

## Usage
The module provides the following handy methods to communicate with Phiremock server:

### expectARequestToRemoteServiceWithAResponse
Allows you to setup an expectation in Phiremock, specifying the expected request and the response the server should give for it:

```php
    $I->expectARequestToRemoteServiceWithAResponse(
        on(getRequest()->andUrl(isEqualTo('/some/url')))
            ->then(respond(203)->andBody('I am a response'))
    );
```

### haveACleanSetupInRemoteService
Cleans the server of all configured expectations, scenarios and requests history, and reloads expectation files if they are present.

```php
    $I->haveACleanSetupInRemoteService();
```

### dontExpectRequestsInRemoteService
Cleans all previously configured expectations and requests history.

```php
    $I->dontExpectRequestsInRemoteService();
```

### haveCleanScenariosInRemoteService
Cleans the state of all scenarios (sets all of them to inital state).

```php
    $I->haveCleanScenariosInRemoteService();
```

### seeRemoteServiceReceived
Allows you to verify that the server received a request a given amount of times. This request could or not be previously set up as an expectation.

```php
    $I->seeRemoteServiceReceived(1, getRequest()->andUrl(isEqualTo('/some/url')));
```

### didNotReceiveRequestsInRemoteService
Resets the requests counter for the verifier in Phiremock. 

```php
    $I->didNotReceiveRequestsInRemoteService();
```

### grabRequestsMadeToRemoteService
Retrieves all the requests received by Phiremock server matching the one specified.

```php
    $I->grabRequestsMadeToRemoteService(getRequest()->andUrl(isEqualTo('/some/url')));
```

### setScenarioState
Forces the state of a scenario.

```php
    $I->setScenarioState('scenarioName', 'newScenarioState');
```

### @expectation Annotations

Allows you to set up an expectation via a json file

```php
    /**
     * @expectation("get_client_timeout")
     */
    public function test(FunctionalTester $I)
    {
        ...
    }
```

That will load by default the file at `tests/_data/phiremock-expectations/get_client_timeout.json`. The path where to place the expectations is configurable.

You may use expectations placed in subdirectories

```php
    /**
     * @expectation("edge_cases/get_client_timeout")
     */
    public function test(FunctionalTester $I)
    {
        ...
    }
```

Multiple annotation formats are accepted

```
     * @expectation get_client_timeout
     * @expectation get_client_timeout.json
     * @expectation(get_client_timeout.json)
     * @expectation(get_client_timeout)
     * @expectation("get_client_timeout")
```

## See also:

Phiremock Client: https://github.com/mcustiel/phiremock-client
Examples in tests: https://github.com/mcustiel/phiremock-codeception-module/tree/master/tests/acceptance
