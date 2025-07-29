<?php
namespace App\Dto;

use App\Dto\LocationReferenceDto;

class CharacterDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $species,
        public readonly string $type,
        public readonly string $gender,
        public readonly LocationReferenceDto $origin,
        public readonly LocationReferenceDto $location,
        public readonly string $image,

        /** @var EpisodeReferenceDto[] */
        public readonly array $episodes,
        
        public readonly string $url,
        public readonly string $created
    ) {}
}