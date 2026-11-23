<?php

namespace App\Training;

use App\Models\Activity;
use App\Models\RunPace;
use App\Models\ScheduledSession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class MarathonPlan
{
    public const int LENGTH = 15;

    public static function startsOn(): CarbonImmutable
    {
        return CarbonImmutable::parse('2027-01-11')->startOfDay();
    }

    public static function prepLength(): int
    {
        return count(array_filter(
            self::schedule(),
            fn (array $entry): bool => $entry['kind'] === 'prep',
        ));
    }

    public static function presentFor(CarbonInterface $today): array
    {
        return self::presentAt(self::positionOn($today), $today);
    }

    /**
     * @return array{
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
     *     }>,
     *     weeks: list<array{id: string, kind: 'prep'|'plan', week: int, label: int, phase: string, km: int, offset: int, range: string}>
     * }
     */
    public static function present(int $week, CarbonInterface $today): array
    {
        $position = self::positionFor('plan', $week);

        if ($position === null) {
            throw new InvalidArgumentException("Week [{$week}] is outside the plan.");
        }

        return self::presentAt($position, $today);
    }

    /**
     * @return array{
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
     *     }>,
     *     weeks: list<array{id: string, kind: 'prep'|'plan', week: int, label: int, phase: string, km: int, offset: int, range: string}>
     * }
     */
    public static function presentPrep(int $prep, CarbonInterface $today): array
    {
        $position = self::positionFor('prep', $prep);

        if ($position === null) {
            throw new InvalidArgumentException("Prep week [{$prep}] is outside the build-up.");
        }

        return self::presentAt($position, $today);
    }

    /**
     * @param  list<array{kind: 'prep'|'plan', index: int, template: int, startsOn: CarbonImmutable, phase: string}>  $schedule
     * @return array{
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
     *     }>,
     *     weeks: list<array{id: string, kind: 'prep'|'plan', week: int, label: int, phase: string, km: int, offset: int, range: string}>
     * }
     */
    private static function presentAt(int $position, CarbonInterface $today): array
    {
        RunPaces::recompute();

        $schedule = self::schedule();
        $entry = $schedule[$position];
        $start = $entry['startsOn'];
        $todayDate = CarbonImmutable::parse($today)->toDateString();
        $paces = RunPace::secondsPerKilometre();
        $recentRuns = Activity::query()
            ->where('started_at', '>=', now()->subMonths((int) config('training.pace_months'))->startOfDay())
            ->orderByDesc('started_at')
            ->get();
        $days = [];

        $stored = ScheduledSession::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $start->addDays(6)->toDateString())
            ->orderBy('date')
            ->get();

        foreach ($stored as $session) {
            $lines = $session->lines ?? [];
            $estimate = $session->name === null ? null : RunDuration::estimate($session->name, $lines, $paces);

            $days[] = [
                'date' => $session->date->toDateString(),
                'label' => $session->date->format('l j M'),
                'isToday' => $session->date->toDateString() === $todayDate,
                'isRest' => $session->is_rest,
                'session' => $session->name === null ? null : [
                    'name' => $session->name,
                    'lines' => $lines,
                ],
                'strength' => $session->has_strength ? 30 : null,
                'estimatedMinutes' => $session->estimated_minutes,
                'estimatedPace' => $estimate === null || $estimate['metres'] < 100
                    ? null
                    : (int) round($estimate['seconds'] / ($estimate['metres'] / 1000)),
                'estimatedMetres' => $estimate === null || $estimate['metres'] < 100
                    ? null
                    : $estimate['metres'],
                'fixedSeconds' => $estimate['fixedSeconds'] ?? 0,
                'efforts' => $estimate['efforts'] ?? [],
                'similarRuns' => $estimate === null ? [] : self::similarRuns($recentRuns, $estimate['metres']),
            ];
        }

        return [
            'kind' => $entry['kind'],
            'week' => $entry['kind'] === 'prep' ? $entry['template'] : $entry['index'],
            'caption' => $entry['kind'] === 'prep' ? 'build-up' : 'of '.self::LENGTH,
            'phase' => $entry['phase'],
            'range' => self::range($start, $start->addDays(6)),
            'current' => $entry['kind'].'-'.$entry['index'],
            'previous' => $position > 0 ? self::target($schedule[$position - 1]) : null,
            'next' => isset($schedule[$position + 1]) ? self::target($schedule[$position + 1]) : null,
            'days' => $days,
            'weeks' => self::overview($schedule),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $runs
     * @return list<array{name: string, minutes: int, pace: int, date: string}>
     */
    private static function similarRuns(Collection $runs, int $metres): array
    {
        if ($metres < 100) {
            return [];
        }

        $matches = [];

        foreach ($runs as $run) {
            if (abs($run->distance_metres - $metres) / $metres > 0.1) {
                continue;
            }

            $matches[] = [
                'name' => $run->name,
                'minutes' => (int) round($run->moving_time_seconds / 60),
                'pace' => (int) round($run->moving_time_seconds / ($run->distance_metres / 1000)),
                'date' => $run->started_at->format('j M'),
            ];

            if (count($matches) === 3) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @param  array{kind: 'prep'|'plan', index: int}  $entry
     * @return array{kind: 'prep'|'plan', week: int}
     */
    private static function target(array $entry): array
    {
        return [
            'kind' => $entry['kind'],
            'week' => $entry['index'],
        ];
    }

    private static function positionOn(CarbonInterface $date): int
    {
        $day = CarbonImmutable::parse($date)->startOfDay();

        foreach (self::schedule() as $position => $entry) {
            if ($day->lessThan($entry['startsOn'])) {
                return max($position - 1, 0);
            }
        }

        return count(self::schedule()) - 1;
    }

    private static function positionFor(string $kind, int $index): ?int
    {
        foreach (self::schedule() as $position => $entry) {
            if ($entry['kind'] === $kind && $entry['index'] === $index) {
                return $position;
            }
        }

        return null;
    }

    public static function sync(): void
    {
        $paces = RunPace::secondsPerKilometre();
        $rows = [];
        $now = now()->toDateTimeString();

        foreach (self::schedule() as $entry) {
            foreach (self::sessions($entry['template']) as $offset => $day) {
                $name = $day['name'] ?? null;
                $lines = $day['lines'] ?? null;

                $rows[] = [
                    'date' => $entry['startsOn']->addDays($offset)->toDateString(),
                    'kind' => $entry['kind'],
                    'week_index' => $entry['index'],
                    'template' => $entry['template'],
                    'phase' => $entry['phase'],
                    'is_rest' => isset($day['rest']),
                    'name' => $name,
                    'lines' => $lines === null ? null : json_encode($lines),
                    'has_strength' => (bool) ($day['strength'] ?? false),
                    'estimated_minutes' => $name === null ? null : RunDuration::minutes($name, $lines ?? [], $paces),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        ScheduledSession::query()->upsert($rows, ['date'], [
            'kind',
            'week_index',
            'template',
            'phase',
            'is_rest',
            'name',
            'lines',
            'has_strength',
            'estimated_minutes',
            'updated_at',
        ]);
    }

    /**
     * @return list<array{kind: 'prep'|'plan', index: int, template: int, startsOn: CarbonImmutable, phase: string}>
     */
    private static function schedule(): array
    {
        $planStart = self::startsOn();
        $cursor = CarbonImmutable::parse('2026-10-05')->startOfDay();
        $rotation = 2;
        $prep = 1;
        $entries = [];

        while ($cursor->lessThan($planStart)) {
            $entries[] = [
                'kind' => 'prep',
                'index' => $prep,
                'template' => $cursor->addWeek()->equalTo($planStart) ? 1 : $rotation,
                'startsOn' => $cursor,
                'phase' => 'Build-up',
            ];

            $cursor = $cursor->addWeek();
            $rotation = match ($rotation) {
                1 => 2,
                2 => 3,
                default => 1,
            };
            $prep++;
        }

        foreach (self::weeks() as $number => $plan) {
            $entries[] = [
                'kind' => 'plan',
                'index' => $number,
                'template' => $number,
                'startsOn' => $planStart->addWeeks($number - 1),
                'phase' => $plan['phase'],
            ];
        }

        return $entries;
    }

    /**
     * @return list<array{name?: string, lines?: list<string>, rest?: true, strength?: true}>
     */
    private static function sessions(int $template): array
    {
        return self::weeks()[$template]['days'];
    }

    /**
     * @param  list<array{kind: 'prep'|'plan', index: int, template: int, startsOn: CarbonImmutable, phase: string}>  $schedule
     * @return list<array{id: string, kind: 'prep'|'plan', week: int, label: int, phase: string, km: int, offset: int, range: string}>
     */
    private static function overview(array $schedule): array
    {
        $weeks = [];

        foreach ($schedule as $offset => $entry) {
            $km = 0;

            foreach (self::sessions($entry['template']) as $day) {
                if (isset($day['lines'])) {
                    $km += self::distance($day['lines']);
                }
            }

            $weeks[] = [
                'id' => $entry['kind'].'-'.$entry['index'],
                'kind' => $entry['kind'],
                'week' => $entry['index'],
                'label' => $entry['template'],
                'phase' => $entry['phase'],
                'km' => $km,
                'offset' => $offset,
                'range' => self::range($entry['startsOn'], $entry['startsOn']->addDays(6)),
            ];
        }

        return $weeks;
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

    /**
     * @param  list<string>  $lines
     * @return array{name: string, lines: list<string>}
     */
    private static function run(string $name, string ...$lines): array
    {
        return [
            'name' => $name,
            'lines' => $lines,
        ];
    }

    /**
     * @return array{rest: true}
     */
    private static function rest(): array
    {
        return ['rest' => true];
    }

    /**
     * @return array{strength: true}
     */
    private static function strength(): array
    {
        return ['strength' => true];
    }

    /**
     * @param  array{name: string, lines: list<string>}  $run
     * @return array{name: string, lines: list<string>, strength: true}
     */
    private static function withStrength(array $run): array
    {
        return [
            'name' => $run['name'],
            'lines' => $run['lines'],
            'strength' => true,
        ];
    }

    /**
     * @return array<int, array{phase: string, days: list<array{name: string, lines: list<string>}|array{name: string, lines: list<string>, strength: true}|array{rest: true}|array{strength: true}>}>
     */
    private static function weeks(): array
    {
        return [
            1 => [
                'phase' => 'Prep',
                'days' => [
                    self::run('Easy run', '6 km'),
                    self::rest(),
                    self::run('Tempo run', '7 km', '2 km easy', '3 km tempo', '2 km easy'),
                    self::strength(),
                    self::rest(),
                    self::run('Long run', '13 km easy'),
                    self::rest(),
                ],
            ],
            2 => [
                'phase' => 'Prep',
                'days' => [
                    self::run('Easy run', '6 km'),
                    self::rest(),
                    self::run('Progression', '7 km', '2 km easy', '2 km marathon pace', '1 km tempo', '2 km easy'),
                    self::strength(),
                    self::rest(),
                    self::run('Long run', '16 km', '6 km easy', '5 km marathon pace', '5 km easy'),
                    self::rest(),
                ],
            ],
            3 => [
                'phase' => 'Prep',
                'days' => [
                    self::run('Easy run and strides', '6 km', '4 × 20s strides'),
                    self::rest(),
                    self::run('Speed session', '10 minutes easy', '5 × 3 minutes interval pace', '2 minutes walk', '10 minutes easy'),
                    self::strength(),
                    self::rest(),
                    self::run('Long run', '19 km', '5 km easy', '3 km marathon pace', '5 km easy', '3 km marathon pace', '3 km easy'),
                    self::rest(),
                ],
            ],
            4 => [
                'phase' => 'Recovery',
                'days' => [
                    self::run('Easy run and strides', '5 km', '4 × 20s strides'),
                    self::rest(),
                    self::run('Tempo run', '6 km', '2 km easy', '3 km tempo', '1 km easy'),
                    self::strength(),
                    self::rest(),
                    self::run('Easy run', '5 km'),
                    self::run('Stabridge Stagger', '16 km'),
                ],
            ],
            5 => [
                'phase' => 'Build',
                'days' => [
                    self::run('Easy run', '6 km'),
                    self::rest(),
                    self::run('Progression', '6 km', '2 km easy', '2 km marathon pace', '1 km tempo', '1 km easy'),
                    self::strength(),
                    self::rest(),
                    self::run('Long run', '23 km', '16 km easy', '5 km marathon pace', '2 km easy'),
                    self::rest(),
                ],
            ],
            6 => [
                'phase' => 'Build',
                'days' => [
                    self::run('Easy run and strides', '6 km', '4 × 20s strides'),
                    self::rest(),
                    self::run('Speed session', '10 minutes easy', '6 × 3 minutes interval pace', '2 minutes walk', '10 minutes easy'),
                    self::strength(),
                    self::rest(),
                    self::run('Long run', '26 km', '6 km easy', '4 km marathon pace', '4 km easy', '3 km marathon pace', '3 km easy', '3 km marathon pace', '3 km easy'),
                    self::rest(),
                ],
            ],
            7 => [
                'phase' => 'Build',
                'days' => [
                    self::withStrength(self::run('Easy run', '5 km')),
                    self::rest(),
                    self::run('Tempo run', '6 km', '2 km easy', '3 km tempo', '1 km easy'),
                    self::run('Easy run and strides', '5 km', '4 × 20s strides'),
                    self::rest(),
                    self::run('Long run', '29 km', '7 km easy', '5 km marathon pace', '6 km easy', '5 km marathon pace', '6 km easy'),
                    self::rest(),
                ],
            ],
            8 => [
                'phase' => 'Recovery',
                'days' => [
                    self::run('Easy run and strides', '8 km', '4 × 20s strides'),
                    self::rest(),
                    self::run('Speed session', '10 minutes easy', '5 × 800 m interval pace', '2 minutes walk', '10 minutes easy'),
                    self::strength(),
                    self::rest(),
                    self::run('Long run', '19 km easy'),
                    self::rest(),
                ],
            ],
            9 => [
                'phase' => 'Peak',
                'days' => [
                    self::withStrength(self::run('Easy run', '5 km')),
                    self::rest(),
                    self::run('Progression', '10 km', '3 km easy', '3 km marathon pace', '2 km tempo', '2 km easy'),
                    self::run('Easy run', '6 km'),
                    self::rest(),
                    self::run('Long run', '29 km', '16 km easy', '10 km marathon pace', '3 km easy'),
                    self::rest(),
                ],
            ],
            10 => [
                'phase' => 'Peak',
                'days' => [
                    self::run('Easy run and strides', '8 km', '5 × 20s strides'),
                    self::rest(),
                    self::run('Speed session', '10 minutes easy', '10 × 2 minutes interval pace', '2 minutes walk', '10 minutes easy'),
                    self::withStrength(self::run('Easy run', '6 km')),
                    self::rest(),
                    self::run('Long run', '32 km easy'),
                    self::rest(),
                ],
            ],
            11 => [
                'phase' => 'Peak',
                'days' => [
                    self::withStrength(self::run('Easy run', '10 km')),
                    self::rest(),
                    self::run('Tempo run', '10 km', '4 km easy', '3 km tempo', '3 km easy'),
                    self::run('Easy run', '10 km'),
                    self::rest(),
                    self::run('Long run', '32 km', '7 km easy', '7 km marathon pace', '6 km easy', '6 km marathon pace', '6 km easy'),
                    self::rest(),
                ],
            ],
            12 => [
                'phase' => 'Peak',
                'days' => [
                    self::run('Easy run and strides', '10 km', '6 × 20s strides'),
                    self::rest(),
                    self::run('Speed session', '15 minutes easy', '3 × 8 minutes tempo', '2 minutes walk', '15 minutes easy'),
                    self::withStrength(self::run('Easy run', '10 km')),
                    self::rest(),
                    self::run('Long run', '35 km easy'),
                    self::rest(),
                ],
            ],
            13 => [
                'phase' => 'Taper',
                'days' => [
                    self::withStrength(self::run('Easy run', '8 km')),
                    self::rest(),
                    self::run('Progression', '10 km', '3 km easy', '3 km marathon pace', '2 km tempo', '2 km easy'),
                    self::run('Easy run', '10 km'),
                    self::rest(),
                    self::run('Long run', '27 km', '8 km easy', '11 km marathon pace', '8 km easy'),
                    self::rest(),
                ],
            ],
            14 => [
                'phase' => 'Taper',
                'days' => [
                    self::run('Easy run and strides', '8 km', '5 × 20s strides'),
                    self::rest(),
                    self::run('Speed session', '15 minutes easy', '5 × 800 m interval pace', '2 minutes walk', '15 minutes easy'),
                    self::withStrength(self::run('Easy run', '10 km')),
                    self::rest(),
                    self::run('Long run', '18 km easy'),
                    self::rest(),
                ],
            ],
            15 => [
                'phase' => 'Race',
                'days' => [
                    self::run('Easy run', '5 km'),
                    self::rest(),
                    self::run('Speed session', '10 minutes easy', '4 × 5 minutes marathon pace', '2 minutes walk', '10 minutes easy'),
                    self::rest(),
                    self::run('Easy run', '5 km'),
                    self::rest(),
                    self::run('Race day'),
                ],
            ],
        ];
    }
}
