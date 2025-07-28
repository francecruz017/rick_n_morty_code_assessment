<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RickAndMortyApi;

class RickAndMortyController extends AbstractController
{
    private RickAndMortyApi $api;

    public function __construct(RickAndMortyApi $api)
    {
        $this->api = $api;
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route("/dimension/{dimensionName}", name: "app_dimension")]
    public function charactersByDimension(string $dimensionName): Response
    {
        $characters = $this->api->getCharactersByDimension($dimensionName);

        return $this->render('characters_by_dimension.html.twig', [
            'dimension'  => $dimensionName,
            'characters' => $characters
        ]);
    }

    #[Route("/location/{locationName}", name: "app_location")]
    public function charactersByLocation(string $locationName): Response
    {
        $result = $this->api->getCharactersByLocation($locationName);

        return $this->render('characters_by_location.html.twig', [
            'location'   => $result['location'],
            'characters' => $result['characters']
        ]);
    }

    #[Route("/episode/{episodeIdOrName}", name: "app_episode")]
    public function charactersByEpisode(string $episodeIdOrName): Response
    {
        $result = $this->api->getCharactersByEpisode($episodeIdOrName);

        return $this->render('characters_by_episode.html.twig', [
            'episode'    => $result['episode'],
            'characters' => $result['characters']
        ]);
    }

    #[Route("/character/{id}", name: "app_character_detail")]
    public function characterDetail(int $id): Response
    {
        $result = $this->api->getCharacterDetail($id);

        return $this->render('character_detail.html.twig', [
            'character' => $result['character'],
            'dimension' => $result['dimension']
        ]);
    }
}