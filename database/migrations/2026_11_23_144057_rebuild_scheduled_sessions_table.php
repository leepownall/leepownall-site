<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sessions were generated from the plan written in code, so they are
     * dropped and the table rebuilt to belong to a stored training week.
     */
    public function up(): void
    {
        Schema::dropIfExists('scheduled_sessions');

        Schema::create('scheduled_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_week_id')->constrained()->cascadeOnDelete();
            $table->date('date')->unique();
            $table->boolean('is_rest')->default(false);
            $table->string('name')->nullable();
            $table->json('lines')->nullable();
            $table->boolean('has_strength')->default(false);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_sessions');

        Schema::create('scheduled_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('kind');
            $table->unsignedSmallInteger('week_index');
            $table->unsignedSmallInteger('template');
            $table->string('phase');
            $table->boolean('is_rest')->default(false);
            $table->string('name')->nullable();
            $table->json('lines')->nullable();
            $table->boolean('has_strength')->default(false);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->timestamps();

            $table->index(['kind', 'week_index']);
        });
    }
};
