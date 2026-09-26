{{--
    The single Desert Sentry dashboard.

    Rendered inside the Filament panel so it inherits the panel's session and
    navigation, but every visual here is the site's own glass/cyan/green
    treatment rather than Filament's default widgets.

    Livewire wiring:
      * the range buttons round-trip to PHP and recompute the metric cards,
      * `wire:poll` advances the simulated stream while `streaming` is on,
      * `refreshStream` is what the poll calls, and `toggleStreaming` freezes it.
--}}
@php
    $series = $this->sparkline;
    $count = count($series);
    $step = $count > 1 ? 100 / ($count - 1) : 100;
    $points = implode(' ', array_map(
        static fn (int $index, int $value): string => round($index * $step, 2).','.round(100 - $value, 2),
        array_keys($series),
        $series,
    ));
    $metrics = $this->metrics;
    $log = $this->systemLog;
@endphp

<x-filament-panels::page>
    {{-- Toolbar. Filament v5 builds its header actions in PHP rather than
         through a Blade slot, so the range selector and the stream toggle sit
         at the top of the page body where the custom styling is fully under
         our control. They are the only controls that touch server state, which
         is what makes this page a Livewire component and not a static view. --}}
    <div
        @if ($streaming) wire:poll.15s="refreshStream" @endif
        @class(['fi-section', 'fi-gap-y-6'])
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div
                role="group"
                aria-label="{{ __('site.dashboard.range_selector') }}"
                class="ds-surface flex items-center gap-1 p-1"
            >
                @foreach (static::ranges() as $value => $key)
                    <button
                        type="button"
                        wire:click="setRange('{{ $value }}')"
                        aria-pressed="{{ $range === $value ? 'true' : 'false' }}"
                        @class([
                            'rounded-lg px-3 py-1.5 font-mono text-xs transition ds-technical-ltr',
                            'bg-[var(--ds-cyan)]/15 text-[var(--ds-cyan)] shadow-[inset_0_0_0_1px_rgb(0_240_255/0.4)]'
                                => $range === $value,
                            'text-slate-400 hover:text-slate-200' => $range !== $value,
                        ])
                    >
                        {{ __($key) }}
                    </button>
                @endforeach
            </div>

            <button
                type="button"
                wire:click="toggleStreaming"
                wire:loading.attr="disabled"
                wire:target="toggleStreaming"
                @class([
                    'rounded-xl border px-4 py-2 font-mono text-xs transition ds-technical-ltr',
                    'border-[var(--ds-green)]/60 bg-[var(--ds-green)]/10 text-[var(--ds-green)]'
                        => $streaming,
                    'border-slate-700 text-slate-400 hover:text-slate-200' => ! $streaming,
                ])
            >
                {{ $streaming ? __('site.dashboard.pause_stream') : __('site.dashboard.resume_stream') }}
            </button>
        </div>
        {{-- Metric cards. Values are technical (Latin/numeric) so they keep
             LTR ordering even when the page renders right-to-left. --}}
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <div class="ds-surface p-5" wire:key="metric-{{ $loop->index }}">
                    <div class="ds-eyebrow">{{ $metric['label'] }}</div>
                    <div
                        class="ds-technical-ltr mt-3 font-mono text-3xl font-black"
                        @class([
                            'text-[var(--ds-green)] ds-glow-green' => $metric['tone'] === 'green',
                            'text-[var(--ds-cyan)] ds-glow-cyan' => $metric['tone'] === 'cyan',
                        ])
                    >
                        @if ($metric['unit'] !== '')
                            <span>{{ $metric['value'] }}</span>
                            <span class="ms-1 text-xl">{{ $metric['unit'] }}</span>
                        @else
                            {{ $metric['value'] }}
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-400">{{ $metric['detail'] }}</p>

                    <svg
                        viewBox="0 0 100 100"
                        preserveAspectRatio="none"
                        class="ds-technical-ltr mt-4 h-10 w-full"
                        role="img"
                        aria-label="{{ __('site.dashboard.trend_aria') }}"
                    >
                        <polyline
                            points="{{ $points }}"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            vector-effect="non-scaling-stroke"
                            @class([
                                'text-[var(--ds-cyan)]' => $metric['tone'] === 'cyan',
                                'text-[var(--ds-green)]' => $metric['tone'] === 'green',
                            ])
                        />
                    </svg>
                </div>
            @endforeach
        </div>

        {{-- Topology + log, mirroring the original two-column layout. --}}
        <div class="grid gap-6 lg:grid-cols-[1.5fr_0.9fr]">
            <section class="ds-surface p-5" aria-labelledby="ds-topology-title">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 id="ds-topology-title" class="ds-eyebrow">{{ __('site.dashboard.live_map') }}</h2>
                    <span class="ds-technical-ltr rounded-full border border-[var(--ds-green)]/50 bg-[var(--ds-green)]/10 px-2 py-1 font-mono text-xs text-[var(--ds-green)]">
                        {{ __('site.dashboard.operational') }}
                    </span>
                </div>

                {{-- The topology is a fixed left-to-right signal chain, so it is
                     pinned to LTR regardless of the active locale. --}}
                <div class="ds-technical-ltr h-[320px] rounded-xl border border-slate-700 bg-slate-950/70 p-3">
                    <svg viewBox="0 0 620 300" class="h-full w-full" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ __('site.dashboard.live_map') }}">
                        <g opacity="0.9">
                            <path d="M60 150H170V90H280V210H420V150H540" stroke="#39FF14" stroke-width="2" fill="none" stroke-dasharray="10 8"/>
                            <path d="M170 150H280" stroke="#00F0FF" stroke-width="2" fill="none"/>
                            <path d="M420 150H540" stroke="#00F0FF" stroke-width="2" fill="none"/>
                        </g>
                        <g>
                            <rect x="30" y="118" width="140" height="62" rx="10" fill="#0f172a" stroke="#00F0FF"/>
                            <text x="100" y="143" text-anchor="middle" fill="#E2E8F0" font-size="13" font-family="JetBrains Mono, monospace">{{ __('site.dashboard.cameras') }}</text>
                            <text x="100" y="164" text-anchor="middle" fill="#00F0FF" font-size="11" font-family="JetBrains Mono, monospace">RTSP</text>

                            <rect x="280" y="70" width="140" height="78" rx="10" fill="#0f172a" stroke="#39FF14"/>
                            <text x="350" y="100" text-anchor="middle" fill="#E2E8F0" font-size="13" font-family="JetBrains Mono, monospace">RK3588</text>
                            <text x="350" y="121" text-anchor="middle" fill="#39FF14" font-size="11" font-family="JetBrains Mono, monospace">YOLOv8</text>

                            <rect x="280" y="170" width="140" height="78" rx="10" fill="#0f172a" stroke="#00F0FF"/>
                            <text x="350" y="200" text-anchor="middle" fill="#E2E8F0" font-size="13" font-family="JetBrains Mono, monospace">RAM</text>
                            <text x="350" y="221" text-anchor="middle" fill="#00F0FF" font-size="11" font-family="JetBrains Mono, monospace">3s / 5s</text>

                            <rect x="480" y="118" width="110" height="62" rx="10" fill="#0f172a" stroke="#00F0FF"/>
                            <text x="535" y="143" text-anchor="middle" fill="#E2E8F0" font-size="13" font-family="JetBrains Mono, monospace">NVR</text>
                            <text x="535" y="164" text-anchor="middle" fill="#00F0FF" font-size="11" font-family="JetBrains Mono, monospace">{{ __('site.dashboard.offline') }}</text>
                        </g>
                    </svg>
                </div>
            </section>

            <section class="ds-surface p-5" aria-labelledby="ds-log-title">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 id="ds-log-title" class="ds-eyebrow">{{ __('site.dashboard.system_log') }}</h2>
                    <span class="ds-technical-ltr font-mono text-[10px] tracking-[0.2em] text-slate-500">
                        {{ $this->rangeLabel() }}
                    </span>
                </div>

                <ul class="space-y-3 text-sm text-slate-300" aria-live="polite">
                    @foreach ($log as $entry)
                        {{-- The message keeps the page direction (it is localized),
                             while the level tag and the timestamp stay LTR
                             because they are technical, not prose. --}}
                        <li
                            class="rounded-lg border border-slate-700 bg-slate-950/70 p-3"
                            wire:key="log-{{ $loop->index }}-{{ $entry['time'] }}"
                        >
                            <span
                                class="ds-technical-ltr font-mono"
                                @class([
                                    'text-[var(--ds-green)]' => $entry['level'] === 'ok',
                                    'text-[var(--ds-cyan)]' => $entry['level'] === 'info',
                                ])
                            >[{{ strtoupper($entry['level']) }}]</span>
                            <span>{{ $entry['message'] }}</span>
                            <span class="ds-technical-ltr float-end font-mono text-xs text-slate-500">{{ $entry['time'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-filament-panels::page>
