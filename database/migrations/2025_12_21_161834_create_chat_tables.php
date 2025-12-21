<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Модель Chat
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable(); // Название чата
            // Типы: 'general' (A+W), 'client_group' (A+W+K), 'private'
            $table->string('type')->default('private'); 
            $table->timestamps();
        });

        // 2. Модель Message (с полем chat_id, как ты просил)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Отправитель
            $table->text('content')->nullable();
            $table->string('attachment_path')->nullable(); // Для фото/файлов
            $table->timestamps();
        });

        // 3. Сводная таблица (Many-to-Many)
        Schema::create('chat_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_user');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chats');
    }
};