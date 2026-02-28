<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_visits', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('comment');
            $table->timestamp('approved_at')->nullable()->after('completed_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_visits', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['completed_at', 'approved_at', 'approved_by']);
        });
    }
};
