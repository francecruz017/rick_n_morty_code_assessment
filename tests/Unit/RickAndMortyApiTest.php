<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Service\RickAndMortyApi;
use Psr\Log\NullLogger;

class RickAndMortyApiTest extends TestCase
{
    public function testGetCharacterDetailReturnsDto(): void
    {
        $characterId = 1;
        $locationUrl = 'https://rickandmortyapi.com/api/location/20';

        $characterApiResponse = [
            'id' => $characterId,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'origin' => ['name' => 'Earth', 'url' => 'picklerick.local'],
            'location' => ['name' => 'Citadel', 'url' => $locationUrl],
            'image' => 'rick.png',
            'episode' => ['picklerick.local'],
            'url' => 'picklerick.local',
            'created' => '2017-11-04T18:48:46.250Z',
        ];

        $locationApiResponse = ['dimension' => 'Dimension C-137'];

        $http = $this->createMock(HttpClientInterface::class);
        $cache = $this->createMock(CacheInterface::class);
        $logger = new NullLogger();

        $cache->method('get')->willReturnCallback(function (string $key, callable $callback) use ($characterApiResponse, $locationApiResponse) {
            $item = $this->createMock(ItemInterface::class);
            if (str_starts_with($key, 'character_')) return $callback($item);
            if (str_starts_with($key, 'location_dimension_')) return $callback($item);
            return null;
        });

        $http->method('request')->willReturnCallback(function (string $method, string $url) use ($characterApiResponse, $locationApiResponse) {
            $response = $this->createMock(ResponseInterface::class);
            if (str_contains($url, '/character/1')) $response->method('toArray')->willReturn($characterApiResponse);
            if (str_contains($url, '/location/20')) $response->method('toArray')->willReturn($locationApiResponse);
            return $response;
        });

        $api = new RickAndMortyApi($http, $logger, $cache);
        $result = $api->getCharacterDetail($characterId);

        $this->assertEquals('Rick Sanchez', $result['character']->name);
        $this->assertEquals('Dimension C-137', $result['dimension']);
    }

    public function testGetCharactersByLocationReturnsResults(): void
    {
        $locationName = 'Citadel';
        $residentUrls = [
            'https://rickandmortyapi.com/api/character/1',
            'https://rickandmortyapi.com/api/character/2',
        ];

        $locationApiResponse = [
            'results' => [
                [
                    'name' => $locationName,
                    'url' => 'picklerick.local',
                    'residents' => $residentUrls,
                ]
            ]
        ];

        $characterApiResponse = [
            [
                'id' => 1,
                'name' => 'Rick Sanchez',
                'status' => 'Alive',
                'species' => 'Human',
                'type' => '',
                'gender' => 'Male',
                'origin' => ['name' => 'Earth', 'url' => 'picklerick.local'],
                'location' => ['name' => $locationName, 'url' => 'picklerick.local'],
                'image' => 'rick.png',
                'episode' => ['picklerick.local'],
                'url' => 'picklerick.local',
                'created' => '2017-11-04T18:48:46.250Z',
            ],
            [
                'id' => 2,
                'name' => 'Morty Smith',
                'status' => 'Alive',
                'species' => 'Human',
                'type' => '',
                'gender' => 'Male',
                'origin' => ['name' => 'Earth', 'url' => 'picklerick.local'],
                'location' => ['name' => $locationName, 'url' => 'picklerick.local'],
                'image' => 'morty.png',
                'episode' => ['picklerick.local'],
                'url' => 'picklerick.local',
                'created' => '2017-11-04T18:48:46.250Z',
            ],
        ];

        $http = $this->createMock(HttpClientInterface::class);
        $cache = $this->createMock(CacheInterface::class);
        $logger = new NullLogger();

        $cache->method('get')->willReturnCallback(
            fn($key, $callback) => $callback($this->createMock(ItemInterface::class))
        );

        $http->method('request')->willReturnCallback(function ($method, $url) use ($locationApiResponse, $characterApiResponse) {
            $response = $this->createMock(ResponseInterface::class);
            if (str_contains($url, 'location?name=')) $response->method('toArray')->willReturn($locationApiResponse);
            if (str_contains($url, 'character/1,2')) $response->method('toArray')->willReturn($characterApiResponse);
            return $response;
        });

        $api = new RickAndMortyApi($http, $logger, $cache);
        $result = $api->getCharactersByLocation($locationName);

        $this->assertEquals('Citadel', $result['location']->name);
        $this->assertCount(2, $result['characters']);
        $this->assertEquals('Rick Sanchez', $result['characters'][0]->name);
        $this->assertEquals('Morty Smith', $result['characters'][1]->name);
    }

    public function testGetCharactersByLocationReturnsEmptyIfNotFound(): void
    {
        $locationName = 'UnknownPlace';

        $http = $this->createMock(HttpClientInterface::class);
        $cache = $this->createMock(CacheInterface::class);
        $logger = new NullLogger();

        $http->method('request')->willReturnCallback(function () {
            $response = $this->createMock(ResponseInterface::class);
            $response->method('toArray')->willReturn(['results' => []]);
            return $response;
        });

        $api = new RickAndMortyApi($http, $logger, $cache);
        $result = $api->getCharactersByLocation($locationName);

        $this->assertEquals($locationName, $result['location']);
        $this->assertEmpty($result['characters']);
    }

    public function testGetCharactersByLocationHandlesApiFailure(): void
    {
        $locationName = 'GlitchCity';

        $http = $this->createMock(HttpClientInterface::class);
        $cache = $this->createMock(CacheInterface::class);
        $logger = new NullLogger();

        $http->method('request')->willThrowException(new \Exception('API failed'));

        $api = new RickAndMortyApi($http, $logger, $cache);
        $result = $api->getCharactersByLocation($locationName);

        $this->assertEquals($locationName, $result['location']);
        $this->assertEmpty($result['characters']);
    }
}
