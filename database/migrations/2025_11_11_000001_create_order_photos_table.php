<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('path');           
            $table->string('original_name'); 
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('order_photos');
    }
};

