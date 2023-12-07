<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Plan;
use App\Models\RecipeIngredient;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_type',
        'plan_id',
        'recipe_date',
        'name',
        'instructions',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
