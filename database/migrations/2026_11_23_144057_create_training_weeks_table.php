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
        Schema::create('training_weeks', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on')->unique();
            $table->string('kind');
            $table->unsignedSmallInteger('number');
            $table->string('phase');
            $table->timestamps();

            $table->unique(['kind', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_weeks');
    }
};
