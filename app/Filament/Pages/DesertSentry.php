<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Livewire\Attributes\Computed;

/**
 * The single Desert Sentry dashboard.
 *
 * This is a Livewire component, so the metric range, the live event feed and the
 * system log all update over the wire without a full page reload. It replaces
 * `Filament\Pages\Dashboard` as the panel home, which keeps the panel to exactly
 * one dashboard.
 */
class DesertSentry extends Page
{
    protected string $view = 'filament.pages.desert-sentry';

    protected static ?string $title = null;

    protected static ?int $navigationSort = -2;

    /**
     * Mount the page at the panel root so it *is* the panel home, matching how
     * `Filament\Pages\Dashboard` claims `/`.
     */
    protected static string $routePath = '/';

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    /**
     * How the "events" metric is aggregated. Changing it re-renders the metric
     * cards and the sparkline on the server.
     */
    public string $range = '24h';

    /**
     * Whether the live feed keeps polling. Toggled from the toolbar so an
     * operator can freeze the stream while reading it.
     */
    public bool $streaming = true;

    /**
     * Incrementing seed so the simulated stream advances on each poll.
     */
    public int $tick = 0;

    public function mount(): void
    {
        $this->range = '24h';

        $this->announceFlashStatus();
    }

    /**
     * Surface the sign-in / sign-up confirmation on the panel.
     *
     * The custom auth controllers flash a `status` message and then redirect
     * here. Nothing in the Filament layout reads that key, so without this the
     * message is dropped silently and the visitor gets no confirmation that the
     * credentials were accepted.
     */
    protected function announceFlashStatus(): void
    {
        $status = session('status');

        if (blank($status)) {
            return;
        }

        Notification::make()
            ->title($status)
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    public static function ranges(): array
    {
        return [
            '1h' => 'site.dashboard.range_1h',
            '24h' => 'site.dashboard.range_24h',
            '7d' => 'site.dashboard.range_7d',
        ];
    }

    /**
     * Human-readable label for a range, or null when the value is unknown.
     */
    public function rangeLabel(?string $range = null): ?string
    {
        $key = static::ranges()[$range ?? $this->range] ?? null;

        return $key === null ? null : __($key);
    }

    public function setRange(string $range): void
    {
        if (array_key_exists($range, static::ranges())) {
            $this->range = $range;
        }
    }

    public function toggleStreaming(): void
    {
        $this->streaming = ! $this->streaming;
    }

    public function refreshStream(): void
    {
        $this->tick++;
    }

    /**
     * Metric values are derived from the selected range so switching it visibly
     * changes the cards, which is what makes this worth a round trip.
     *
     * @return array<string, array{label: string, value: string, unit: string, tone: string, detail: string}>
     */
    #[Computed]
    public function metrics(): array
    {
        $scale = match ($this->range) {
            '1h' => 0.11,
            '7d' => 6.4,
            default => 1.0,
        };

        $events = 24_800 * $scale;

        return [
            'events' => [
                'label' => __('site.dashboard.threat_detection'),
                'value' => $events >= 1000
                    ? number_format($events / 1000, 1).'K'
                    : number_format($events),
                'unit' => __('site.dashboard.events_unit'),
                'tone' => 'green',
                'detail' => __('site.dashboard.metric_events_detail', ['range' => $this->rangeLabel()]),
            ],
            'edge' => [
                'label' => __('site.dashboard.edge_load'),
                'value' => '68',
                'unit' => '%',
                'tone' => 'cyan',
                'detail' => __('site.dashboard.rk_utilization'),
            ],
            'wear' => [
                'label' => __('site.dashboard.disk_wear'),
                'value' => '0.19',
                'unit' => '%',
                'tone' => 'green',
                'detail' => __('site.dashboard.ring_savings'),
            ],
            'airgap' => [
                'label' => __('site.dashboard.airgap'),
                'value' => __('site.dashboard.operational'),
                'unit' => '',
                'tone' => 'cyan',
                'detail' => __('site.dashboard.zero_cloud'),
            ],
        ];
    }

    /**
     * Sparkline series for the selected range. Purely derived, so it is safe to
     * recompute on every poll.
     *
     * @return list<int>
     */
    #[Computed]
    public function sparkline(): array
    {
        $points = match ($this->range) {
            '1h' => 12,
            '7d' => 14,
            default => 16,
        };

        $base = match ($this->range) {
            '1h' => 38,
            '7d' => 74,
            default => 58,
        };

        $series = [];

        for ($i = 0; $i < $points; $i++) {
            // Deterministic pseudo-variation keeps the chart stable between polls.
            $wave = (int) round(($base / 2) * sin(($i + $this->tick) * 0.7));
            $series[] = max(4, min(96, $base + $wave));
        }

        return $series;
    }

    /**
     * Rolling system log. Newest first, and it advances with the poll tick so
     * the live stream visibly moves.
     *
     * @return list<array{level: string, message: string, time: string}>
     */
    #[Computed]
    public function systemLog(): array
    {
        $messages = [
            ['ok', 'site.dashboard.log_motion'],
            ['ok', 'site.dashboard.log_pipeline'],
            ['info', 'site.dashboard.log_buffer'],
            ['info', 'site.dashboard.log_airgap'],
        ];

        $now = now();

        return array_map(
            fn (array $entry, int $index): array => [
                'level' => $entry[0],
                'message' => __($entry[1]),
                'time' => $now->copy()->subSeconds(($index + $this->tick) * 7)->format('H:i:s'),
            ],
            $messages,
            array_keys($messages),
        );
    }

    public function getTitle(): string
    {
        return __('site.dashboard.title');
    }

    public function getHeading(): string
    {
        return __('site.dashboard.control_hub');
    }

    public function getSubheading(): string
    {
        return __('site.dashboard.subheading');
    }

    /**
     * The sidebar entry, with a floor under it.
     *
     * A language added from the panel starts with no strings in it, and the
     * loader answers an untranslated key with an empty string rather than
     * falling back. Filament passes this label straight into an uninitialised
     * typed property, so an empty one throws and takes down every page in the
     * panel -- not just the dashboard -- with
     * "Typed property NavigationItem::$label must not be accessed before
     * initialization".
     *
     * The sidebar is chrome rather than content: an operator has to be able to
     * navigate a half-translated panel to finish translating it, so this label
     * falls back to English instead of disappearing.
     */
    public static function getNavigationLabel(): string
    {
        return __('site.dashboard.nav_label') ?: __('site.dashboard.nav_label', [], 'en') ?: 'Desert Sentry';
    }
}
