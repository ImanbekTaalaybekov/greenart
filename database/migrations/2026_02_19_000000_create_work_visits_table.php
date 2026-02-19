<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->date('visit_date');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['worker_id', 'order_id', 'visit_date']);
            $table->index(['worker_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_visits');
    }
};
