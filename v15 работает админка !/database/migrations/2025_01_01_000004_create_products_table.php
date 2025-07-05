<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/migrations/2025_01_01_000004_create_products_table.php
| Описание: Создание главной таблицы товаров с поддержкой эффекта "матрёшки"
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ВАЖНО: Структура JSON полей строго по ТЗ!
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            
            // Эффект матрёшки - двухслойные изображения
            $table->boolean('use_matryoshka')->default(false);
            
            // JSON: {"outer": "path/to/outer.png", "inner": "path/to/inner.png"}
            $table->json('image_layers')->nullable();
            
            // JSON: ["img1.jpg", "img2.jpg", "img3.jpg"]
            $table->json('gallery_images')->nullable();
            
            // Связь с темой
            $table->foreignId('theme_id')->nullable()->constrained('themes')->onDelete('set null');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('slug');
            $table->index('theme_id');
            $table->index('price');
            $table->index('use_matryoshka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};