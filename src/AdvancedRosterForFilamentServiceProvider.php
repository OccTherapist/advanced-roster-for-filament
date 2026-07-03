<?php

namespace OccTherapist\AdvancedRosterForFilament;

use Filament\Panel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScopeResolver;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterSection;
use OccTherapist\AdvancedRosterForFilament\Http\Controllers\RosterPrintController;
use OccTherapist\AdvancedRosterForFilament\Livewire\RosterEntryRepetitionDetails;
use OccTherapist\AdvancedRosterForFilament\Livewire\RosterNoteRepetitionDetails;
use OccTherapist\AdvancedRosterForFilament\Support\ConfigRosterSection;
use OccTherapist\AdvancedRosterForFilament\Support\RosterAssigneeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterEntryTypeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterFilterRegistry;
use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;
use OccTherapist\AdvancedRosterForFilament\Support\RosterSectionRegistry;
use OccTherapist\AdvancedRosterForFilament\Support\RosterValidatorRegistry;

class AdvancedRosterForFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/advanced-roster-for-filament.php', 'advanced-roster-for-filament');

        $this->app->singleton(RosterSectionRegistry::class);
        $this->app->singleton(RosterEntryTypeResolver::class);
        $this->app->singleton(RosterValidatorRegistry::class);
        $this->app->singleton(RosterFilterRegistry::class);
        $this->app->singleton(RosterPreferencesRepository::class);
        $this->app->singleton(RosterAssigneeResolver::class);

        $this->app->singleton(RosterScopeResolver::class, function () {
            $class = config('advanced-roster-for-filament.scope_resolver');

            return app($class);
        });

        $this->app->singleton(RosterScopeManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'advanced-roster-for-filament');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'advanced-roster-for-filament');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'advanced-roster');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/advanced-roster-for-filament.php' => config_path('advanced-roster-for-filament.php'),
            ], 'advanced-roster-for-filament-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/advanced-roster-for-filament'),
            ], 'advanced-roster-for-filament-views');
        }

        $this->registerPanelMacro();
        $this->registerDefaultSection();
        $this->registerPrintRoute();
        $this->registerLivewireComponents();
    }

    protected function registerPanelMacro(): void
    {
        if (Panel::hasMacro('advancedRoster')) {
            return;
        }

        Panel::macro('advancedRoster', function (?callable $configure = null): Panel {
            $plugin = AdvancedRosterForFilamentPlugin::make();

            if ($configure) {
                $configure($plugin);
            }

            return $this->plugin($plugin);
        });
    }

    protected function registerDefaultSection(): void
    {
        $registry = $this->app->make(RosterSectionRegistry::class);
        $sectionKey = config('advanced-roster-for-filament.assignee_section_key', 'assignees');

        if (! $registry->has($sectionKey)) {
            $registry->register(new ConfigRosterSection(
                key: $sectionKey,
                label: __('advanced-roster-for-filament::navigation.label'),
                assigneeModel: config('advanced-roster-for-filament.assignee_model'),
            ));
        }

        foreach (config('advanced-roster-for-filament.sections', []) as $key => $config) {
            if (is_string($config) && is_subclass_of($config, RosterSection::class)) {
                $registry->register(app($config));

                continue;
            }

            if (is_array($config)) {
                $registry->register(ConfigRosterSection::fromConfig($key, $config));
            }
        }
    }

    protected function registerPrintRoute(): void
    {
        $routeName = config('advanced-roster-for-filament.print.route_name', 'advanced-roster.print');

        Route::middleware(['web', 'auth'])
            ->get('/advanced-roster/print', RosterPrintController::class)
            ->name($routeName);
    }

    protected function registerLivewireComponents(): void
    {
        $this->app->booted(function (): void {
            if (! class_exists(Livewire::class) || ! $this->app->bound('livewire')) {
                return;
            }

            Livewire::component('advanced-roster-entry-repetition-details', RosterEntryRepetitionDetails::class);
            Livewire::component('advanced-roster-note-repetition-details', RosterNoteRepetitionDetails::class);
        });
    }
}
