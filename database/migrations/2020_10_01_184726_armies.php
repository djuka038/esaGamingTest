<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Armies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('armies', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name', 127);
            $table->enum('strategy', ['random', 'weakest', 'strongest']);
            $table->timestamps();

            $table->unique('name', 'uq_armies_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('armies');
    }
}
