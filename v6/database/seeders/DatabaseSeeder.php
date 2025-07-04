<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/seeders/DatabaseSeeder.php
| Описание: Главный сидер для первоначального заполнения базы данных
|--------------------------------------------------------------------------
*/

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Создаем администратора
        User::create([
            'name' => 'Admin',
            'email' => 'admin@stuj.ru',
            'password' => Hash::make('password'), // ВАЖНО: Поменять после первого входа!
        ]);

        // Запускаем остальные сидеры
        $this->call([
            CategorySeeder::class,
            ThemeSeeder::class,
            AttributeSeeder::class,
        ]);

        // Сообщение после установки
        $this->command->info('База данных заполнена!');
        $this->command->warn('ВАЖНО: Измените пароль администратора после первого входа!');
        $this->command->info('Email: admin@stuj.ru');
        $this->command->info('Пароль: password');
    }
}