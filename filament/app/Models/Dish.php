<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dish extends Model
{
    protected $table = 'dish';
    
    protected $fillable = ['restaurant_id', 'category_id', 'name', 'description', 'image_url', 'price', 'discount_price', 'vat_rate'];

    public function ingredients(){
        return $this->belongsToMany(Ingredient::class, 'ingredients_dishes', 'dish_id', 'ingredient_id');
    }

    public function category(){
        return $this->hasOne(DishCategory::class, 'id', 'category_id');
    }
}
