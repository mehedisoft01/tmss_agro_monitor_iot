<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('link', 50);
            $table->string('component', 255);
            $table->text('meta')->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('parent_id')->default(0);
            $table->integer('priority')->default(1);
            $table->integer( 'is_visible')->default(1);
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modules');
    }
}
