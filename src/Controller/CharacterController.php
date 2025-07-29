<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RickAndMortyApi;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CharacterController extends AbstractController
{
    private RickAndMortyApi $api;

    public function __construct(RickAndMortyApi $api)
    {
        $this->api = $api;
    }

    #[Route('/characters/{page}', name: 'characters_list', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function list(int $page): Response
    {
        $data = $this->api->getCharacters($page);

        return $this->render('characters/index.html.twig', [
            'characters' => $data['results'],
            'info' => $data['info'],
            'currentPage' => $page,
        ]);
    }

    #[Route('/character/{id}', name: 'character_show')]
    public function show(int $id): Response
    {
        $result = $this->api->getCharacterDetail($id);

        if ($result['character'] === null) {
            throw new NotFoundHttpException("Character not found.");
        }

        return $this->render('characters/show.html.twig', [
            'character' => $result['character'],
            'dimension' => $result['dimension'],
        ]);
    }
}