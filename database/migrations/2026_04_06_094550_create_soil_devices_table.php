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
        Schema::create('soil_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->integer('farmer_type')->nullable()->comment('1=paddy,2=vegetable');
            $table->string('device_location')->nullable();
            $table->string('device_lat')->nullable();
            $table->string('device_long')->nullable();
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
        Schema::dropIfExists('soil_devices');
    }
};
