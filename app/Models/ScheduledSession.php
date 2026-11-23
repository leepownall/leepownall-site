<?php

namespace App\Models;

use Database\Factories\ScheduledSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledSession extends Model
{
    /** @use HasFactory<ScheduledSessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'kind',
        'week_index',
        'template',
        'phase',
        'is_rest',
        'name',
        'lines',
        'has_strength',
        'estimated_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'week_index' => 'integer',
            'template' => 'integer',
            'is_rest' => 'boolean',
            'lines' => 'array',
            'has_strength' => 'boolean',
            'estimated_minutes' => 'integer',
        ];
    }
}
