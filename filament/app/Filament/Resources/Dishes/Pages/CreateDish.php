<?php

namespace App\Filament\Resources\Dishes\Pages;

use App\Filament\Resources\Dishes\DishResource;
use Filament\Resources\Pages\CreateRecord;
use Stripe\StripeClient;

class CreateDish extends CreateRecord
{
    protected static string $resource = DishResource::class;

    protected function afterCreate(): void
    {
        $stripe = new StripeClient(env('STRIPE_SECRET'));

        // Crea il prodotto su Stripe con il suo prezzo di default (in centesimi)
        $product = $stripe->products->create([
            'name' => $this->record->name,
            'default_price_data' => [
                'unit_amount' => (int) round($this->record->price * 100),
                'currency' => 'eur',
            ],
        ]);

        // stripe_id = prodotto, price_id = prezzo (usato da RestaurantController::placeOrder)
        $this->record->stripe_id = $product->id;
        $this->record->price_id = $product->default_price;
        $this->record->saveQuietly();
    }
}
