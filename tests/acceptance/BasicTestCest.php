<?php

use Codeception\Configuration;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Mcustiel\Phiremock\Client\Phiremock;
use function Mcustiel\Phiremock\Client\postRequest;
use function Mcustiel\Phiremock\Client\respond;
use Mcustiel\Phiremock\Client\Utils\A;
use Mcustiel\Phiremock\Client\Utils\Is;
use Mcustiel\Phiremock\Client\Utils\Respond;

class BasicTestCest
{
    /** @var Client */
    private $guzzle;

    public function _before(AcceptanceTester $I)
    {
        $this->guzzle = new Client(
            [
                'base_uri'    => 'http://localhost:18080',
                'http_errors' => false
            ]
        );
    }

    public function severalExceptatationsInOneTest(AcceptanceTester $I)
    {
        $I->expectARequestToRemoteServiceWithAResponse(
            Phiremock::on(
                A::getRequest()->andUrl(Is::equalTo('/potato'))
            )->then(
                Respond::withStatusCode(203)->andBody('I am a potato')
            )
        );
        $I->expectARequestToRemoteServiceWithAResponse(
            Phiremock::on(
                A::getRequest()->andUrl(Is::equalTo('/tomato'))
            )->then(
                Respond::withStatusCode(203)->andBody('I am a tomato')
            )
        );
        $I->expectARequestToRemoteServiceWithAResponse(
            Phiremock::on(
                A::getRequest()->andUrl(Is::equalTo('/coconut'))
            )->then(
                Respond::withStatusCode(203)->andBody('I am a coconut')
            )
        );
        $I->expectARequestToRemoteServiceWithAResponse(
            Phiremock::on(
                A::getRequest()->andUrl(Is::equalTo('/banana'))
            )->then(
                Respond::withStatusCode(203)->andBody('I am a banana')
            )
        );
        foreach (['potato', 'tomato', 'banana', 'coconut'] as $item) {
            $response = (string) $this->guzzle->get("/{$item}")->getBody();
            $I->assertEquals('I am a ' . $item, $response);
        }
        $I->seeRemoteServiceReceived(4, A::getRequest());
        $I->seeRemoteServiceReceived(1, A::getRequest()->andUrl(Is::equalTo('/potato')));
        $I->seeRemoteServiceReceived(1, A::getRequest()->andUrl(Is::equalTo('/tomato')));
        $I->seeRemoteServiceReceived(1, A::getRequest()->andUrl(Is::equalTo('/banana')));
        $I->seeRemoteServiceReceived(1, A::getRequest()->andUrl(Is::equalTo('/coconut')));
        $I->didNotReceiveRequestsInRemoteService();
        $I->seeRemoteServiceReceived(0, A::getRequest());
    }

    public function shouldSetTheScenarioState(AcceptanceTester $I): void
    {
        $I->expectARequestToRemoteServiceWithAResponse(
            Phiremock::on(
                A::getRequest()
                    ->andUrl(Is::equalTo('/potato'))
                    ->andScenarioState('tomatoScenario', 'potatoState')
            )->then(
                Respond::withStatusCode(203)->andBody('I am a potato')
            )
        );
        $response = $this->guzzle->get('/potato');
        $I->assertSame(404, $response->getStatusCode());
        $I->setScenarioState('tomatoScenario', 'potatoState');
        $response = $this->guzzle->get('/potato');
        $I->assertSame(203, $response->getStatusCode());
        $I->assertSame('I am a potato', (string) $response->getBody());
    }

    public function shouldCreateAnExpectationWithBinaryResponseTest(AcceptanceTester $I)
    {
        $responseContents = file_get_contents(Configuration::dataDir() . '/fixtures/silhouette-1444982_640.png');
        $I->expectARequestToRemoteServiceWithAResponse(
            Phiremock::on(
                A::getRequest()->andUrl(Is::equalTo('/show-me-the-video'))
            )->then(
                respond(200)->andBinaryBody($responseContents)
            )
        );

        $responseBody = (string) $this->guzzle->get('/show-me-the-video')->getBody();
        $I->assertEquals($responseContents, $responseBody);
    }

    public function testGrabRequestsMadeToRemoteService(AcceptanceTester $I)
    {
        $requestBuilder = postRequest()->andUrl(Is::equalTo('/some/url'));
        $I->expectARequestToRemoteServiceWithAResponse(
            Phiremock::on($requestBuilder)->then(respond(200))
        );

        $request = new Request(
            'POST',
            '/some/url',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query(['a' => 'b'])
        );
        $this->guzzle->send($request);

        $requests = $I->grabRequestsMadeToRemoteService($requestBuilder);
        $I->assertCount(1, $requests);
        $first = reset($requests);
        $I->assertEquals('POST', $first->method);
        $I->assertEquals('a=b', $first->body);

        $headers = (array) $first->headers;
        $expectedSubset = [
            'Host'         => ['localhost:18080'],
            'Content-Type' => ['application/x-www-form-urlencoded']
        ];

        foreach ($expectedSubset as $key => $value) {
            $I->assertArrayHasKey($key, $headers);
            $I->assertSame($value, $headers[$key]);
        }
    }

    /**
     * @param AcceptanceTester $I
     * @expectation("test_first_get")
     */
    public function testAnnotationExpectationIsLoaded(AcceptanceTester $I)
    {
        $requestBuilder = A::getRequest()->andUrl(Is::equalTo('/expectation/1'));
        $response = (string) $this->guzzle->get('/expectation/1')->getBody();

        $requests = $I->grabRequestsMadeToRemoteService($requestBuilder);
        $I->assertCount(1, $requests);

        $I->assertEquals('response', $response);
    }

    /**
     * @param AcceptanceTester $I
     * @expectation("test_first_get")
     * @expectation("test_second_get")
     */
    public function testMultipleAnnotationsAreLoaded(AcceptanceTester $I)
    {
        $requestBuilder = A::getRequest()->andUrl(Is::matching('/\\/expectation\\/\\d+/'));
        $this->guzzle->get('/expectation/1');
        $this->guzzle->get('/expectation/2');
        $requests = $I->grabRequestsMadeToRemoteService($requestBuilder);
        $I->assertCount(2, $requests);
    }

    /**
     * @param AcceptanceTester $I
     * @expectation("subdirectory/test_first_get")
     */
    public function testAnnotationInSubdirectoryIsLoaded(AcceptanceTester $I)
    {
        $conditionsBuilder = A::getRequest();
        $requestBuilder = $conditionsBuilder->andMethod(Is::equalTo('GET'))->andUrl(Is::equalTo('/expectation/subdirectory'));
        $responseBody = (string) $this->guzzle->get('/expectation/subdirectory')->getBody();
        $I->assertSame('response', $responseBody);
        $requests = $I->grabRequestsMadeToRemoteService($requestBuilder);
        $I->assertCount(1, $requests);
    }

    /**
     * @param AcceptanceTester $I
     *
     * @expectation test_first_get
     * @expectation test_first_get.json
     * @expectation(test_first_get.json)
     * @expectation(test_first_get)
     * @expectation("test_first_get")
     */
    public function testAnnotationFormats(AcceptanceTester $I)
    {
    }
}
