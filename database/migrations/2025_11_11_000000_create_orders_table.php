<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('worker_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('description');

            $table->enum('payment_type', ['included', 'extra'])->index();

            $table->decimal('payment_money', 12, 2)->nullable();

            $table->enum('status', ['pending', 'assigned', 'in_progress', 'done', 'cancelled'])
                  ->default('pending')
                  ->index();

            $table->timestamps();

            $table->index(['worker_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('orders');
    }
};
