<?php

namespace Pinterest;

use Pinterest\Api\Exceptions\AtLeastOneScopeNeeded;
use Pinterest\Api\Exceptions\InvalidScopeException;
use Pinterest\Api\Exceptions\TokenMissing;
use Pinterest\Api\Exceptions\TooManyScopesGiven;
use Pinterest\App\Scope;
use Pinterest\Http\ClientInterface;
use Pinterest\Http\Request;

/**
 * This class is responsible for authenticating requests.
 *
 * @author Toon Daelman <spinnewebber_toon@hotmail.com>
 */
final class Authentication implements ClientInterface
{
    /**
     * The API base uri.
     *
     * @var string
     */
    const BASE_URI = 'https://api.pinterest.com/v1/';

    /**
     * The http client.
     *
     * @var ClientInterface
     */
    private $http;

    /**
     * The client ID.
     *
     * @var string
     */
    private $clientId;

    /**
     * The client secret.
     *
     * @var string
     */
    private $clientSecret;

    /**
     * The access token.
     *
     * @var string
     */
    private $accessToken;

    /**
     * The Constructor.
     *
     * @param ClientInterface $client       The http client.
     * @param string          $clientId     The client id.
     * @param string          $clientSecret The client secret.
     */
    public function __construct(ClientInterface $client, $clientId, $clientSecret)
    {
        $this->http = $client;
        $this->clientId = (string) $clientId;
        $this->clientSecret = (string) $clientSecret;
    }

    /**
     * Alternative constructor for when we already have an accessToken.
     *
     * @param ClientInterface $client       The (un-authenticated) Http client.
     * @param string          $clientId     The client id.
     * @param string          $clientSecret The client secret.
     * @param string          $accessToken  The OAuth access token.
     */
    public static function withAccessToken(
        ClientInterface $client,
        $clientId,
        $clientSecret,
        $accessToken
    ) {
        $authentication = new static($client, $clientId, $clientSecret);
        $authentication->accessToken = (string) $accessToken;

        return $authentication;
    }

    /**
     * Alternative constructor for when we only have an accessToken.
     *
     * ATTENTION: only the execute method will work, as the others need client id and secret.
     *
     * @param ClientInterface $client      The http client.
     * @param string          $accessToken The OAuth access token.
     */
    public static function onlyAccessToken(
        ClientInterface $client,
        $accessToken
    ) {
        $authentication = new static($client, null, null);
        $authentication->accessToken = (string) $accessToken;

        return $authentication;
    }

    /**
     * First step of the OAuth process.
     *
     * @param string $redirectUrl The OAuth redirect url (where code gets sent).
     * @param array  $scopes      An array of scopes (see assertValidScopes).
     * @param string $state       A unique code you can use to check if the redirect is not spoofed.
     *
     * @return string The redirect url.
     */
    public function getAuthenticationUrl($redirectUrl, array $scopes, $state)
    {
        $this->assertValidScopes($scopes);

        $params = array(
            'response_type' => 'code',
            'redirect_uri'  => (string) $redirectUrl,
            'client_id'     => $this->clientId,
            'scope'         => implode(',', $scopes),
            'state'         => (string) $state,
        );

        return sprintf(
            'https://api.pinterest.com/oauth/?%s',
            http_build_query($params)
        );
    }

    /**
     * Checks if an array of given scopes contains only valid scopes (and at least one).
     *
     * @param array $scopes The array of scopes to check.
     *
     * @throws InvalidScopeException When invalid scope in the given array.
     * @throws AtLeastOneScopeNeeded When no scopes given.
     * @throws TooManyScopesGiven    When double scopes in the list.
     */
    private function assertValidScopes(array $scopes)
    {
        $allowedScopes = array(
            Scope::READ_PUBLIC,
            Scope::WRITE_PUBLIC,
            Scope::READ_RELATIONSHIPS,
            Scope::WRITE_RELATIONSHIPS,
        );

        foreach ($scopes as $scope) {
            if (!in_array($scope, $allowedScopes)) {
                throw new InvalidScopeException($scope);
            }
        }

        if (count($scopes) < 1) {
            throw new AtLeastOneScopeNeeded();
        }

        if (count($scopes) > count($allowedScopes)) {
            throw new TooManyScopesGiven();
        }
    }

    /**
     * Second step of the OAuth process.
     *
     * @param string $code The OAuth code, caught from the redirect page.
     *
     * @return string The OAuth access token.
     */
    public function requestAccessToken($code)
    {
        $request = new Request(
            'POST',
            static::BASE_URI . 'oauth/token',
            array(
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code'          => (string) $code,
            )
        );

        $response = $this->http->execute($request);

        if (
            !isset($response->body)
            || !isset($response->body->access_token)
        ) {
            throw new TokenMissing();
        }

        $this->accessToken = $response->body->access_token;

        return $this->accessToken;
    }

    /**
     * Executes a http request.
     *
     * @param Request $request The http request.
     *
     * @return Response The http response.
     */
    public function execute(Request $request)
    {
        $headers = $request->getHeaders();
        $headers['Authorization'] = sprintf('BEARER %s', $this->accessToken);

        $authenticatedRequest = new Request(
            $request->getMethod(),
            static::BASE_URI . $request->getEndpoint(),
            $request->getParams(),
            $headers
        );

        return $this->http->execute($authenticatedRequest);
    }

    /**
     * Returns the access token for persisting in some storage.
     *
     * @return string The OAuth access token.
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
