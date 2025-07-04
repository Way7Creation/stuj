<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/migrations/2025_01_01_000008_create_quiz_rules_table.php
| Описание: Правила квиза для астрологического подбора камней
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Структура для астрологических вычислений
     */
    public function up(): void
    {
        Schema::create('quiz_rules', function (Blueprint $table) {
            $table->id();
            
            // Месяц рождения (1-12)
            $table->integer('month');
            
            // День рождения (1-31)
            $table->integer('day');
            
            // Часовой диапазон
            $table->integer('hour_start'); // 0-23
            $table->integer('hour_end');   // 0-23
            
            // JSON массив рекомендуемых камней: ["агат", "турмалин", "аметист"]
            $table->json('stones');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска правил
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