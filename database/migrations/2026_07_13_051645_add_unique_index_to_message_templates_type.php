<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 01文档message_templates表没有标注type唯一，但05/07文档都是按"7种固定类型各一条"的
 * 前提设计（渲染时按type查询单条记录），加唯一约束防止后台不小心插入同type的重复行。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->unique('type');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropUnique(['type']);
        });
    }
};
