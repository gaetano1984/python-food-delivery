<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    //
    protected $fillable = ['name', 'is_allergen', 'allergen_type', 'is_removerable'];
}
