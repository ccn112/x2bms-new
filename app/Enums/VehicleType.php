<?php

namespace App\Enums;

enum VehicleType: string
{
    case Car = 'car';
    case Motorbike = 'motorbike';
    case Bicycle = 'bicycle';

    public function label(): string
    {
        return match ($this) {
            self::Car => 'Ô tô',
            self::Motorbike => 'Xe máy',
            self::Bicycle => 'Xe đạp',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Car => 'blue',
            self::Motorbike => 'teal',
            self::Bicycle => 'slate',
        };
    }
}
