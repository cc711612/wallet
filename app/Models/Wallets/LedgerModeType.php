<?php

namespace App\Models\Wallets;

enum LedgerModeType: string
{
    case SINGLE_USER = 'single';
    case MULTI_USER = 'multi';
    case COUPLE = 'couple';

    /**
     * Get all available values
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the enum value
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE_USER => '單人模式',
            self::MULTI_USER => '多人模式',
            self::COUPLE => '情侶模式',
        };
    }
}
