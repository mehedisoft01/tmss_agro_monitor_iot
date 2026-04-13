<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_thresholds', function (Blueprint $table) {
            $table->id();
            $table->integer('device_category_id');
            $table->integer('sensor_id');
            $table->float('min_value')->nullable();
            $table->float('max_value')->nullable();
            $table->float('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_thresholds');
    }
};
