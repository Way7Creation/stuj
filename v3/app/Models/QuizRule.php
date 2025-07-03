<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/QuizRule.php
| Описание: Правила для астрологического подбора камней по дате и времени рождения
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
     * Автоматическое преобразование JSON поля stones
     */
    protected $casts = [
        'stones' => 'array',  // ["агат", "турмалин", "аметист"]
        'month' => 'integer',
        'day' => 'integer',
        'hour_start' => 'integer',
        'hour_end' => 'integer'
    ];

    /**
     * Проверка, подходит ли правило для заданного времени
     */
    public function matchesDateTime(int $month, int $day, int $hour): bool
    {
        // Проверка месяца и дня
        if ($this->month !== $month || $this->day !== $day) {
            return false;
        }

        // Проверка часа
        if ($this->hour_start <= $this->hour_end) {
            // Обычный диапазон (например, 9-17)
            return $hour >= $this->hour_start && $hour <= $this->hour_end;
        } else {
            // Диапазон через полночь (например, 22-3)
            return $hour >= $this->hour_start || $hour <= $this->hour_end;
        }
    }

    /**
     * Найти правила для заданной даты и времени
     */
    public static function findForDateTime(int $month, int $day, int $hour)
    {
        return static::where('month', $month)
            ->where('day', $day)
            ->get()
            ->filter(function ($rule) use ($hour) {
                return $rule->matchesDateTime($month, $day, $hour);
            })
            ->first();
    }

    /**
     * Получить рекомендуемые товары по камням из правила
     */
    public function getRecommendedProducts()
    {
        if (empty($this->stones)) {
            return collect();
        }

        return Product::whereHas('attributes', function ($query) {
            $query->whereIn('name', $this->stones);
        })->get();
    }
}