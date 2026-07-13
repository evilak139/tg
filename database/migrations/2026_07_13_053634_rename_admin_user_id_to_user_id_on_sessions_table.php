<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 修正初始化时的一个失误：Laravel的DatabaseSessionHandler把当前登录用户ID硬编码写入
 * sessions.user_id这个列名（不可配置），当时误改成了admin_user_id，导致Filament后台
 * 一登录写session就报"Unknown column 'user_id'"。改回框架约定的列名。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->renameColumn('admin_user_id', 'user_id');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->renameColumn('user_id', 'admin_user_id');
        });
    }
};
