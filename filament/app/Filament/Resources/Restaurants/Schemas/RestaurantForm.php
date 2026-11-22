<?php

namespace App\Filament\Resources\Restaurants\Schemas;

use App\Models\Dish;
use App\Models\DishCategory;
use App\Models\Restaurant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Anagrafica')->schema([
                    TextInput::make('name')->label('Rag. Sociale')->required(),
                    TextInput::make('city')->label('Comune')->required(),
                    TextInput::make('address')->label('Indirizzo')->required(),
                    TextInput::make('CAP')->label('CAP')->required(),
                    Select::make('type')->label('Tipo')->required()->options([
                        'pizzeria' => 'pizzeria',
                        'trattoria' => 'trattoria',
                        'osteria' => 'osteria',
                        'ristorante' => 'ristorante',
                    ]),
                ]),
                Fieldset::make('Menu')->schema(fn (): array => self::menuFields()),
            ]);
    }

    /**
     * One multi-select per dish category. These fields are not columns of
     * `restaurants`; they are saved on the `restaurant_dishes` pivot.
     */
    private static function menuFields(): array
    {
        return DishCategory::all()->map(fn (DishCategory $category) => Select::make("menu_categoria_{$category->id}")
            ->label($category->dish_category)
            ->options(Dish::where('category_id', $category->id)->pluck('name', 'id'))
            ->multiple()
            ->preload()
            ->dehydrated(false)
            ->afterStateHydrated(function (Select $component, ?Restaurant $record) use ($category) {
                if ($record) {
                    $component->state(
                        $record->dishes()->where('category_id', $category->id)->pluck('dish.id')->all()
                    );
                }
            })
            ->saveRelationshipsUsing(function (Select $component, Restaurant $record) use ($category) {
                $inCategory = Dish::where('category_id', $category->id)->pluck('id')->all();
                $record->dishes()->detach($inCategory);
                $record->dishes()->attach($component->getState() ?? []);
            })
        )->all();
    }
}
