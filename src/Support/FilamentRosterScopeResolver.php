<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScopeResolver;

class FilamentRosterScopeResolver implements RosterScopeResolver
{
    public function resolve(): ?RosterScope
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof RosterScope) {
            return $tenant;
        }

        if ($tenant instanceof Model) {
            return new ModelRosterScopeAdapter($tenant);
        }

        return null;
    }
}
