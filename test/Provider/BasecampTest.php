<?php namespace Uberboom\OAuth2\Client\Test\Provider;

use Mockery as m;

class BasecampTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;
    protected function setUp()
    {
        $this->provider = new \Uberboom\OAuth2\Client\Provider\Basecamp([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }
    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }
    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->state);
    }
    public function testUrlAccessToken()
    {
        $url = $this->provider->urlAccessToken();
        $uri = parse_url($url);
        $this->assertEquals('/authorization/token', $uri['path']);
    }
    public function testGetAccessToken()
    {
        $response = m::mock('Guzzle\Http\Message\Response');
        $response->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires": 3600, "refresh_token": "mock_refresh_token", "uid": 1}');
        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(1);
        $client->shouldReceive('post->send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->accessToken);
        $this->assertLessThanOrEqual(time() + 3600, $token->expires);
        $this->assertGreaterThanOrEqual(time(), $token->expires);
        $this->assertEquals('mock_refresh_token', $token->refreshToken);
        $this->assertEquals('1', $token->uid);
    }
    public function testScopes()
    {
        $this->assertEquals([], $this->provider->getScopes());
    }
    public function testUserData()
    {
        $postResponse = m::mock('Guzzle\Http\Message\Response');
        $postResponse->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires": 3600, "refresh_token": "mock_refresh_token", "uid": 1}');
        $getResponse = m::mock('Guzzle\Http\Message\Response');
        $getResponse->shouldReceive('getBody')->times(4)->andReturn('{"identity": {"first_name": "mock_first_name", "id": 12345, "email_address": "mock_email_address", "last_name": "mock_last_name"}, "accounts": [ {"id": 45678, "name": "mock_name", "href": "mock_href", "product": "mock_product"} ]}');
        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(5);
        $client->shouldReceive('post->send')->times(1)->andReturn($postResponse);
        $client->shouldReceive('get->send')->times(4)->andReturn($getResponse);
        $client->shouldReceive('setDefaultOption')->times(4);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getUserDetails($token);
        $this->assertEquals(12345, $this->provider->getUserUid($token));
        $this->assertEquals('mock_email_address', $this->provider->getUserEmail($token));
        $this->assertEquals(['mock_first_name', 'mock_last_name'], $this->provider->getUserScreenName($token));
        $this->assertEquals('mock_email_address', $user->email);
    }
    public function testBasecampAccounts()
    {
        $postResponse = m::mock('Guzzle\Http\Message\Response');
        $postResponse->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires": 3600, "refresh_token": "mock_refresh_token", "uid": 1}');
        $getResponse = m::mock('Guzzle\Http\Message\Response');
        $getResponse->shouldReceive('getBody')->times(1)->andReturn('{"identity": {"first_name": "mock_first_name", "id": 12345, "email_address": "mock_email_address", "last_name": "mock_last_name"}, "accounts": [ {"id": 45678, "name": "mock_name", "href": "mock_href", "product": "mock_product"} ]}');
        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(2);
        $client->shouldReceive('post->send')->times(1)->andReturn($postResponse);
        $client->shouldReceive('get->send')->times(1)->andReturn($getResponse);
        $client->shouldReceive('setDefaultOption')->times(1);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $expectedUser = new \stdClass();
        $expectedUser->id = 45678;
        $expectedUser->name = 'mock_name';
        $expectedUser->href = 'mock_href';
        $expectedUser->product = 'mock_product';
        $this->assertEquals([$expectedUser], $this->provider->getBasecampAccounts($token));
    }
}
