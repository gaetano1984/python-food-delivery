<?php

namespace App\Filament\Resources\Dishes\Schemas;

use App\Models\DishCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DishForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Categoria')
                    ->options(fn () => DishCategory::pluck('dish_category', 'id'))
                    ->required(),
                TextInput::make('name')->label('Nome')->required(),
                TextInput::make('description')->label('Descrizione'),
                FileUpload::make('image_url')->label('Immagine')->image(),
                TextInput::make('price')->label('Prezzo')->numeric()->required(),
                TextInput::make('discount_price')->label('Prezzo scontato')->numeric(),
                TextInput::make('vat_rate')->label('IVA %')->numeric()->default(10),
                Select::make('ingredients')
                    ->label('Ingredienti')
                    ->relationship('ingredients', 'name')
                    ->multiple()
                    ->preload(),
            ]);
    }
}
