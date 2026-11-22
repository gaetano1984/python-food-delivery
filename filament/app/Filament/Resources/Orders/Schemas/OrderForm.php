<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Dish;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('dettagli ordine')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('carrello')
                            ->label('Dettagli carrello')
                            ->state(fn (?Order $record) => self::cartSummary($record))
                            ->html()
                            ->visibleOn('edit'),
                        Select::make('status')
                            ->label('Stato')
                            ->options([
                                'placed' => 'Ricevuto',
                                'paid' => 'Pagato',
                                'delivered' => 'Consegnato',
                                'error' => 'Errore',
                            ])
                            ->required(),
                    ]),
            ]);
    }

    private static function cartSummary(?Order $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('');
        }

        // La colonna `order` può contenere un array (cast) o una stringa JSON (salvataggio con json_encode)
        $items = $record->order;
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        $totale = 0;
        $rows = [];
        foreach ((array) $items as $item) {
            $dish = Dish::where('price_id', $item['price'] ?? null)->first();
            $qty = (int) ($item['quantity'] ?? 0);

            if (! $dish) {
                $rows[] = '<li>Piatto non trovato ('.e($item['price'] ?? '-').') - '.$qty.' x ?</li>';

                continue;
            }

            $totale += $dish->price * $qty;
            $rows[] = '<li>'.e($dish->name).' - '.$qty.' x '.number_format($dish->price, 2, ',', '.').'€</li>';
        }

        return new HtmlString(
            '<ul>'.implode('', $rows).'</ul>'
            .'<strong>Totale: '.number_format($totale, 2, ',', '.').'€</strong>'
        );
    }
}
