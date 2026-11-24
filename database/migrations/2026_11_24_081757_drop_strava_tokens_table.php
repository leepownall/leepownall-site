<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Strava data now arrives through the MCP server, so the site no longer
     * holds Strava API tokens.
     */
    public function up(): void
    {
        Schema::dropIfExists('strava_tokens');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('strava_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('athlete_id')->unique();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
