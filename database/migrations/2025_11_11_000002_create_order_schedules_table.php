<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->date('scheduled_for');
            $table->enum('status', ['planned', 'done', 'cancelled'])->default('planned');
            $table->timestamps();

            $table->unique(['order_id', 'scheduled_for']);
            $table->index(['worker_id', 'scheduled_for']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('order_schedules');
    }
};
