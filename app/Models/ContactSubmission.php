<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    public const TYPES = [
        'general' => 'General question',
        'suggestion' => 'Suggestion',
        'correction' => 'Correction request',
        'other' => 'Other',
    ];

    protected $fillable = ['name', 'email', 'type', 'message', 'ip_hash'];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime'];
    }
}
