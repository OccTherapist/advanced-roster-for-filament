<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use Illuminate\Database\Eloquent\Model;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;

class ModelRosterScopeAdapter implements RosterScope
{
    public function __construct(
        protected Model $model,
    ) {}

    public function getRosterScopeKey(): int|string
    {
        return $this->model->getKey();
    }

    public function getRosterScopeType(): string
    {
        return $this->model->getMorphClass();
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}
