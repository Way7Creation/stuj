<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/migrations/2025_01_02_000002_create_attribute_values_table.php
| Описание: Создание таблицы значений атрибутов для современного интернет-магазина
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
        // Создаем таблицу значений атрибутов
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->onDelete('cascade');
            $table->string('value', 255); // Значение атрибута (например: "Красный", "Синий")
            $table->string('slug', 255)->nullable(); // SEO-дружественный URL
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->boolean('is_active')->default(true); // Активность значения
            $table->timestamps();
            
            // Индексы
            $table->index('attribute_id');
            $table->index('slug');
            $table->index('sort_order');
            $table->index('is_active');
            
            // Уникальность: одно значение на атрибут
            $table->unique(['attribute_id', 'value']);
        });

        // Модифицируем связь товаров с атрибутами для поддержки значений
        Schema::table('product_attributes', function (Blueprint $table) {
            // Добавляем ссылку на конкретное значение атрибута
            $table->foreignId('attribute_value_id')->nullable()->after('attribute_id')->constrained('attribute_values')->onDelete('cascade');
            
            // Добавляем поле для кастомного значения (если нужно уникальное значение)
            $table->string('custom_value', 255)->nullable()->after('attribute_value_id');
            
            // Индекс для быстрого поиска
            $table->index('attribute_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropForeign(['attribute_value_id']);
            $table->dropColumn(['attribute_value_id', 'custom_value']);
        });
        
        Schema::dropIfExists('attribute_values');
    }
};