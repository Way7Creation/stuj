<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/migrations/2025_01_01_000008_create_marketplace_maps_table.php
| Описание: Создание таблицы маппинга атрибутов для маркетплейсов
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
        Schema::create('marketplace_maps', function (Blueprint $table) {
            $table->id();
            $table->enum('marketplace', ['wildberries', 'ozon', 'yandex_market', 'flowwow'])
                  ->comment('Название маркетплейса');
            $table->foreignId('our_attr_id')
                  ->constrained('attributes')
                  ->onDelete('cascade')
                  ->comment('ID нашего атрибута');
            $table->string('marketplace_attr_name', 255)
                  ->comment('Название атрибута в маркетплейсе');
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('marketplace');
            $table->index('our_attr_id');
            
            // Уникальность: один наш атрибут = одно название в маркетплейсе
            $table->unique(['marketplace', 'our_attr_id']);
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