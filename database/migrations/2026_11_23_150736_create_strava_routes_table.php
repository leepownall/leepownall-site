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
        Schema::create('strava_routes', function (Blueprint $table) {
            $table->id();
            $table->string('strava_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('distance_metres');
            $table->unsignedInteger('elevation_gain_metres');
            $table->string('time');
            $table->timestamps();

            $table->index(['time', 'distance_metres']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strava_routes');
    }
};
