<?php

namespace App\Models;

use App\Training\RunEffort;
use Database\Factories\RunPaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunPace extends Model
{
    /** @use HasFactory<RunPaceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'effort',
        'seconds_per_kilometre',
    ];

    /**
     * @return array<string, int>
     */
    public static function secondsPerKilometre(): array
    {
        $paces = [];

        foreach (self::query()->get() as $pace) {
            $effort = $pace->effort instanceof RunEffort ? $pace->effort->value : (string) $pace->effort;
            $paces[$effort] = $pace->seconds_per_kilometre;
        }

        return $paces;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effort' => RunEffort::class,
        ];
    }
}
