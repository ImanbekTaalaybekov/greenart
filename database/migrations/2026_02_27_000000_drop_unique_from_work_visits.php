<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_visits', function (Blueprint $table) {
            $table->dropUnique(['worker_id', 'order_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::table('work_visits', function (Blueprint $table) {
            $table->unique(['worker_id', 'order_id', 'visit_date']);
        });
    }
};
