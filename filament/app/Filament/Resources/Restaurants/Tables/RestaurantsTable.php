<?php

namespace App\Filament\Resources\Restaurants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Rag. Sociale')->searchable()->sortable(),
                TextColumn::make('city')->label('Comune')->searchable()->sortable(),
                TextColumn::make('address')->label('Indirizzo'),
                TextColumn::make('CAP')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge(),
            ])
            ->filters([
                Filter::make('filter_restaurant')
                    ->schema([
                        TextInput::make('filter_name')->label('Nome'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['filter_name'] ?? null,
                        fn (Builder $query, $name) => $query->where('name', 'like', "%{$name}%")
                    )),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
