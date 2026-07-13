<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tg_user_id')->unique();
            $table->string('tg_username')->nullable();
            $table->string('nickname');
            $table->foreignId('invited_by_l1')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invited_by_l2')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invited_by_l3')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('points_balance')->default(0);
            $table->dateTime('register_time');
            $table->dateTime('last_active_time');
            $table->integer('checkin_streak')->default(0);
            $table->date('last_checkin_date')->nullable();
            $table->string('identity_level')->default('注册会员');
            $table->string('activity_tag')->default('活跃');
            $table->boolean('is_high_value')->default(false);
            $table->string('device_fingerprint')->nullable();
            $table->string('register_ip')->nullable();
            $table->string('status')->default('正常');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
