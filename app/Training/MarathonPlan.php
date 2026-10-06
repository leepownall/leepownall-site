<?php

namespace App\Training;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
     *         strength: int|null
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
     *         strength: int|null
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
     *         strength: int|null
     *     }>,
     *     weeks: list<array{id: string, kind: 'prep'|'plan', week: int, label: int, phase: string, km: int, offset: int, range: string}>
     * }
     */
    private static function presentAt(int $position, CarbonInterface $today): array
    {
        $schedule = self::schedule();
        $entry = $schedule[$position];
        $start = $entry['startsOn'];
        $todayDate = CarbonImmutable::parse($today)->toDateString();
        $days = [];

        foreach (self::sessions($entry['template']) as $offset => $day) {
            $date = $start->addDays($offset);

            $days[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('l j M'),
                'isToday' => $date->toDateString() === $todayDate,
                'isRest' => isset($day['rest']),
                'session' => isset($day['name']) ? [
                    'name' => $day['name'],
                    'lines' => $day['lines'],
                ] : null,
                'strength' => ($day['strength'] ?? false) ? 30 : null,
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
