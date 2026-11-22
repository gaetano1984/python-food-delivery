<?php

namespace App\Filament\Resources\Restaurants\Pages;

use App\Filament\Resources\Restaurants\RestaurantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    /** @var array<int, int> */
    private array $dataToSync = [];

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    #[Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // The menu_categoria_* fields are not dehydrated (they are not columns of `restaurants`),
        // so we read them from the raw form state instead of $data.
        $dishes = [];
        foreach ($this->data as $key => $value) {
            if (str_starts_with($key, 'menu_categoria_')) {
                $dishes = array_merge($dishes, (array) $value);
                unset($data[$key]);
            }
        }
        $this->dataToSync = array_values(array_unique($dishes));

        return $data;
    }

    protected function afterSave(): void
    {
        $this->getRecord()->dishes()->sync($this->dataToSync);
    }
}
