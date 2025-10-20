<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->string('name');
            $table->string('type');
            $table->float('distance')->nullable();
            $table->integer('moving_time')->nullable();
            $table->integer('elapsed_time')->nullable();
            $table->float('total_elevation_gain')->nullable();
            $table->dateTime('started_at');
            $table->mediumText('path');
            $table->float('max_elevation')->nullable();
            $table->float('min_elevation')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
};
