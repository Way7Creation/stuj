<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/migrations/2025_01_02_000001_add_parent_id_to_categories_table.php
| Описание: Добавление поддержки подкатегорий (иерархия категорий)
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
        Schema::table('categories', function (Blueprint $table) {
            // Добавляем поле для родительской категории
            $table->foreignId('parent_id')->nullable()->after('slug')->constrained('categories')->onDelete('cascade');
            
            // Добавляем поле сортировки
            $table->integer('sort_order')->default(0)->after('parent_id');
            
            // Добавляем SEO поля
            $table->string('meta_title', 255)->nullable()->after('sort_order');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // Добавляем индексы
            $table->index('parent_id');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'sort_order', 'meta_title', 'meta_description']);
        });
    }
};