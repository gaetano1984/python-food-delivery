<?php

namespace App\Filament\Resources\DishCategories\Pages;

use App\Filament\Resources\DishCategories\DishCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDishCategory extends CreateRecord
{
    protected static string $resource = DishCategoryResource::class;
}
