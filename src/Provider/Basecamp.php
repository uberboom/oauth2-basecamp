<?php namespace Uberboom\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Token\AccessToken;

class Basecamp extends AbstractProvider
{
    public $authorizationHeader = 'Bearer';

    public function urlAuthorize()
    {
        return 'https://launchpad.37signals.com/authorization/new';
    }

    public function urlAccessToken()
    {
        return 'https://launchpad.37signals.com/authorization/token';
    }

    public function urlUserDetails(AccessToken $token)
    {
        return 'https://launchpad.37signals.com/authorization.json';
    }

    public function userDetails($response, AccessToken $token)
    {
        $user = new User();
        $user->exchangeArray([
            'uid' => isset($response->identity->id) && $response->identity->id ? $response->identity->id : null,
            'email' => isset($response->identity->email_address) && $response->identity->email_address ? $response->identity->email_address : null,
            'firstname' => isset($response->identity->first_name) && $response->identity->first_name ? $response->identity->first_name : null,
            'lastname' => isset($response->identity->last_name) && $response->identity->last_name ? $response->identity->last_name : null,
        ]);
        return $user;
    }

    public function userUid($response, AccessToken $token)
    {
        return isset($response->identity->id) && $response->identity->id ? $response->identity->id : null;
    }

    public function userEmail($response, AccessToken $token)
    {
        return isset($response->identity->email_address) && $response->identity->email_address ? $response->identity->email_address : null;
    }

    public function userScreenName($response, AccessToken $token)
    {
        return [$response->identity->first_name, $response->identity->last_name];
    }

    public function getAuthorizationUrl($options = [])
    {
        $url = parent::getAuthorizationUrl($options);
        $url .= '&type=web_server';
        return $url;
    }

    public function getAccessToken($grant = 'authorization_code', $params = [])
    {
        $params['type'] = 'web_server';
        return parent::getAccessToken($grant, $params);
    }

    /**
     * Get basecamp accounts
     *
     * @return array
     */
    public function getBasecampAccounts(AccessToken $token)
    {
        $response = $this->fetchUserDetails($token);
        $details = json_decode($response);
        return $details->accounts;
    }

}
