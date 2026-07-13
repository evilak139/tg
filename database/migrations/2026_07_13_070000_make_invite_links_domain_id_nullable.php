<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 邀请链接暂时改用Telegram原始深链（https://t.me/{bot}?start={user_id}），
 * 不再强制要求先配置启用域名才能生成邀请链接，domain_id放开成可空。
 * 短链表/跳转路由本身没删，域名配置齐了之后随时可以切回去。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invite_links', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->foreignId('domain_id')->nullable()->change();
            $table->foreign('domain_id')->references('id')->on('domains')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invite_links', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->foreignId('domain_id')->nullable(false)->change();
            $table->foreign('domain_id')->references('id')->on('domains')->restrictOnDelete();
        });
    }
};
