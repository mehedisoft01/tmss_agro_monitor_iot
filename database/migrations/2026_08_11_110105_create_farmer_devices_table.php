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
        Schema::create('farmer_devices', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_id')->nullable();
            $table->string('device_id')->nullable();
            $table->integer('status')->default(1)->comment('1=Active, 0=InActive');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmer_devices');
    }
};
