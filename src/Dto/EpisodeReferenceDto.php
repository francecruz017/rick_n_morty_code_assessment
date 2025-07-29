<?php

namespace App\Dto;

class EpisodeReferenceDto
{
    public function __construct(
        public readonly string $url
    ) {}
}
