import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import Layout from '@/Layout';
import { cn } from '@/lib/utils';
import { training } from '@/routes';
import { prep } from '@/routes/training';

type Session = {
    name: string;
    lines: string[];
};

type TrainingDay = {
    date: string;
    label: string;
    isToday: boolean;
    isRest: boolean;
    session: Session | null;
    strength: number | null;
};

type WeekTarget = {
    kind: 'prep' | 'plan';
    week: number;
};

type PlanWeek = {
    id: string;
    kind: 'prep' | 'plan';
    week: number;
    label: number;
    phase: string;
    km: number;
    offset: number;
    range: string;
};

type TrainingProps = {
    week: number;
    caption: string;
    phase: string;
    range: string;
    current: string;
    previous: WeekTarget | null;
    next: WeekTarget | null;
    days: TrainingDay[];
    weeks: PlanWeek[];
};

type SegmentType = 'easy' | 'marathon pace' | 'tempo' | 'interval pace' | 'walk';

type Segment = {
    type: SegmentType;
    weight: number;
    km: number | null;
    label: string;
};

type ParsedSession = {
    segments: Segment[];
    total: number | null;
    strides: string | null;
    reps: { value: string; unit: string } | null;
    showList: boolean;
};

const MINUTES_PER_KM = 5.5;

const SEGMENT_STYLES: Record<SegmentType, { label: string; color: string }> = {
    easy: { label: 'Easy', color: 'bg-[oklch(0.72_0.15_165)]' },
    'marathon pace': { label: 'Marathon pace', color: 'bg-[oklch(0.72_0.15_250)]' },
    tempo: { label: 'Tempo', color: 'bg-[oklch(0.72_0.15_60)]' },
    'interval pace': { label: 'Interval', color: 'bg-[oklch(0.72_0.15_15)]' },
    walk: { label: 'Walk', color: 'bg-[oklch(0.9_0_0)]' },
};

function segmentType(value: string): SegmentType {
    return value in SEGMENT_STYLES ? (value as SegmentType) : 'easy';
}

function parseSession(lines: string[]): ParsedSession {
    const segments: Segment[] = [];
    let total: number | null = null;
    let strides: string | null = null;
    let reps: ParsedSession['reps'] = null;

    for (const line of lines) {
        if (/strides$/.test(line)) {
            strides = line;
            continue;
        }

        const repeat = line.match(/^(\d+) × (\d+) ?(minutes|m) (.+)$/);

        if (repeat) {
            const count = Number(repeat[1]);
            const value = Number(repeat[2]);
            const isMetres = repeat[3] === 'm';

            segments.push({
                type: segmentType(repeat[4]),
                weight: isMetres ? ((count * value) / 1000) * MINUTES_PER_KM : count * value,
                km: null,
                label: line,
            });
            reps = { value: `${count} × ${value}`, unit: isMetres ? 'm' : 'min' };
            continue;
        }

        const single = line.match(/^(\d+) (km|minutes)(?: (.+))?$/);

        if (single) {
            const value = Number(single[1]);

            if (!single[3]) {
                total = value;
                continue;
            }

            segments.push({
                type: segmentType(single[3]),
                weight: single[2] === 'km' ? value * MINUTES_PER_KM : value,
                km: single[2] === 'km' ? value : null,
                label: line,
            });
        }
    }

    const showList = segments.length > 1;

    if (total === null && segments.length > 0 && segments.every((segment) => segment.km !== null)) {
        total = segments.reduce((sum, segment) => sum + (segment.km ?? 0), 0);
    }

    if (segments.length === 0 && total !== null) {
        segments.push({ type: 'easy', weight: total * MINUTES_PER_KM, km: total, label: `${total} km easy` });
    }

    return { segments, total, strides, reps, showList };
}

function weekHref(target: WeekTarget) {
    return target.kind === 'prep' ? prep(target.week) : training(target.week);
}

function PlanStrip({ weeks, current }: { weeks: PlanWeek[]; current: string }) {
    const scrollerRef = useRef<HTMLDivElement>(null);
    const currentRef = useRef<HTMLAnchorElement>(null);
    const currentOffset = weeks.find((week) => week.id === current)?.offset ?? 0;
    const phases = weeks.reduce<{ name: string; weeks: PlanWeek[] }[]>((groups, week) => {
        const last = groups[groups.length - 1];

        if (last && last.name === week.phase) {
            last.weeks.push(week);
        } else {
            groups.push({ name: week.phase, weeks: [week] });
        }

        return groups;
    }, []);

    useEffect(() => {
        const scroller = scrollerRef.current;
        const item = currentRef.current;

        if (!scroller || !item) {
            return;
        }

        const frame = requestAnimationFrame(() => {
            if (scroller.scrollWidth <= scroller.clientWidth) {
                return;
            }

            const scrollerBox = scroller.getBoundingClientRect();
            const itemBox = item.getBoundingClientRect();

            scroller.scrollLeft += itemBox.left - scrollerBox.left - scroller.clientWidth / 2 + itemBox.width / 2;
        });

        return () => cancelAnimationFrame(frame);
    }, [current]);

    return (
        <div
            ref={scrollerRef}
            className="-mx-4 min-w-0 snap-x snap-mandatory overflow-x-auto overscroll-x-contain px-4 sm:-mx-8 sm:px-8 xl:mx-0 xl:snap-none xl:overflow-visible xl:px-0"
        >
            <div className="flex w-max gap-2 xl:w-auto xl:gap-1.5">
                {phases.map((phase, index) => (
                    <div key={`${phase.name}-${index}`} className="flex shrink-0 flex-col gap-1.5 xl:min-w-0 xl:shrink" style={{ flex: phase.weeks.length }}>
                        <span className="hidden truncate text-[11px] tracking-wider text-muted-foreground uppercase xl:block">{phase.name}</span>
                        <div className="flex gap-2 xl:gap-[3px]">
                            {phase.weeks.map((week) => (
                                <Link
                                    key={week.id}
                                    ref={week.id === current ? currentRef : undefined}
                                    href={weekHref({ kind: week.kind, week: week.week })}
                                    prefetch
                                    title={`${week.range} · ${week.km} km`}
                                    className={cn(
                                        'flex h-12 w-16 shrink-0 snap-center items-center justify-center rounded-lg text-sm transition-colors xl:h-[22px] xl:w-auto xl:min-w-0 xl:flex-1 xl:snap-align-none xl:rounded xl:text-[11px]',
                                        week.id === current
                                            ? 'bg-foreground text-background'
                                            : week.offset < currentOffset
                                              ? 'bg-[oklch(0.85_0_0)] text-[oklch(0.45_0_0)] hover:bg-[oklch(0.8_0_0)]'
                                              : 'bg-[oklch(0.95_0_0)] text-[oklch(0.45_0_0)] hover:bg-[oklch(0.9_0_0)]',
                                    )}
                                >
                                    {week.label}
                                </Link>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function Unit({ children }: { children: string }) {
    return <span className="ml-1 font-sans text-sm text-muted-foreground">{children}</span>;
}

function strengthPreference(): boolean {
    if (typeof window === 'undefined') {
        return true;
    }

    return localStorage.getItem('training-show-strength') !== '0';
}

function StrengthSession({ minutes, featured = false }: { minutes: number; featured?: boolean }) {
    if (featured) {
        return (
            <div className="flex flex-1 flex-col gap-1">
                <span className="text-[15px] font-medium tracking-tight">Strength</span>
                <span className="font-['Instrument_Serif'] text-[44px] leading-none">
                    {minutes}
                    <Unit>min</Unit>
                </span>
            </div>
        );
    }

    return (
        <div className="mt-auto flex items-baseline justify-between gap-2 border-t border-border pt-3">
            <span className="text-[13px] font-medium">Strength</span>
            <span className="text-[13px] text-muted-foreground">{minutes} min</span>
        </div>
    );
}

export default function Training({ week, caption, phase, range, current, previous, next, days, weeks }: TrainingProps) {
    const [showStrength, setShowStrength] = useState(strengthPreference);
    const parsed = days.map((day) => (day.session ? parseSession(day.session.lines) : null));
    const runs = days.filter((day) => day.session !== null).length;
    const strengthSessions = showStrength ? days.filter((day) => day.strength !== null).length : 0;
    const distance = parsed.reduce((sum, session) => sum + (session?.total ?? 0), 0);
    const hasTimed = parsed.some(
        (session, index) =>
            session !== null &&
            session.total === null &&
            days[index].session?.name !== 'Race day' &&
            days[index].session?.name !== 'Stabridge Stagger',
    );
    const usedTypes = new Set(parsed.flatMap((session) => session?.segments.map((segment) => segment.type) ?? []));

    function toggleStrength(): void {
        setShowStrength((current) => {
            const next = !current;
            localStorage.setItem('training-show-strength', next ? '1' : '0');

            return next;
        });
    }

    return (
        <Layout wide>
            <Head title={`Week ${week}`} />
            <div className="flex min-w-0 flex-col gap-7">
                <div className="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <header className="flex flex-col gap-1.5">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="rounded-full border border-border px-2.5 py-0.5 text-xs font-medium tracking-wider uppercase">
                                {phase}
                            </span>
                            <span className="text-sm text-muted-foreground">{range}</span>
                        </div>
                        <h1 className="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                            <span className="font-['Instrument_Serif'] text-5xl leading-none font-normal xl:text-6xl">Week {week}</span>
                            <span className="text-[15px] text-muted-foreground">{caption}</span>
                        </h1>
                    </header>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between xl:justify-end xl:gap-8">
                        <div className="flex flex-col gap-0.5 sm:items-end">
                            <span className="font-['Instrument_Serif'] text-4xl leading-none">
                                {distance}
                                <Unit>km</Unit>
                            </span>
                            <span className="text-[13px] text-pretty text-muted-foreground sm:text-right">
                                {runs} {runs === 1 ? 'run' : 'runs'}
                                {hasTimed ? ' · plus speed session' : ''}
                                {strengthSessions > 0 ? ` · ${strengthSessions} strength ${strengthSessions === 1 ? 'session' : 'sessions'}` : ''}
                            </span>
                        </div>
                        <nav className="grid grid-cols-2 gap-2 sm:flex">
                            {previous !== null ? (
                                <Link
                                    href={weekHref(previous)}
                                    prefetch
                                    className="flex h-11 items-center justify-center rounded-full border border-border px-3.5 text-sm transition-colors hover:bg-muted xl:h-9"
                                >
                                    ← Previous
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="flex h-11 cursor-not-allowed items-center justify-center rounded-full border border-border px-3.5 text-sm opacity-40 xl:h-9"
                                >
                                    ← Previous
                                </button>
                            )}
                            {next !== null ? (
                                <Link
                                    href={weekHref(next)}
                                    prefetch
                                    className="flex h-11 items-center justify-center rounded-full bg-foreground px-3.5 text-sm text-background transition-colors hover:bg-foreground/85 xl:h-9"
                                >
                                    Next →
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="flex h-11 cursor-not-allowed items-center justify-center rounded-full bg-foreground px-3.5 text-sm text-background opacity-40 xl:h-9"
                                >
                                    Next →
                                </button>
                            )}
                        </nav>
                    </div>
                </div>

                <PlanStrip weeks={weeks} current={current} />

                <div>
                    <ol className="grid grid-cols-1 gap-3 xl:grid-cols-7">
                        {days.map((day, index) => {
                            const [weekday, dayOfMonth, month] = day.label.split(' ');
                            const session = parsed[index];
                            const isRace = day.session?.name === 'Race day';
                            const strength = showStrength ? day.strength : null;
                            const isEmpty = day.session === null && !day.isRest && strength === null;
                            const compact = day.isRest || isEmpty;

                            return (
                                <li
                                    key={day.date}
                                    className={cn(
                                        'flex rounded-xl',
                                        day.isToday ? 'border-[1.5px] border-foreground' : 'border',
                                        isEmpty && !day.isToday && 'border-dashed border-[oklch(0.88_0_0)]',
                                        day.isRest && 'bg-[repeating-linear-gradient(135deg,#fff_0_7px,oklch(0.975_0_0)_7px_8px)]',
                                        compact
                                            ? 'flex-row items-center justify-between gap-3 px-4 py-3 xl:min-h-[340px] xl:flex-col xl:items-stretch xl:justify-start xl:gap-4 xl:p-4'
                                            : 'flex-col gap-3 p-4 xl:min-h-[340px] xl:gap-4',
                                    )}
                                >
                                    <div className={cn('flex items-start justify-between gap-2', compact && 'xl:w-full')}>
                                        <div
                                            className={cn(
                                                'flex flex-none gap-0.5',
                                                compact ? 'flex-row items-baseline gap-2 xl:flex-col xl:items-start xl:gap-0.5' : 'flex-col',
                                            )}
                                        >
                                            <span className="text-xs font-medium tracking-wider text-muted-foreground uppercase">
                                                {weekday.slice(0, 3)}
                                            </span>
                                            <span className="font-['Instrument_Serif'] text-[30px] leading-none whitespace-nowrap">
                                                {dayOfMonth} <span className="font-sans text-[13px] text-muted-foreground">{month}</span>
                                            </span>
                                        </div>
                                        {day.isToday ? (
                                            <span className="rounded-full bg-foreground px-2 py-0.5 text-[11px] font-medium text-background">
                                                Today
                                            </span>
                                        ) : null}
                                    </div>

                                    {isRace ? (
                                        <div className="flex flex-1 flex-col justify-end gap-1">
                                            <span className="text-[15px] font-medium">Race day</span>
                                            <span className="font-['Instrument_Serif'] text-[44px] leading-none">
                                                42.2
                                                <Unit>km</Unit>
                                            </span>
                                        </div>
                                    ) : day.session?.name === 'Stabridge Stagger' ? (
                                        <div className="flex flex-1 flex-col justify-end gap-1">
                                            <span className="text-[15px] font-medium">Stabridge Stagger</span>
                                            <span className="font-['Instrument_Serif'] text-[44px] leading-none">
                                                10
                                                <Unit>miles</Unit>
                                            </span>
                                        </div>
                                    ) : day.session && session ? (
                                        <div className="flex flex-1 flex-col gap-3">
                                            <div className="flex flex-col gap-1">
                                                <span className="text-[15px] font-medium tracking-tight">{day.session.name}</span>
                                                <span className="font-['Instrument_Serif'] text-[44px] leading-none">
                                                    {session.total ?? session.reps?.value}
                                                    <Unit>{session.total !== null ? 'km' : (session.reps?.unit ?? '')}</Unit>
                                                </span>
                                            </div>
                                            <div className="flex h-2 gap-0.5">
                                                {session.segments.map((segment, segmentIndex) => (
                                                    <div
                                                        key={segmentIndex}
                                                        className={cn('rounded-[3px]', SEGMENT_STYLES[segment.type].color)}
                                                        style={{ flex: segment.weight }}
                                                    />
                                                ))}
                                            </div>
                                            {session.showList ? (
                                                <ul className="flex flex-col gap-1.5">
                                                    {session.segments.map((segment, segmentIndex) => (
                                                        <li
                                                            key={segmentIndex}
                                                            className="flex items-baseline gap-2 text-[13px] leading-snug text-[oklch(0.35_0_0)]"
                                                        >
                                                            <span
                                                                className={cn(
                                                                    'size-[7px] flex-none -translate-y-px rounded-full',
                                                                    SEGMENT_STYLES[segment.type].color,
                                                                )}
                                                            />
                                                            <span>{segment.label}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : null}
                                            {session.strides ? (
                                                <span className="self-start rounded-full border border-border px-2.5 py-0.5 text-xs text-[oklch(0.35_0_0)]">
                                                    + {session.strides}
                                                </span>
                                            ) : null}
                                            {strength !== null ? <StrengthSession minutes={strength} /> : null}
                                        </div>
                                    ) : strength !== null ? (
                                        <StrengthSession minutes={strength} featured />
                                    ) : day.isRest ? (
                                        <div className="flex flex-none items-center xl:flex-1 xl:items-end">
                                            <span className="font-['Instrument_Serif'] text-2xl text-muted-foreground italic xl:text-[30px]">Rest</span>
                                        </div>
                                    ) : (
                                        <div className="flex flex-none items-center xl:flex-1 xl:items-end">
                                            <span className="text-[13px] text-muted-foreground">Nothing planned</span>
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    {usedTypes.size > 0 ? (
                        <div className="flex flex-wrap gap-5">
                            {(Object.keys(SEGMENT_STYLES) as SegmentType[])
                                .filter((type) => usedTypes.has(type))
                                .map((type) => (
                                    <span key={type} className="flex items-center gap-1.5 text-[13px] text-[oklch(0.35_0_0)]">
                                        <span className={cn('size-2.5 rounded-[3px]', SEGMENT_STYLES[type].color)} />
                                        {SEGMENT_STYLES[type].label}
                                    </span>
                                ))}
                        </div>
                    ) : (
                        <span />
                    )}
                    <button
                        type="button"
                        role="switch"
                        aria-checked={showStrength}
                        onClick={toggleStrength}
                        className="flex items-center gap-2 self-end py-2"
                    >
                        <span className="text-sm text-muted-foreground">Strength</span>
                        <span
                            className={cn('relative h-5 w-9 rounded-full transition-colors', showStrength ? 'bg-foreground' : 'bg-[oklch(0.85_0_0)]')}
                        >
                            <span
                                className={cn(
                                    'absolute top-0.5 left-0.5 size-4 rounded-full bg-background shadow-sm transition-transform',
                                    showStrength && 'translate-x-4',
                                )}
                            />
                        </span>
                    </button>
                </div>
            </div>
        </Layout>
    );
}
