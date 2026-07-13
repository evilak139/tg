<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 01文档users表未包含此字段。02文档要求邀请里程碑奖励"此前未领取过对应档位"才发放，
 * 但邀请人数是实时统计值（不是逐笔累加），没有状态记录就无法判断某个档位是否已经领过。
 * 这里补一个字段记录已领取的里程碑档位（如 [5,20]），TODO(需确认)：产品侧若已有其他
 * 判重方案（如查points_ledger流水），可以替换掉这个字段。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('milestones_claimed')->nullable()->after('is_high_value');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('milestones_claimed');
        });
    }
};
