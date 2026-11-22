<?php

namespace App\Filament\Resources\Dishes\Tables;

use App\Models\DishCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DishesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.dish_category')->label('Categoria'),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('description')->label('Descrizione')->limit(50),
                ImageColumn::make('image_url')->label('Immagine'),
                TextColumn::make('price')->label('Prezzo')->money('EUR')->sortable(),
                TextColumn::make('discount_price')->label('Prezzo scontato')->money('EUR'),
                TextColumn::make('vat_rate')->label('IVA %'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->options(fn () => DishCategory::pluck('dish_category', 'id')),
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
