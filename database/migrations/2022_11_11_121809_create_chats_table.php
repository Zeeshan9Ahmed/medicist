<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id('chat_id');
            $table->bigInteger('chat_sender_id')->unsigned();
            $table->bigInteger('chat_reciever_id')->unsigned()->nullable();
            $table->bigInteger('chat_group_id')->unsigned()->nullable();
            $table->enum('chat_group_type',['appointment','prescription_request']);
            $table->longText('chat_message');
            $table->longText('chat_type');
            $table->time('chat_read_at')->nullable();
            $table->string('chat_status')->nullable();
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
        Schema::dropIfExists('chats');
    }
}
