<?php

namespace App\Enums;

enum DegreeLevel: string
{
    case D3 = 'D3';
    case D4 = 'D4';

    /**
     * Total semester untuk jenjang ini (PRD §2.1) — D3 = 6, D4 = 8.
     */
    public function totalSemesters(): int
    {
        return match ($this) {
            self::D3 => 6,
            self::D4 => 8,
        };
    }
}
