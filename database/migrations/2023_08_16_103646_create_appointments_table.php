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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('doctor_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('appointment_type')->nullable();
            $table->longText('note')->nullable();
            $table->decimal('fee')->nullable();
            $table->boolean('is_resheduled')->default(0);
            $table->boolean('is_cancelled')->default(0);
            $table->longText('reason')->nullable();
            $table->foreignId('appointment_id')->references('id')->on('appointments')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('is_deleted')->default(0);
            $table->json('previous_appointment')->nullable();
            // $table->integer('appointment_id')->nullable();
            $table->boolean('notification_sent')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
