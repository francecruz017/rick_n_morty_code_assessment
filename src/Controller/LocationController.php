<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RickAndMortyApi;

class LocationController extends AbstractController
{
    private RickAndMortyApi $api;

    public function __construct(RickAndMortyApi $api)
    {
        $this->api = $api;
    }

    #[Route('/locations/{page}', name: 'locations_list', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function list(int $page): Response
    {
        $data = $this->api->getLocations($page);

        return $this->render('locations/index.html.twig', [
            'locations' => $data['results'],
            'info' => $data['info'],
            'currentPage' => $page,
        ]);
    }

    #[Route('/location/{locationName}', name: 'location_characters')]
    public function showCharactersByLocation(string $locationName): Response
    {
        $result = $this->api->getCharactersByLocation($locationName);

        return $this->render('locations/show.html.twig', [
            'location' => $result['location'],
            'characters' => $result['characters'],
        ]);
    }

    #[Route('/dimension/{dimensionName}', name: 'dimension_characters')]
    public function showCharactersByDimension(string $dimensionName): Response
    {
        $characters = $this->api->getCharactersByDimension($dimensionName);

        return $this->render('dimensions/show.html.twig', [
            'dimension' => $dimensionName,
            'characters' => $characters,
        ]);
    }
}