<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Dish;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
       return $schema
            ->components([
                //
                Fieldset::make('dettagli ordine')->schema([
                    Section::make([
                        Placeholder::make('order')
                        //->label('dettagli carrello')
                        ->content(function($record){
                            // con il cast 'array' del model, order può essere già un array o una stringa JSON
                            $order = is_string($record->order) ? json_decode($record->order, TRUE) : $record->order;
                            $totale = 0;
                            $table = array_map(function($a) use (&$totale){
                                $dish = Dish::where('price_id', $a['price'])->first();
                                if (! $dish) {
                                    return "<li>piatto non trovato (".e($a['price']).") - ".$a['quantity']." x ?</li>";
                                }
                                $totale += $dish['price']*$a['quantity'];
                                return "<li>".$dish['name']." -  ".$a['quantity']." x ".$dish['price']."€</li>";
                            }, (array) $order);
                            $table = "<ul>".implode("", $table)."</ul>";
                            $table .= "Totale: ".$totale."€";   
                            return new HtmlString($table);
                        })                        
                    ])
                    
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('user.email')->label('Cliente')->formatStateUsing(function($record){
                    $u = $record->user;
                    return $u->name." ".$u->lastname;
                }),
                TextColumn::make('restaurant.name')->label('Ristorante'),
                TextColumn::make('status')->label('Stato')
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
