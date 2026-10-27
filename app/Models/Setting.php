<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** Editable site settings and their validation rules. Anything else is rejected. */
    public const RULES = [
        'contact_email' => ['nullable', 'email', 'max:150'],
        'contact_address' => ['nullable', 'string', 'max:300'],
        'facebook_url' => ['nullable', 'url:http,https', 'max:300'],
        'instagram_url' => ['nullable', 'url:http,https', 'max:300'],
        'about_history' => ['nullable', 'string', 'max:5000'],
        'about_mission' => ['nullable', 'string', 'max:2000'],
        'about_vision' => ['nullable', 'string', 'max:2000'],
        'about_principles' => ['nullable', 'string', 'max:5000'],
        'assistant_enabled' => ['nullable', 'in:0,1'],
    ];

    /** @return array<string, string|null> every key in RULES, null when unset */
    public static function allValues(): array
    {
        $stored = static::query()->whereIn('key', array_keys(self::RULES))->pluck('value', 'key')->all();

        return array_merge(array_fill_keys(array_keys(self::RULES), null), $stored);
    }

    public static function put(array $values): void
    {
        foreach (array_intersect_key($values, self::RULES) as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => ($value === '' ? null : $value)]);
        }
    }
}
