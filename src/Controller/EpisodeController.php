<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RickAndMortyApi;

class EpisodeController extends AbstractController
{
    private RickAndMortyApi $api;

    public function __construct(RickAndMortyApi $api)
    {
        $this->api = $api;
    }

    #[Route('/episodes/{page}', name: 'episodes_list', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function list(int $page): Response
    {
        $data = $this->api->getEpisodes($page);

        return $this->render('episodes/index.html.twig', [
            'episodes' => $data['results'],
            'info' => $data['info'],
            'currentPage' => $page,
        ]);
    }

    #[Route('/episode/{episodeIdOrName}', name: 'episode_characters')]
    public function charactersInEpisode(string $episodeIdOrName): Response
    {
        $result = $this->api->getCharactersByEpisode($episodeIdOrName);

        return $this->render('episodes/characters.html.twig', [
            'episode' => $result['episode'],
            'characters' => $result['characters'],
        ]);
    }
}