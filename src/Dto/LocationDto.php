<?php
namespace App\Dto;

class LocationDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $dimension,
        public readonly array $residents,
        public readonly string $url,
        public readonly string $created
    ) {}
}