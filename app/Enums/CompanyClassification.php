<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyClassification: string
{
    case B2B = 'b2b';
    case B2C = 'b2c';
    case HYBRID = 'hybrid';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::B2B => 'B2B',
            self::B2C => 'B2C',
            self::HYBRID => 'Hybrid',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::B2B => 'B2B',
            self::B2C => 'B2C',
            self::HYBRID => 'Hybrid',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::B2B => 'blue',
            self::B2C => 'green',
            self::HYBRID => 'purple',
            self::UNKNOWN => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::B2B => 'Primarily serves other businesses with products or services',
            self::B2C => 'Directly serves individual consumers and end users',
            self::HYBRID => 'Serves both businesses and consumers with different offerings',
            self::UNKNOWN => 'Classification could not be determined from available data',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
