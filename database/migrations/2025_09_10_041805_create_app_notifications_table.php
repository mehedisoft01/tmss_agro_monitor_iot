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
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->integer('send_to')->nullable();
            $table->integer('type')->nullable();
            $table->integer('type_id')->nullable();
            $table->string('title');
            $table->text('notification')->nullable();
            $table->string('link')->nullable();
            $table->string('notification_to')->default(0);
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
