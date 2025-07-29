<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Dto\CharacterDto;
use App\Dto\EpisodeDto;
use App\Dto\EpisodeReferenceDto;
use App\Dto\LocationDto;
use App\Dto\LocationReferenceDto;

class RickAndMortyApi
{
    private const BASE_URL = 'https://rickandmortyapi.com/api';
    private HttpClientInterface $http;
    private LoggerInterface $logger;
    private CacheInterface $cache;

    public function __construct(HttpClientInterface $http, LoggerInterface $logger, CacheInterface $cache)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /** @return array{results: CharacterDto[], info: array} */
    public function getCharacters(int $page = 1): array
    {
        $data = $this->getAllByType('character', $page);
        return [
            'results' => array_map(fn($char) => $this->mapToCharacterDto($char), $data['results'] ?? []),
            'info' => $data['info'] ?? ['pages' => 1, 'next' => null, 'prev' => null],
        ];
    }

    /** @return array{results: EpisodeDto[], info: array} */
    public function getEpisodes(int $page = 1): array
    {
        $data = $this->getAllByType('episode', $page);
        return [
            'results' => array_map(fn($ep) => new EpisodeDto(
                $ep['id'],
                $ep['name'],
                $ep['air_date'],
                $ep['episode'],
                $ep['characters'],
                $ep['url'],
                $ep['created']
            ), $data['results'] ?? []),
            'info' => $data['info'] ?? ['pages' => 1, 'next' => null, 'prev' => null],
        ];
    }

    /** @return array{results: LocationDto[], info: array} */
    public function getLocations(int $page = 1): array
    {
        $data = $this->getAllByType('location', $page);
        return [
            'results' => array_map(fn($loc) => new LocationDto(
                $loc['id'],
                $loc['name'],
                $loc['type'],
                $loc['dimension'],
                $loc['residents'],
                $loc['url'],
                $loc['created']
            ), $data['results'] ?? []),
            'info' => $data['info'] ?? ['pages' => 1, 'next' => null, 'prev' => null],
        ];
    }

    /** @return array{results: array, info: array} */
    private function getAllByType(string $type, int $page): array
    {
        $url = self::BASE_URL . '/' . $type . '?page=' . $page;

        try {
            return $this->cache->get("{$type}_page_{$page}", function (ItemInterface $item) use ($url) {
                $item->expiresAfter(3600);
                return $this->http->request('GET', $url)->toArray(false);
            });
        } catch (\Throwable $e) {
            $this->logger->error("Error fetching {$type} page {$page}", ['exception' => $e]);
            return ['results' => [], 'info' => ['pages' => 1, 'next' => null, 'prev' => null]];
        }
    }

    /** @return CharacterDto[] */
    public function getCharactersByDimension(string $dimensionName): array
    {
        try {
            $response = $this->http->request('GET', self::BASE_URL . '/location?dimension=' . urlencode($dimensionName));
            $data = $response->toArray(false);
            $locations = $data['results'] ?? [];

            $characterUrls = [];
            foreach ($locations as $loc) {
                $characterUrls = array_merge($characterUrls, $loc['residents'] ?? []);
            }

            $characterIds = array_unique(self::extractIdsFromUrls($characterUrls));
            return $characterIds ? $this->fetchCharactersByIds($characterIds) : [];
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching characters by dimension', ['dimension' => $dimensionName, 'exception' => $e]);
            return [];
        }
    }

    /** @return array{location: LocationReferenceDto|string, characters: CharacterDto[]} */
    public function getCharactersByLocation(string $locationName): array
    {
        try {
            $data = $this->http->request('GET', self::BASE_URL . '/location?name=' . urlencode($locationName))->toArray(false);
            $locations = $data['results'] ?? [];

            if (!$locations) {
                return ['location' => $locationName, 'characters' => []];
            }

            $location = $locations[0];
            $residentUrls = $location['residents'] ?? [];
            $characterIds = self::extractIdsFromUrls($residentUrls);

            return [
                'location' => new LocationReferenceDto($location['name'], $location['url']),
                'characters' => $characterIds ? $this->fetchCharactersByIds($characterIds) : []
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching characters by location', ['location' => $locationName, 'exception' => $e]);
            return ['location' => $locationName, 'characters' => []];
        }
    }

    /** @return array{episode: EpisodeDto|null, characters: CharacterDto[]} */
    public function getCharactersByEpisode(string $episodeIdOrName): array
    {
        try {
            $episodeInfo = $this->cache->get("episode_{$episodeIdOrName}", function (ItemInterface $item) use ($episodeIdOrName) {
                $item->expiresAfter(3600);
                if (ctype_digit($episodeIdOrName)) {
                    return $this->http->request('GET', self::BASE_URL . '/episode/' . $episodeIdOrName)->toArray(false);
                } else {
                    $data = $this->http->request('GET', self::BASE_URL . '/episode?name=' . urlencode($episodeIdOrName))->toArray(false);
                    return $data['results'][0] ?? null;
                }
            });

            if (!$episodeInfo) {
                return ['episode' => null, 'characters' => []];
            }

            $characterIds = self::extractIdsFromUrls($episodeInfo['characters'] ?? []);

            /** @var CharacterDto[] $characters */
            $characters = $characterIds ? $this->fetchCharactersByIds($characterIds) : [];

            return [
                'episode' => new EpisodeDto(
                    $episodeInfo['id'],
                    $episodeInfo['name'],
                    $episodeInfo['air_date'],
                    $episodeInfo['episode'],
                    $episodeInfo['characters'],
                    $episodeInfo['url'],
                    $episodeInfo['created']
                ),
                'characters' => $characters
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching characters by episode', ['episode' => $episodeIdOrName, 'exception' => $e]);
            return ['episode' => null, 'characters' => []];
        }
    }

    /** @return array{character: CharacterDto|null, dimension: string|null} */
    public function getCharacterDetail(int $id): array
    {
        try {
            $charData = $this->cache->get("character_{$id}", function (ItemInterface $item) use ($id) {
                $item->expiresAfter(3600);
                return $this->http->request('GET', self::BASE_URL . "/character/{$id}")->toArray(false);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching character detail', ['character_id' => $id, 'exception' => $e]);
            return ['character' => null, 'dimension' => null];
        }

        $locationDimension = null;
        $locationUrl = $charData['location']['url'] ?? null;

        if ($locationUrl) {
            try {
                $locationDimension = $this->cache->get("location_dimension_" . md5($locationUrl), function (ItemInterface $item) use ($locationUrl) {
                    $item->expiresAfter(3600);
                    $locData = $this->http->request('GET', $locationUrl)->toArray(false);
                    return $locData['dimension'] ?? null;
                });
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to fetch location dimension', ['url' => $locationUrl, 'exception' => $e]);
            }
        }

        return [
            'character' => $this->mapToCharacterDto($charData),
            'dimension' => $locationDimension
        ];
    }

    /** @return CharacterDto[] */
    private function fetchCharactersByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $idsParam = implode(',', $ids);

        try {
            $result = $this->cache->get("characters_" . md5($idsParam), function (ItemInterface $item) use ($idsParam) {
                $item->expiresAfter(3600);
                return $this->http->request('GET', self::BASE_URL . "/character/{$idsParam}")->toArray(false);
            });

            $characters = count($ids) === 1 ? [$result] : $result;
            return array_map(fn($char) => $this->mapToCharacterDto($char), $characters);
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching characters by IDs', ['ids' => $ids, 'exception' => $e]);
            return [];
        }
    }

    /** @return int[] */
    private static function extractIdsFromUrls(array $urls): array
    {
        return array_filter(array_map(fn($url) => (int) substr(strrchr($url, '/'), 1), $urls));
    }

    private function mapToCharacterDto(array $data): CharacterDto
    {
        $episodes = array_map(fn(string $url) => new EpisodeReferenceDto($url), $data['episode']);

        return new CharacterDto(
            $data['id'],
            $data['name'],
            $data['status'],
            $data['species'],
            $data['type'],
            $data['gender'],
            new LocationReferenceDto($data['origin']['name'], $data['origin']['url']),
            new LocationReferenceDto($data['location']['name'], $data['location']['url']),
            $data['image'],
            $episodes,
            $data['url'],
            $data['created']
        );
    }
}
