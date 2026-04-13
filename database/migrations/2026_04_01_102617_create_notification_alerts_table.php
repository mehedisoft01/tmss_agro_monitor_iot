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
        Schema::create('notification_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->integer('device_category_id');
            $table->integer('sensor_id');
            $table->double('current_value');
            $table->double('min_value');
            $table->double('max_value');
            $table->string('message');
            $table->boolean('is_read')->default(0);
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
        Schema::dropIfExists('notification_alerts');
    }
};
