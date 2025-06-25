<?php

declare(strict_types=1);

namespace App\Enums;

enum ClassificationMethod: string
{
    case AUTOMATED = 'automated';
    case MANUAL = 'manual';
    case AI_VERIFIED = 'ai_verified';

    public function label(): string
    {
        return match ($this) {
            self::AUTOMATED => 'Automated',
            self::MANUAL => 'Manual',
            self::AI_VERIFIED => 'AI Verified',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AUTOMATED => 'Classification performed automatically by AI',
            self::MANUAL => 'Classification performed manually by user',
            self::AI_VERIFIED => 'Automated classification verified by AI',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AUTOMATED => 'blue',
            self::MANUAL => 'green',
            self::AI_VERIFIED => 'purple',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
