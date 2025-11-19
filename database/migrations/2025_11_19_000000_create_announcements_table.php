<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            // кто создал (админ)
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('body')->nullable();

            // кому показывать: всем, только клиентам, только сотрудникам
            $table->enum('audience', ['all','clients','workers'])->default('all')->index();

            // можно оставить на будущее (публикация по времени)
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['created_by', 'audience']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('announcements');
    }
};
