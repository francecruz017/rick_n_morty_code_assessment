<?php
namespace App\Dto;

class LocationReferenceDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $url
    ) {}
}