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
        'training_week_id',
        'date',
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
            'date' => 'immutable_date',
            'is_rest' => 'boolean',
            'lines' => 'array',
            'has_strength' => 'boolean',
            'estimated_minutes' => 'integer',
        ];
    }
}
