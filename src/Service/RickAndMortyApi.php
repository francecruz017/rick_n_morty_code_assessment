<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RickAndMortyApi
{
    private const BASE_URL = 'https://rickandmortyapi.com/api';
    private HttpClientInterface $http;

    public function __construct(HttpClientInterface $http)
    {
        $this->http = $http;
    }

    /**
     * @param string $dimensionName
     * @return array
     */
    public function getCharactersByDimension(string $dimensionName): array
    {
        try {
            $response = $this->http->request('GET', self::BASE_URL . '/location?dimension=' . urlencode($dimensionName));
            $data = $response->toArray(false);
            $locations = $data['results'] ?? [];

            $characterUrls = [];
            foreach ($locations as $loc) {
                if (!empty($loc['residents'])) {
                    $characterUrls = array_merge($characterUrls, $loc['residents']);
                }
            }

            $characterIds = array_unique(array_filter(array_map(function($url) {
                $parts = explode('/', $url);
                return end($parts);
            }, $characterUrls)));

            if (!$characterIds) {
                return [];
            }

            $idsParam = implode(',', $characterIds);
            $characters = $this->fetchCharactersByIds($idsParam);

            return $characters;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param string $locationName
     * @return array ['location' => string, 'characters' => array]
     */
    public function getCharactersByLocation(string $locationName): array
    {
        try {
            $response = $this->http->request('GET', self::BASE_URL . '/location?name=' . urlencode($locationName));
            $data = $response->toArray(false);
            $locations = $data['results'] ?? [];

            if (!$locations) {
                return ['location' => $locationName, 'characters' => []];
            }

            $location = $locations[0];
            $residentUrls = $location['residents'] ?? [];
            $ids = array_filter(array_map(function($url) {
                return (int) substr(strrchr($url, '/'), 1);
            }, $residentUrls));

            if (!$ids) {
                return ['location' => $location['name'], 'characters' => []];
            }

            $idsParam = implode(',', $ids);
            $characters = $this->fetchCharactersByIds($idsParam);

            return [
                'location' => $location['name'],
                'characters' => $characters
            ];
        } catch (\Throwable $e) {
            return ['location' => $locationName, 'characters' => []];
        }
    }

    /**
     * @param string $episodeIdOrName
     * @return array ['episode' => ?array, 'characters' => array]
     */
    public function getCharactersByEpisode(string $episodeIdOrName): array
    {
        try {
            $episodeInfo = null;
            if (ctype_digit($episodeIdOrName)) {
                $episodeInfo = $this->http->request('GET', self::BASE_URL . '/episode/' . $episodeIdOrName)->toArray(false);
            } else {
                $url = self::BASE_URL . '/episode?name=' . urlencode($episodeIdOrName);
                $data = $this->http->request('GET', $url)->toArray(false);
                if (!empty($data['results'])) {
                    $episodeInfo = $data['results'][0];
                }
            }
            if (!$episodeInfo) {
                return ['episode' => null, 'characters' => []];
            }

            $charUrls = $episodeInfo['characters'] ?? [];
            $ids = array_filter(array_map(function($url) {
                return (int) substr(strrchr($url, '/'), 1);
            }, $charUrls));

            if (!$ids) {
                return ['episode' => $episodeInfo, 'characters' => []];
            }

            $idsParam = implode(',', $ids);
            $characters = $this->fetchCharactersByIds($idsParam);

            return ['episode' => $episodeInfo, 'characters' => $characters];
        } catch (\Throwable $e) {
            return ['episode' => null, 'characters' => []];
        }
    }

    /**
     * @param int $id
     * @return array ['character' => ?array, 'dimension' => ?string]
     */
    public function getCharacterDetail(int $id): array
    {
        try {
            $charData = $this->http->request('GET', self::BASE_URL . "/character/{$id}")->toArray(false);
        } catch (\Throwable $e) {
            return ['character' => null, 'dimension' => null];
        }

        $locationDimension = null;
        if ($charData) {
            $locationUrl = $charData['location']['url'] ?? null;
            if ($locationUrl) {
                try {
                    $locData = $this->http->request('GET', $locationUrl)->toArray(false);
                    $locationDimension = $locData['dimension'] ?? null;
                } catch (\Throwable $e) {
                    // ignore, dimension stays null
                }
            }
        }

        return [
            'character' => $charData,
            'dimension' => $locationDimension
        ];
    }

    /**
     * Fetch multiple characters by their IDs (helper)
     * Handles both single and multiple characters as per API spec
     *
     * @param string $idsParam
     * @return array
     */
    private function fetchCharactersByIds(string $idsParam): array
    {
        try {
            $result = $this->http->request('GET', self::BASE_URL . "/character/{$idsParam}")->toArray(false);
            // API returns a single object for one character, or array for multiple
            return is_assoc($result) ? [$result] : $result;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

/**
 * Helper: checks if an array is associative
 * @param array $arr
 * @return bool
 */
function is_assoc(array $arr): bool
{
    if ([] === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}