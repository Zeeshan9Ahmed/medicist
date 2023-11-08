<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('to_user_id')->nullable();
            $table->integer('from_user_id')->nullable();
            // $table->string('channel_name')->nullable();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('notification_type')->nullable();
            $table->text('redirection_id')->nullable();
            $table->enum('notification_is_read', ['0', '1'])->comment('0=Not Read, 1=Readed');
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
        Schema::dropIfExists('notifications');
    }
}
