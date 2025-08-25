<?php

namespace App\Enums\System\Gateway;

use App\Enums\EnumTrait;

enum WhatsAppGatewayTypeEnum: string
{
    use EnumTrait;

    case NODE   = 'node';
    case CLOUD  = 'cloud';
    case EVOLUTION = 'evolution';

    /**
     * values
     *
     * @return string
     */
    public function values(): string
    {
        return match($this) {
            self::NODE   => 'Node',
            self::CLOUD  => 'cloud',
            self::EVOLUTION => 'Evolution'
        };
    }

    /**
     * badge
     *
     * @return void
     */
    public function badge(): void
    {
        $color = match($this) {
            self::NODE   => 'info',
            self::CLOUD  => 'success',
            self::EVOLUTION => 'primary',
        };

        echo "<span class='i-badge {$color}'>{$this->values()}</span>";
    }

    /**
     * getValues
     *
     * @return array
     */
    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}