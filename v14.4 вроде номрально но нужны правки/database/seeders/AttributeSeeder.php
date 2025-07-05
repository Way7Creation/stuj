<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/database/seeders/AttributeSeeder.php
| Описание: Заполнение таблицы атрибутов камнями и металлами
|--------------------------------------------------------------------------
*/

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * ВАЖНО: Камни используются в квизе для астрологического подбора!
     */
    public function run(): void
    {
        // Натуральные камни
        $stones = [
            'агат',
            'турмалин',
            'аметист',
            'хризолит',
            'гранат',
            'лазурит',
            'малахит',
            'янтарь',
            'жемчуг',
            'опал',
            'топаз',
            'изумруд',
            'рубин',
            'сапфир',
            'алмаз',
            'кварц',
            'оникс',
            'обсидиан',
            'лунный камень',
            'тигровый глаз'
        ];

        // Металлы
        $metals = [
            'серебро',
            'золото',
            'латунь',
            'медь',
            'сталь',
            'титан'
        ];

        // Создаем атрибуты для камней
        foreach ($stones as $stone) {
            Attribute::create(['name' => $stone]);
        }

        // Создаем атрибуты для металлов
        foreach ($metals as $metal) {
            Attribute::create(['name' => $metal]);
        }

        $total = count($stones) + count($metals);
        $this->command->info("Атрибуты созданы: {$total} (камни: " . count($stones) . ", металлы: " . count($metals) . ")");
    }
}