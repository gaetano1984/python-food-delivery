<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class IngredientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Anagrafica')->schema([
                    TextInput::make('name')->label('Nome')->required(),
                    Checkbox::make('is_allergen')->label('Allergene'),
                    TextInput::make('allergen_type')->label('Tipo di allergene'),
                    Checkbox::make('is_removerable')->label('Può essere rimosso')->default(true),
                ]),
            ]);
    }
}
