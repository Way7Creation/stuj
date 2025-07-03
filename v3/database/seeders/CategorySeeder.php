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
            'Колье',
            'Подвески',
            'Броши',
            'Комплекты'
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'slug' => Str::slug($category)
            ]);
        }

        $this->command->info('Категории созданы: ' . count($categories));
    }
}