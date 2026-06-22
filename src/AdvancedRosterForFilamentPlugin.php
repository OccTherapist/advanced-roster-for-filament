<?php

namespace OccTherapist\AdvancedRosterForFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage;

class AdvancedRosterForFilamentPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament()->getPlugin(static::make()->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'advanced-roster-for-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            RosterPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
