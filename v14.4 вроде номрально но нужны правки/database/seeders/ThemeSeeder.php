<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/seeders/ThemeSeeder.php
| Описание: Заполнение таблицы тем начальными данными
|--------------------------------------------------------------------------
*/

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Theme;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            'Минимализм',
            'Готика',
            'Винтаж',
            'Бохо',
            'Классика',
            'Модерн',
            'Этника',
            'Романтика'
        ];

        foreach ($themes as $theme) {
            Theme::create([
                'name' => $theme
            ]);
        }

        $this->command->info('Темы созданы: ' . count($themes));
    }
}