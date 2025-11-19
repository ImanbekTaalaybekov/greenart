<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->enum('work_type', ['included', 'extra'])->default('extra')->after('worker_id');
        });
    }

    public function down(): void {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropColumn('work_type');
        });
    }
};
