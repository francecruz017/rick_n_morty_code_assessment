<?php
namespace App\Dto;

class EpisodeDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $air_date,

        /** @var EpisodeReferenceDto[] */
        public readonly string $episode,

        /** @var CharacterReferenceDto[] */
        public readonly array $characters,
        
        public readonly string $url,
        public readonly string $created
    ) {}
}