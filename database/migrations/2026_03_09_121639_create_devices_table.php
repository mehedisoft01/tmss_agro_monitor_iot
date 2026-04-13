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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model');
            $table->string('product_id');
            $table->string('product_name')->nullable();
            $table->boolean('online')->default(false);
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lon', 10, 6)->nullable();
            $table->string('local_key')->nullable();
            $table->string('time_zone')->nullable();
            $table->uuid('device_id');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('api_base')->default('https://openapi.tuyaeu.com');
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
        Schema::dropIfExists('devices');
    }
};
