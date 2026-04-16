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
        Schema::create('weather', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->timestamp('local_observation_date_time')->nullable();
            $table->unsignedBigInteger('epoch_time')->nullable();
            $table->string('weather_text', 100)->nullable();
            $table->integer('weather_icon')->nullable();
            $table->boolean('has_precipitation')->default(false);
            $table->string('precipitation_type')->nullable();
            $table->boolean('is_day_time')->default(true);
            $table->float('temperature')->default(0);
            $table->float('real_feel_temperature')->default(0);
            $table->string('real_feel_temperature_phrase', 100)->nullable();
            $table->float('real_feel_temperature_shade')->default(0);
            $table->string('real_feel_temperature_shade_phrase', 100)->nullable();
            $table->float('uv_index')->default(0);
            $table->string('uv_index_text', 100)->nullable();
            $table->float('wind')->default(0);
            $table->integer('wind_direction')->default(0);
            $table->string('wind_direction_text', 10)->default('');
            $table->float('wind_gust')->default(0);
            $table->float('relative_humidity')->default(0);
            $table->float('indoor_relative_humidity')->default(0);
            $table->float('dew_point')->default(0);
            $table->float('pressure')->default(0);
            $table->float('pressure_tendency')->default(0);
            $table->string('pressure_tendency_code', 10)->nullable();
            $table->float('visibility')->default(0);
            $table->float('cloud_cover')->default(0);
            $table->float('ceiling')->default(0);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather');
    }
};
