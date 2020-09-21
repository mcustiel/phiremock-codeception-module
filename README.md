# phiremock-codeception-module

This codeception module allows you to connect to a Phiremock Server and to interact with it in a semantic way through the codeception actor in your tests.

#### Configuration
You need to enable Phiremock module in your suite's configuration file:

```yaml
modules:
    enabled:
        - Phiremock:
            host: phiremock-myhost.dev # Defaults to localhost
            port: 18080 # Defaults to 8086
            resetBeforeEachTest: false # if set to true, executes `$I->haveACleanSetupInRemoteService` before each test. Defaults to false
            expectations_path: /path/to/my/expectation/json/files # Defaults to codeception_dir/_data/phiremock-expectations
            client_factory: \My\ClientFactory # Defaults to 'default'
```

#### Use
The module provides the following handy methods to communicate with Phiremock server:

#### expectARequestToRemoteServiceWithAResponse
Allows you to setup an expectation in Phiremock, specifying the expected request and the response the server should give for it:

```php
    $I->expectARequestToRemoteServiceWithAResponse(
        Phiremock::on(
            A::getRequest()->andUrl(Is::equalTo('/some/url'))
        )->then(
            Respond::withStatusCode(203)->andBody('I am a response')
        )
    );
```

#### haveACleanSetupInRemoteService
Cleans the server of all configured expectations, scenarios and requests history, and reloads expectation files.

```php
    $I->haveACleanSetupInRemoteService();
```

#### dontExpectRequestsInRemoteService
Cleans all previously configured expectations and requests history.

```php
    $I->dontExpectRequestsInRemoteService();
```

#### haveCleanScenariosInRemoteService
Cleans the state of all scenarios (sets all of them to inital state).

```php
    $I->haveCleanScenariosInRemoteService();
```

#### seeRemoteServiceReceived
Allows you to verify that the server received a request a given amount of times. This request could or not be previously set up as an expectation.

```php
    $I->seeRemoteServiceReceived(1, A::getRequest()->andUrl(Is::equalTo('/some/url')));
```

#### didNotReceiveRequestsInRemoteService
Resets the requests counter for the verifier in Phiremock. 

```php
    $I->didNotReceiveRequestsInRemoteService();
```

#### grabRequestsMadeToRemoteService
Retrieves all the requests received by Phiremock server matching the one specified.

```php
    $I->grabRequestsMadeToRemoteService(A::getRequest()->andUrl(Is::equalTo('/some/url')));
```

#### @expectation Annotations

Allows you to to set up an expectation via a json file 

```php
    /**
     * @expectation("get_client_timeout")
     */
    public function test(FunctionalTester $I)
    {
        ...
    }
```

That will load by default the file at `tests/_unique_expectations/get_client_timeout.json`

Multiple annotation formats are accepted

```
     * @expectation get_client_timeout
     * @expectation get_client_timeout.json
     * @expectation(get_client_timeout.json)
     * @expectation(get_client_timeout)
     * @expectation("get_client_timeout")
```
