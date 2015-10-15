<?php

use Pinterest\Api;
use Pinterest\Authentication;
use Pinterest\Image;
use Pinterest\Http\BuzzClient;
use Pinterest\Objects\User;

class ApiTest extends TestCase
{
    protected $api;

    public function setUp()
    {
        $client = new BuzzClient();
        $auth = Authentication::withAccessToken($client, null, null, getenv('ACCESS_TOKEN'));
        $this->api = new Api($auth);
    }

    public function testGetUser()
    {
        $this->assertUser($this->api->getUser('otthans'));
        $this->assertUser($this->api->getUser('314196648911734959'));
    }

    public function testGetBoard()
    {
        $this->assertBoard($this->api->getBoard('314196580192594085'));
    }

    public function testGetUserBoards()
    {
        $this->assertMultipleBoards($this->api->getUserBoards());
    }

    public function testGetUserLikes()
    {
        $this->assertMultiplePins($this->api->getUserLikes());
    }

    public function testGetUserPins()
    {
        $this->assertMultiplePins($this->api->getUserPins());
    }

    public function testGetCurrentUser()
    {
        $this->assertUser($this->api->getCurrentUser());
    }

    public function testGetUserFollowers()
    {
        $this->assertMultipleUsers($this->api->getUserFollowers());
    }

    public function testGetUserFollowingBoards()
    {
        $this->assertMultipleBoards($this->api->getUserFollowingBoards());
    }

    public function testGetUserFollowing()
    {
        $this->assertMultipleUsers($this->api->getUserFollowing());
    }

    public function testGetUserInterests()
    {
        $this->assertMultiplePins($this->api->getUserInterests());
    }

    public function testFollowUser()
    {
        $username = 'engagor';
        $response = $this->api->followUser($username);
        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertTrue($response->ok());

        $this->setExpectedException('InvalidArgumentException');
        $username = '';
        $this->api->followUser($username);
    }

    public function testCreatePin()
    {
        $response = $this->api->createPin(
            7670330554511789,
            'Test Pin',
            // Image::url('https://wordpress-engagor.netdna-ssl.com/assets/img/hero/team.jpg')
            Image::file('/Users/Toon/Desktop/test.png')
        );

        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertTrue($response->ok());
    }
}
