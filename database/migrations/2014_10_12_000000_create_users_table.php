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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->enum('role', ['user','doctor','pharmacy','labortory']);
            $table->enum('gender', ['male','female','other'])->nullable();
            $table->enum('signin_mode', ['social','phone'])->nullable();
            
            $table->string('phone_number')->nullable();
            $table->string('dob')->nullable();
            $table->string('language')->nullable();
            $table->string('height_feet')->nullable();
            $table->string('height_inch')->nullable();
            $table->string('weight')->nullable();
            $table->string('weight_type')->nullable();
            
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('state')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            
            $table->boolean('profile_completed')->default(0);
            $table->boolean('is_approved')->default(0);
            $table->string('device_type')->nullable();
            $table->string('device_token')->nullable();
            $table->string('social_type')->nullable();
            $table->string('social_token')->nullable();
            $table->boolean('is_social')->nullable();
            $table->enum('is_active',[0,1])->default(1);
            
            $table->boolean('push_notification')->default(1);
            $table->string('customer_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('card_id')->nullable();

            $table->rememberToken()->nullable();
            




            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
