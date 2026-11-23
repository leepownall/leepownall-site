<?php

namespace App\Models;

use App\Training\WeekKind;
use Carbon\CarbonImmutable;
use Database\Factories\TrainingWeekFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingWeek extends Model
{
    /** @use HasFactory<TrainingWeekFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'starts_on',
        'kind',
        'number',
        'phase',
    ];

    /**
     * @return HasMany<ScheduledSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ScheduledSession::class)->orderBy('date');
    }

    public function endsOn(): CarbonImmutable
    {
        return $this->starts_on->addDays(6);
    }

    /**
     * Identifies the week across both kinds, such as "prep-3" or "plan-1".
     */
    public function reference(): string
    {
        return $this->kind->value.'-'.$this->number;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'kind' => WeekKind::class,
            'number' => 'integer',
        ];
    }
}
