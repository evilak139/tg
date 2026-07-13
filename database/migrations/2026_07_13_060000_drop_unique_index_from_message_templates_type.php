<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 后台现在支持新建"自定义"类型的模板（供群发消息用），同一type允许多条记录，
 * 之前加的type唯一索引与此冲突，去掉。7个系统触发类型的"只能一条"约束改由
 * 应用层保证：新建页面固定写死type=自定义，编辑页面type字段禁用不可改，
 * 系统类型的唯一一条记录只由Seeder用updateOrCreate写入。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropUnique(['type']);
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->unique('type');
        });
    }
};
