<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // кто создал заявку (клиент)
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            // назначенный сотрудник (может быть пустым до назначения)
            $table->foreignId('worker_id')->nullable()->constrained('users')->nullOnDelete();

            // текст заявки
            $table->text('description');

            // входит в обязанности (included) или платная доп.работа (extra)
            $table->enum('payment_type', ['included', 'extra'])->index();

            // сумма для extra, для included = NULL
            $table->decimal('payment_money', 12, 2)->nullable();

            // статус заявки
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'done', 'cancelled'])
                  ->default('pending')
                  ->index();

            $table->timestamps();

            // полезные индексы для типичных фильтров
            $table->index(['worker_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('orders');
    }
};
