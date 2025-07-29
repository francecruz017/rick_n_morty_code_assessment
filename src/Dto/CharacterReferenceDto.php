<?php

namespace App\Dto;

class CharacterReferenceDto
{
    public function __construct(
        public readonly string $url
    ) {}
}