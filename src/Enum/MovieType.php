<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Enum;

enum MovieType: string
{
    case Film = 'movie';
    case Serie = 'series';
    case Episode = 'episode';

    public function label(): string
    {
        return $this->name;
    }
}
