<?php

/*
|--------------------------------------------------------------------------
| Путь: создать в database/migrations/
| Имя файла: 2025_01_03_000001_recreate_product_attributes_table.php
| Описание: ПРАВИЛЬНАЯ миграция для product_attributes с поддержкой значений
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Удаляем старую таблицу если существует
        Schema::dropIfExists('product_attributes');
        
        // Создаем новую таблицу с правильной структурой
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('attributes')->onDelete('cascade');
            
            // Ссылка на конкретное значение атрибута (может быть null для custom значений)
            $table->foreignId('attribute_value_id')->nullable()->constrained('attribute_values')->onDelete('cascade');
            
            // Кастомное значение (если не используется стандартное значение)
            $table->string('custom_value', 255)->nullable();
            
            // Индексы для быстрых выборок
            $table->index('product_id');
            $table->index('attribute_id');
            $table->index('attribute_value_id');
            
            // НЕ делаем primary key из product_id + attribute_id, 
            // так как один товар может иметь несколько значений одного атрибута
            // (например, несколько камней в одном изделии)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};