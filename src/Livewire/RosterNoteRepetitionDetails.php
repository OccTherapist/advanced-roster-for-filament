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
use OccTherapist\AdvancedRosterForFilament\Models\RosterNote;

class RosterNoteRepetitionDetails extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?int $rosterNoteId = null;

    public function table(Table $table): Table
    {
        $record = RosterNote::query()->find($this->rosterNoteId);
        $parentId = $record?->parent_id ?? $this->rosterNoteId;

        return $table
            ->query(
                RosterNote::query()
                    ->where(function ($query) use ($parentId) {
                        $query->where('parent_id', $parentId)
                            ->orWhere('id', $parentId);
                    })
                    ->whereNot('id', $this->rosterNoteId)
                    ->orderBy('date')
            )
            ->columns([
                TextColumn::make('date')
                    ->label(__('advanced-roster-for-filament::fields.date'))
                    ->date('d.m.Y'),
                TextColumn::make('note')
                    ->label(__('advanced-roster-for-filament::fields.note'))
                    ->limit(50),
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
        return view('advanced-roster-for-filament::livewire.roster-note-repetition-details');
    }
}
