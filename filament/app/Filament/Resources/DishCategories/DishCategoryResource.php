<?php

namespace App\Filament\Resources\DishCategories;

use App\Filament\Resources\DishCategories\Pages\CreateDishCategory;
use App\Filament\Resources\DishCategories\Pages\EditDishCategory;
use App\Filament\Resources\DishCategories\Pages\ListDishCategories;
use App\Filament\Resources\DishCategories\Schemas\DishCategoryForm;
use App\Filament\Resources\DishCategories\Tables\DishCategoriesTable;
use App\Models\DishCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DishCategoryResource extends Resource
{
    protected static ?string $model = DishCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Dishes';

    public static function form(Schema $schema): Schema
    {
        return DishCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DishCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDishCategories::route('/'),
            'create' => CreateDishCategory::route('/create'),
            'edit' => EditDishCategory::route('/{record}/edit'),
        ];
    }
}
