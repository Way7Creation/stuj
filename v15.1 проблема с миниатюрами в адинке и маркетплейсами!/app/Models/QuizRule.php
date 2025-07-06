<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/QuizRule.php
| Описание: Модель правил астрологического квиза
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizRule extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'month',
        'day',
        'hour_start',
        'hour_end',
        'stones'
    ];

    /**
     * Типы полей
     */
    protected $casts = [
        'month' => 'integer',
        'day' => 'integer',
        'hour_start' => 'integer',
        'hour_end' => 'integer',
        'stones' => 'array'
    ];

    /**
     * Проверка, подходит ли правило для заданного времени
     */
    public function matchesDateTime(int $month, int $day, int $hour): bool
    {
        return $this->month === $month 
            && $this->day === $day 
            && $hour >= $this->hour_start 
            && $hour <= $this->hour_end;
    }

    /**
     * Получить читаемый период времени
     */
    public function getTimeRangeAttribute(): string
    {
        return sprintf('%02d:00 - %02d:59', $this->hour_start, $this->hour_end);
    }

    /**
     * Получить читаемую дату
     */
    public function getDateAttribute(): string
    {
        $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
        ];

        return $this->day . ' ' . ($months[$this->month] ?? $this->month);
    }

    /**
     * Получить полное описание правила
     */
    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s, %s: %s',
            $this->date,
            $this->time_range,
            implode(', ', $this->stones)
        );
    }
}