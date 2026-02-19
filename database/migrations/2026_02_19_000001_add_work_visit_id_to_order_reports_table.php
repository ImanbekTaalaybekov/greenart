<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->foreignId('work_visit_id')
                ->nullable()
                ->after('worker_id')
                ->constrained('work_visits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_visit_id');
        });
    }
};
