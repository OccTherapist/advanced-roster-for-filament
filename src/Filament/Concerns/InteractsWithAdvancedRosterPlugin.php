<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Concerns;

use Closure;
use Filament\Panel;
use OccTherapist\AdvancedRosterForFilament\AdvancedRosterForFilamentPlugin;

trait InteractsWithAdvancedRosterPlugin
{
    public function advancedRoster(?Closure $configure = null): static
    {
        $plugin = AdvancedRosterForFilamentPlugin::make();

        if ($configure) {
            $configure($plugin);
        }

        /** @var Panel $this */
        $this->plugin($plugin);

        return $this;
    }

    public function hasAdvancedRosterPlugin(): bool
    {
        /** @var Panel $this */
        return $this->hasPlugin(AdvancedRosterForFilamentPlugin::make()->getId());
    }
}
