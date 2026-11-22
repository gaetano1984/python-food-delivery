<?php

namespace App\Filament\Resources\DishCategories\Pages;

use App\Filament\Resources\DishCategories\DishCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDishCategory extends EditRecord
{
    protected static string $resource = DishCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
