<?php

namespace App\Enums\System;

enum EvolutionWhatsappTemplateTypeEnum: string
{
    case SIMPLE_TXT   = 'simple_txt';
    case IMAGE        = 'image';
    case POLL         = 'poll';
    case LIST_BUTTONS = 'list_buttons';

    public function label(): string
    {
        return match ($this) {
            self::SIMPLE_TXT   => 'Simple Text',
            self::IMAGE        => 'Image',
            self::POLL         => 'Poll',
            self::LIST_BUTTONS => 'List Buttons',
        };
    }

    public static function values(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }
}


