<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use InvalidArgumentException;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryTypeContract;
use OccTherapist\AdvancedRosterForFilament\Enums\RosterEntryType;

class RosterEntryTypeResolver
{
    /** @var array<string, class-string<RosterEntryTypeContract>> */
    protected array $types;

    public function __construct()
    {
        $this->types = config('advanced-roster-for-filament.entry_types', []);
    }

    public function resolve(null|string|RosterEntryTypeContract $type): ?RosterEntryTypeContract
    {
        if ($type instanceof RosterEntryTypeContract) {
            return $type;
        }

        if ($type === null) {
            return RosterEntryType::WORK;
        }

        if (isset($this->types[$type])) {
            $class = $this->types[$type];

            if (is_subclass_of($class, RosterEntryTypeContract::class)) {
                if (enum_exists($class)) {
                    return $class::from($type);
                }

                return app($class);
            }
        }

        return RosterEntryType::tryFrom($type);
    }

    public function register(string $key, string $class): void
    {
        if (! is_subclass_of($class, RosterEntryTypeContract::class)) {
            throw new InvalidArgumentException("{$class} must implement RosterEntryTypeContract.");
        }

        $this->types[$key] = $class;
    }

    /**
     * @return array<string, RosterEntryTypeContract>
     */
    public function all(): array
    {
        $resolved = [];

        foreach ($this->types as $key => $class) {
            $type = $this->resolve($key);

            if ($type) {
                $resolved[$key] = $type;
            }
        }

        return $resolved;
    }
}
