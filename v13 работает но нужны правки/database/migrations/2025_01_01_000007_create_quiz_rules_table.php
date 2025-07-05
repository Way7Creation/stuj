<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/migrations/2025_01_01_000007_create_quiz_rules_table.php
| Описание: Создание таблицы правил астрологического квиза
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
        Schema::create('quiz_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('month')->comment('Месяц рождения (1-12)');
            $table->integer('day')->comment('День рождения (1-31)');
            $table->integer('hour_start')->comment('Начальный час (0-23)');
            $table->integer('hour_end')->comment('Конечный час (0-23)');
            $table->json('stones')->comment('Массив рекомендуемых камней');
            $table->timestamps();
            
            // Индексы для быстрого поиска по дате
            $table->index(['month', 'day']);
            $table->index(['hour_start', 'hour_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_rules');
    }
};