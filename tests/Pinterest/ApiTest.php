<?php

namespace Pinterest\Tests;

use Pinterest\Api;
use Pinterest\Authentication;
use Pinterest\Image;
use Pinterest\Http\BuzzClient;
use Pinterest\Objects\User;

class ApiTest extends TestCase
{
    protected $api;
    protected $board;

    public function setUp()
    {
        $cacheDir = sprintf('%s/responses', __DIR__);
        $client = new BuzzClient();
        $mocked = new MockClient($client, $cacheDir);
        $auth = Authentication::withAccessToken($mocked, null, null, getenv('ACCESS_TOKEN'));
        $this->api = new Api($auth);
        $this->board = getenv('BOARD_ID');
    }

    public function testGetUser()
    {
        $this->assertUser($this->api->getUser('otthans'));
        $this->assertUser($this->api->getUser('314196648911734959'));

        $this->setExpectedException('InvalidArgumentException');
        $this->api->getUser('');
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
        $this->assertMultipleBoards($this->api->getUserInterests());
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

    /**
     * @dataProvider imageProvider
     */
    public function testCreatePin(Image $image, $note)
    {
        $response = $this->api->createPin(
            $this->board,
            $note,
            $image
        );

        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertTrue($response->ok());

        $response = $this->api->deletePin($response->result()->id);
    }

    public function imageProvider()
    {
        $imageFixture = __DIR__ . '/fixtures/test.png';

        return array(
            array(Image::url('https://wordpress-engagor.netdna-ssl.com/assets/img/hero/team.jpg'), 'Test pin url'),
            array(Image::file($imageFixture), 'Test pin file'),
            array(Image::base64(base64_encode(file_get_contents($imageFixture))), 'Test pin base64'),
        );
    }

    public function testDeletePin()
    {
        $data = $this->imageProvider();
        $createResponse = $this->api->createPin(
            $this->board,
            $data[0][1],
            $data[0][0]
        );
        $this->assertInstanceOf('Pinterest\Http\Response', $createResponse);
        $this->assertTrue($createResponse->ok());

        $pinId = $createResponse->result()->id;
        $response = $this->api->deletePin($pinId);
        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertTrue($response->ok());
    }

    public function testCreateAndDeleteBoard()
    {
        $createResponse = $this->api->createBoard('My board!', 'A simple description');
        $this->assertInstanceOf('Pinterest\Http\Response', $createResponse);
        $this->assertInstanceOf('Pinterest\Objects\Board', $createResponse->result());
        $this->assertTrue($createResponse->ok());

        $boardId = $createResponse->result()->id;
        $response = $this->api->deleteBoard($boardId);
        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertTrue($response->ok());
    }
}
