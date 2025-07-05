<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/seeders/CategorySeeder.php
| Описание: Заполнение таблицы категорий начальными данными
|--------------------------------------------------------------------------
*/

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Кольца',
            'Браслеты', 
            'Серьги',
            'Подвески',
            'Колье',
            'Броши',
            'Цепочки',
            'Чокеры'
        ];

        foreach ($categories as $categoryName) {
            Category::create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName)
            ]);
        }

        $this->command->info('Категории созданы: ' . count($categories));
    }
}