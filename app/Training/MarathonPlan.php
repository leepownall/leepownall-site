<?php

namespace App\Training;

use App\Models\Activity;
use App\Models\Race;
use App\Models\RunPace;
use App\Models\ScheduledSession;
use App\Models\TrainingWeek;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * @phpstan-type Day array{name?: string|null, lines?: list<string>|null, rest?: bool, strength?: bool}
 * @phpstan-type Week array{starts_on: string, kind: 'prep'|'plan', phase: string, days: list<Day>}
 * @phpstan-type ExportedWeek array{starts_on: string, kind: 'prep'|'plan', number: int, phase: string, days: list<array{date: string, name: string|null, lines: list<string>|null, rest: bool, strength: bool}>}
 * @phpstan-type Page array{
 *     kind: 'prep'|'plan',
 *     week: int,
 *     caption: string,
 *     phase: string,
 *     range: string,
 *     current: string,
 *     previous: array{kind: 'prep'|'plan', week: int}|null,
 *     next: array{kind: 'prep'|'plan', week: int}|null,
 *     days: list<array{
 *         date: string,
 *         label: string,
 *         isToday: bool,
 *         isRest: bool,
 *         session: array{name: string, lines: list<string>}|null,
 *         strength: int|null,
 *         estimatedMinutes: int|null,
 *         estimatedPace: int|null,
 *         estimatedMetres: int|null,
 *         fixedSeconds: int,
 *         efforts: list<array{effort: string, metres: int, pace: int}>,
 *         similarRuns: list<array{name: string, minutes: int, pace: int, date: string}>,
 *         actual: list<array{name: string, metres: int, minutes: int, pace: int, url: string}>,
 *         status: 'done'|'missed'|'extra'|null,
 *         races: list<array{name: string, km: float, kind: 'target'|'race'}>,
 *     }>,
 *     targetRace: array{name: string, date: string, km: float, daysToGo: int}|null,
 *     weeks: list<array{id: string, kind: 'prep'|'plan', week: int, phase: string, km: int, offset: int, range: string, race: 'target'|'race'|null}>
 * }
 */
final class MarathonPlan
{
    public static function planLength(): int
    {
        return TrainingWeek::query()->where('kind', WeekKind::Plan)->count();
    }

    public static function prepLength(): int
    {
        return TrainingWeek::query()->where('kind', WeekKind::Prep)->count();
    }

    /**
     * The week containing today, or the nearest week before or after the
     * plan. Runs, paces, and estimates built from them are included only
     * for the owner; guests see the plan.
     *
     * @return Page|null
     */
    public static function presentFor(CarbonInterface $now, bool $includePrivate): ?array
    {
        $schedule = self::schedule();

        if ($schedule->isEmpty()) {
            return null;
        }

        $today = self::localDate($now);
        $position = $schedule->search(fn (TrainingWeek $week): bool => $today->lessThan($week->starts_on));
        $position = $position === false ? $schedule->count() - 1 : max($position - 1, 0);

        return self::presentAt($schedule, $position, $today, $includePrivate);
    }

    /**
     * @return Page|null
     */
    public static function present(WeekKind $kind, int $number, CarbonInterface $now, bool $includePrivate): ?array
    {
        $schedule = self::schedule();
        $position = $schedule->search(fn (TrainingWeek $week): bool => $week->kind === $kind && $week->number === $number);

        return $position === false ? null : self::presentAt($schedule, $position, self::localDate($now), $includePrivate);
    }

    /**
     * Replace the stored plan with these weeks. Weeks are numbered in date
     * order within their kind, so the build-up and the plan each start at 1.
     *
     * @param  list<Week>  $weeks
     */
    public static function replace(array $weeks): void
    {
        $paces = RunPace::secondsPerKilometre();
        $numbers = [WeekKind::Prep->value => 0, WeekKind::Plan->value => 0];

        usort($weeks, fn (array $a, array $b): int => strcmp($a['starts_on'], $b['starts_on']));

        (new TrainingWeek)->getConnection()->transaction(function () use ($weeks, $paces, &$numbers): void {
            ScheduledSession::query()->delete();
            TrainingWeek::query()->delete();

            foreach ($weeks as $entry) {
                $startsOn = CarbonImmutable::parse($entry['starts_on'])->startOfDay();

                $week = TrainingWeek::query()->create([
                    'starts_on' => $startsOn,
                    'kind' => $entry['kind'],
                    'number' => ++$numbers[$entry['kind']],
                    'phase' => $entry['phase'],
                ]);

                $days = $entry['days'];
                ksort($days);

                foreach (array_values($days) as $offset => $day) {
                    $name = $day['name'] ?? null;
                    $lines = $day['lines'] ?? null;

                    $week->sessions()->create([
                        'date' => $startsOn->addDays($offset),
                        'is_rest' => (bool) ($day['rest'] ?? false),
                        'name' => $name,
                        'lines' => $lines,
                        'has_strength' => (bool) ($day['strength'] ?? false),
                        'estimated_minutes' => $name === null ? null : RunDuration::minutes($name, $lines ?? [], $paces),
                    ]);
                }
            }
        });
    }

    /**
     * @return list<ExportedWeek>
     */
    public static function export(): array
    {
        return self::schedule()->map(fn (TrainingWeek $week): array => [
            'starts_on' => $week->starts_on->toDateString(),
            'kind' => $week->kind->value,
            'number' => $week->number,
            'phase' => $week->phase,
            'days' => $week->sessions->map(fn (ScheduledSession $session): array => [
                'date' => $session->date->toDateString(),
                'name' => $session->name,
                'lines' => $session->lines,
                'rest' => $session->is_rest,
                'strength' => $session->has_strength,
            ])->all(),
        ])->all();
    }

    /**
     * Re-estimate how long each stored session takes after paces change.
     */
    public static function refreshEstimates(): void
    {
        $paces = RunPace::secondsPerKilometre();

        ScheduledSession::query()
            ->whereNotNull('name')
            ->each(function (ScheduledSession $session) use ($paces): void {
                $session->update([
                    'estimated_minutes' => RunDuration::minutes((string) $session->name, $session->lines ?? [], $paces),
                ]);
            });
    }

    /**
     * Today's date where the owner runs, at midnight, so it compares with
     * the stored dates of weeks, sessions, and races.
     */
    private static function localDate(CarbonInterface $now): CarbonImmutable
    {
        return CarbonImmutable::parse(CarbonImmutable::instance($now)->setTimezone((string) config('training.timezone'))->toDateString());
    }

    /**
     * @param  EloquentCollection<int, TrainingWeek>  $schedule
     * @return Page
     */
    private static function presentAt(EloquentCollection $schedule, int $position, CarbonImmutable $today, bool $includePrivate): array
    {
        if ($includePrivate) {
            RunPaces::recompute();
        }

        $week = $schedule[$position];
        $todayDate = $today->toDateString();
        $paces = $includePrivate ? RunPace::secondsPerKilometre() : [];
        $races = Race::query()->orderBy('date')->orderBy('id')->get();
        $racesByDate = $races->groupBy(fn (Race $race): string => $race->date->toDateString());
        $target = $races->firstWhere('is_target', true);
        $recentRuns = $includePrivate ? self::recentRuns() : collect();
        $runsByDay = $includePrivate ? self::runsByDay($week) : collect();
        $days = [];

        foreach ($week->sessions as $session) {
            $date = $session->date->toDateString();
            $lines = $session->lines ?? [];
            $runs = $runsByDay->get($date, collect());
            $estimate = $includePrivate && $session->name !== null ? RunDuration::estimate($session->name, $lines, $paces) : null;
            $measurable = $estimate !== null && $estimate['metres'] >= 100;

            $days[] = [
                'date' => $date,
                'label' => $session->date->format('l j M'),
                'isToday' => $date === $todayDate,
                'isRest' => $session->is_rest,
                'session' => $session->name === null ? null : [
                    'name' => $session->name,
                    'lines' => $lines,
                ],
                'strength' => $session->has_strength ? 30 : null,
                'estimatedMinutes' => $includePrivate ? $session->estimated_minutes : null,
                'estimatedPace' => $measurable ? (int) round($estimate['seconds'] / ($estimate['metres'] / 1000)) : null,
                'estimatedMetres' => $measurable ? $estimate['metres'] : null,
                'fixedSeconds' => $estimate['fixedSeconds'] ?? 0,
                'efforts' => $estimate['efforts'] ?? [],
                'similarRuns' => $measurable ? self::similarRuns($recentRuns, $estimate['metres']) : [],
                'actual' => $runs->map(fn (Activity $run): array => [
                    'name' => $run->name,
                    'metres' => $run->distance_metres,
                    'minutes' => $run->movingMinutes(),
                    'pace' => $run->paceSecondsPerKilometre(),
                    'url' => $run->url(),
                ])->values()->all(),
                'status' => $includePrivate ? self::status($session, $runs, $todayDate)?->value : null,
                'races' => $racesByDate->get($date, collect())->map(fn (Race $race): array => [
                    'name' => $race->name,
                    'km' => $race->kilometres(),
                    'kind' => $race->kind()->value,
                ])->values()->all(),
            ];
        }

        return [
            'kind' => $week->kind->value,
            'week' => $week->number,
            'caption' => $week->kind === WeekKind::Prep ? 'build-up' : 'of '.$schedule->where('kind', WeekKind::Plan)->count(),
            'phase' => $week->phase,
            'range' => self::range($week->starts_on, $week->endsOn()),
            'current' => $week->reference(),
            'previous' => $position > 0 ? self::link($schedule[$position - 1]) : null,
            'next' => isset($schedule[$position + 1]) ? self::link($schedule[$position + 1]) : null,
            'days' => $days,
            'targetRace' => $target === null ? null : [
                'name' => $target->name,
                'date' => $target->date->format('j M Y'),
                'km' => $target->kilometres(),
                'daysToGo' => (int) $today->diffInDays($target->date, false),
            ],
            'weeks' => self::overview($schedule, $races),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $runs
     */
    private static function status(ScheduledSession $session, Collection $runs, string $todayDate): ?DayStatus
    {
        $isPlanned = $session->name !== null;

        return match (true) {
            $isPlanned && $runs->isNotEmpty() => DayStatus::Done,
            $isPlanned && $session->date->toDateString() < $todayDate => DayStatus::Missed,
            ! $isPlanned && $runs->isNotEmpty() => DayStatus::Extra,
            default => null,
        };
    }

    /**
     * @return Collection<int, Activity>
     */
    private static function recentRuns(): Collection
    {
        return Activity::query()
            ->where('started_at', '>=', now()->subMonths((int) config('training.pace_months'))->startOfDay())
            ->orderByDesc('started_at')
            ->get();
    }

    /**
     * The week's runs keyed by the date they started where the owner runs,
     * so a run at 00:30 in summer counts for that day, not the one before.
     *
     * @return Collection<string, Collection<int, Activity>>
     */
    private static function runsByDay(TrainingWeek $week): Collection
    {
        $timezone = (string) config('training.timezone');

        return Activity::query()
            ->whereBetween('started_at', [
                $week->starts_on->shiftTimezone($timezone)->utc(),
                $week->starts_on->addWeek()->shiftTimezone($timezone)->utc(),
            ])
            ->orderBy('started_at')
            ->get()
            ->groupBy(fn (Activity $activity): string => $activity->started_at->setTimezone($timezone)->toDateString());
    }

    /**
     * @return EloquentCollection<int, TrainingWeek>
     */
    private static function schedule(): EloquentCollection
    {
        return TrainingWeek::query()->with('sessions')->orderBy('starts_on')->get();
    }

    /**
     * Up to three recent runs within a tenth of the planned distance.
     *
     * @param  Collection<int, Activity>  $runs
     * @return list<array{name: string, minutes: int, pace: int, date: string}>
     */
    private static function similarRuns(Collection $runs, int $metres): array
    {
        return $runs
            ->filter(fn (Activity $run): bool => abs($run->distance_metres - $metres) / $metres <= 0.1)
            ->take(3)
            ->map(fn (Activity $run): array => [
                'name' => $run->name,
                'minutes' => $run->movingMinutes(),
                'pace' => $run->paceSecondsPerKilometre(),
                'date' => $run->started_at->format('j M'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{kind: 'prep'|'plan', week: int}
     */
    private static function link(TrainingWeek $week): array
    {
        return [
            'kind' => $week->kind->value,
            'week' => $week->number,
        ];
    }

    /**
     * @param  EloquentCollection<int, TrainingWeek>  $schedule
     * @param  EloquentCollection<int, Race>  $races
     * @return list<array{id: string, kind: 'prep'|'plan', week: int, phase: string, km: int, offset: int, range: string, race: 'target'|'race'|null}>
     */
    private static function overview(EloquentCollection $schedule, EloquentCollection $races): array
    {
        return $schedule->values()->map(function (TrainingWeek $week, int $offset) use ($races): array {
            $weekRaces = $races->filter(fn (Race $race): bool => $race->date->between($week->starts_on, $week->endsOn()));
            $marker = $weekRaces->contains('is_target', true) ? RaceKind::Target : ($weekRaces->isNotEmpty() ? RaceKind::Race : null);

            return [
                'id' => $week->reference(),
                'kind' => $week->kind->value,
                'week' => $week->number,
                'phase' => $week->phase,
                'km' => $week->sessions->sum(fn (ScheduledSession $session): int => self::distance($session->lines ?? [])),
                'offset' => $offset,
                'range' => self::range($week->starts_on, $week->endsOn()),
                'race' => $marker?->value,
            ];
        })->all();
    }

    /**
     * @param  list<string>  $lines
     */
    private static function distance(array $lines): int
    {
        if ($lines === []) {
            return 0;
        }

        if (preg_match('/^(\d+) km/', $lines[0], $match)) {
            return (int) $match[1];
        }

        return 0;
    }

    private static function range(CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($start->month === $end->month) {
            return $start->format('j').'–'.$end->format('j M Y');
        }

        return $start->format('j M').' – '.$end->format('j M Y');
    }
}
