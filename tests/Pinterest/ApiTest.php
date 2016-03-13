<?php

/*
 * This file is part of the Pinterest PHP library.
 *
 * (c) Hans Ott <hansott@hotmail.be>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 *
 * Source: https://github.com/hansott/pinterest-php
 */

namespace Pinterest\Tests;

use Pinterest\Api;
use Pinterest\Authentication;
use Pinterest\Http\BuzzClient;
use Pinterest\Image;

class ApiTest extends TestCase
{
    /**
     * @var Api
     */
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
        $headers = $response->getHeaders();
        $this->assertEquals($response->getRateLimit(), $headers['X-Ratelimit-Limit']);
        $this->assertEquals($response->getHeader('X-Ratelimit-Limit'), $headers['X-Ratelimit-Limit']);
        $this->assertEquals($response->getRemainingRequests(), $headers['X-Ratelimit-Remaining']);
        $this->assertEquals($response->getHeader('X-Ratelimit-Remaining'), $headers['X-Ratelimit-Remaining']);

        $this->api->deletePin($response->result()->id);
    }

    public function imageProvider()
    {
        $imageFixture = __DIR__.'/fixtures/test.png';

        return array(
            array(Image::url('http://www.engagor.com/wp-content/uploads/2015/10/company-hero-3.jpg'), 'Test pin url'),
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

    public function test_it_creates_and_updates_and_deletes_a_board()
    {
        $response = $this->api->createBoard('My board!', 'A simple description');
        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertInstanceOf('Pinterest\Objects\Board', $response->result());
        $this->assertTrue($response->ok());

        $board = $response->result();
        $boardId = $board->id;
        $board->name = 'Updated My board!';

        $response = $this->api->updateBoard($board);
        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertInstanceOf('Pinterest\Objects\Board', $response->result());
        $this->assertTrue($response->ok());

        $response = $this->api->deleteBoard($boardId);
        $this->assertInstanceOf('Pinterest\Http\Response', $response);
        $this->assertTrue($response->ok());
    }
}
