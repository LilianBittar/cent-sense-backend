<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_ingredient_id',
        'prod_id',
        'price',
        'description',
        'title',
        'link',
        'img',
    ];

    public function recipeIngredient()
    {
        return $this->belongsTo(RecipeIngredient::class);
    }
}
