<?php

namespace OccTherapist\AdvancedRosterForFilament\Livewire;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;

class RosterEntryRepetitionDetails extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?int $rosterEntryId = null;

    public function table(Table $table): Table
    {
        $record = RosterEntry::query()->find($this->rosterEntryId);
        $parentId = $record?->parent_id ?? $this->rosterEntryId;

        return $table
            ->query(
                RosterEntry::query()
                    ->where(function ($query) use ($parentId) {
                        $query->where('parent_id', $parentId)
                            ->orWhere('id', $parentId);
                    })
                    ->whereNot('id', $this->rosterEntryId)
                    ->orderBy('start_at')
            )
            ->columns([
                TextColumn::make('start_at')
                    ->label(__('advanced-roster-for-filament::fields.date'))
                    ->date('d.m.Y'),
                TextColumn::make('time')
                    ->label(__('advanced-roster-for-filament::fields.start_at'))
                    ->state(fn (RosterEntry $record) => $record->start_at->format('H:i').' - '.$record->end_at->format('H:i')),
                TextColumn::make('comment')
                    ->label(__('advanced-roster-for-filament::fields.comment'))
                    ->limit(30),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->slideOver(false),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('advanced-roster-for-filament::livewire.roster-entry-repetition-details');
    }
}
