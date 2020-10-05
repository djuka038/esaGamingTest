<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GameParticipants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_participants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('game_id')->unsigned();
            $table->integer('participant_id')->unsigned();
            $table->smallInteger('order_of_play');
            $table->enum('status', ['alive', 'dead'])->default('alive');
            $table->timestamps();

            $table->unique(['game_id', 'participant_id'], 'uq_game_participants_game_id_participant_id');
            $table->unique(['game_id', 'participant_id', 'order_of_play'], 'uq_game_participants_game_id_participant_id_order_of_play');
            $table->foreign('game_id', 'fk_game_participants_game_id_games_id')->references('id')->on('games')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('participant_id', 'fk_game_participants_participant_id_armies_id')->references('id')
                ->on('armies')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_participants');
    }
}
