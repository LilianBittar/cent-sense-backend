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
        Schema::table('plans', function (Blueprint $table) {
            $table->date('start_date');
            $table->integer('number_of_days');
            $table->integer('budget');
            $table->unsignedBigInteger('user_id')->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dropColumn('number_of_days');
            $table->dropColumn('budget');
            $table->dropColumn('user_id');
        });
    }
};
