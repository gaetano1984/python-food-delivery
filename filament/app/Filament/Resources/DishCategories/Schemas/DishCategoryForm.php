<?php

namespace App\Filament\Resources\DishCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DishCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dish_category')->label('Categoria piatto')->required(),
            ]);
    }
}
