<?php

namespace App\Models;

use App\Training\RaceKind;
use Database\Factories\RaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    /** @use HasFactory<RaceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'date',
        'distance_metres',
        'is_target',
    ];

    public function kind(): RaceKind
    {
        return $this->is_target ? RaceKind::Target : RaceKind::Race;
    }

    public function kilometres(): float
    {
        return round($this->distance_metres / 1000, 1);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'distance_metres' => 'integer',
            'is_target' => 'boolean',
        ];
    }
}
