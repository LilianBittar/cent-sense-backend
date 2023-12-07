<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredient_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_ingredient_id')->foreign('recipe_ingredient_id')->references('id')->on('recipe_ingredients');
            $table->integer('prod_id');
            $table->decimal('price', 8, 2);
            $table->string('description');
            $table->string('title');
            $table->string('link');
            $table->string('img');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_products');
    }
};
