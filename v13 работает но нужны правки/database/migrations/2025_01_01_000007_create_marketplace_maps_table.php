<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/migrations/2025_01_01_000007_create_marketplace_maps_table.php
| Описание: Маппинг атрибутов между нашей БД и маркетплейсами (WB, Ozon, Яндекс, flowwow)
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ВАЖНО: Только 4 маркетплейса согласно ТЗ!
     */
    public function up(): void
    {
        Schema::create('marketplace_maps', function (Blueprint $table) {
            $table->id();
            
            // ENUM строго по ТЗ: 'wildberries', 'ozon', 'yandex_market', 'flowwow'
            $table->enum('marketplace', ['wildberries', 'ozon', 'yandex_market', 'flowwow']);
            
            // Наш атрибут
            $table->foreignId('our_attr_id')->constrained('attributes')->onDelete('cascade');
            
            // Название атрибута в маркетплейсе
            $table->string('marketplace_attr_name', 255);
            
            $table->timestamps();
            
            // Уникальность: один атрибут = одно соответствие на маркетплейсе
            $table->unique(['marketplace', 'our_attr_id']);
            
            // Индексы
            $table->index('marketplace');
            $table->index('our_attr_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_maps');
    }
};