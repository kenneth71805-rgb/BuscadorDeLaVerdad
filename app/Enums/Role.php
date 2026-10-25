<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case EditorInChief = 'editor_in_chief';
    case CategoryHead = 'category_head';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::EditorInChief => 'Editor-in-Chief',
            self::CategoryHead => 'Head of Category',
        };
    }
}
