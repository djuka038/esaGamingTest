<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Units extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units', function (BLueprint $table) {
            $table->increments('id');
            $table->integer('army_id')->unsigned();
            $table->tinyInteger('health')->unsigned();

            $table->foreign('army_id', 'fk_units_army_id_army_id')->references('id')->on('armies')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('units');
    }
}
