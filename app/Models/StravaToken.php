<?php

namespace App\Models;

use Database\Factories\StravaTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StravaToken extends Model
{
    /** @use HasFactory<StravaTokenFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'athlete_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Store the tokens from a Strava token response, which also arrive when
     * a refresh rotates them.
     *
     * @param  array{access_token: string, refresh_token: string, expires_at: int}  $response
     */
    public static function store(int $athleteId, array $response): self
    {
        return self::query()->updateOrCreate(['athlete_id' => $athleteId], [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'expires_at' => $response['expires_at'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'athlete_id' => 'integer',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }
}
