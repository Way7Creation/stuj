<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/QuizController.php
| Описание: Контроллер астрологического квиза подбора камней
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\QuizRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class QuizController extends Controller
{
    /**
     * Расчет квиза подбора камней
     * POST /api/v1/quiz
     */
    public function calculate(Request $request): JsonResponse
    {
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'day' => 'required|integer|min:1|max:31',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'hour' => 'required|integer|min:0|max:23'
        ], [
            'day.required' => 'День обязателен',
            'day.min' => 'День должен быть от 1 до 31',
            'day.max' => 'День должен быть от 1 до 31',
            'month.required' => 'Месяц обязателен',
            'month.min' => 'Месяц должен быть от 1 до 12',
            'month.max' => 'Месяц должен быть от 1 до 12',
            'year.required' => 'Год обязателен',
            'year.min' => 'Год должен быть не ранее 1900',
            'hour.required' => 'Час обязателен',
            'hour.min' => 'Час должен быть от 0 до 23',
            'hour.max' => 'Час должен быть от 0 до 23'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $day = $request->get('day');
        $month = $request->get('month');
        $year = $request->get('year');
        $hour = $request->get('hour');

        try {
            // Проверяем корректность даты
            $birthDate = Carbon::createFromDate($year, $month, $day);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Некорректная дата'
            ], 422);
        }

        // Определяем знак зодиака
        $zodiacSign = $this->getZodiacSign($month, $day);
        
        // Получаем рекомендованные камни
        $recommendedStones = $this->getRecommendedStones($month, $day, $hour, $zodiacSign);

        // Ищем товары с этими камнями
        $products = $this->findProductsWithStones($recommendedStones);

        return response()->json([
            'birth_info' => [
                'date' => $birthDate->format('d.m.Y'),
                'zodiac_sign' => $zodiacSign,
                'hour' => $hour
            ],
            'recommended_stones' => $recommendedStones,
            'products' => $products,
            'message' => count($products) > 0 
                ? "Мы нашли " . count($products) . " украшений, подходящих именно вам!"
                : "К сожалению, пока нет товаров с вашими камнями. Свяжитесь с нами для индивидуального заказа."
        ]);
    }

    /**
     * Определение знака зодиака по дате
     */
    private function getZodiacSign(int $month, int $day): string
    {
        $zodiacSigns = [
            ['name' => 'Козерог', 'start' => ['month' => 12, 'day' => 22], 'end' => ['month' => 1, 'day' => 19]],
            ['name' => 'Водолей', 'start' => ['month' => 1, 'day' => 20], 'end' => ['month' => 2, 'day' => 18]],
            ['name' => 'Рыбы', 'start' => ['month' => 2, 'day' => 19], 'end' => ['month' => 3, 'day' => 20]],
            ['name' => 'Овен', 'start' => ['month' => 3, 'day' => 21], 'end' => ['month' => 4, 'day' => 19]],
            ['name' => 'Телец', 'start' => ['month' => 4, 'day' => 20], 'end' => ['month' => 5, 'day' => 20]],
            ['name' => 'Близнецы', 'start' => ['month' => 5, 'day' => 21], 'end' => ['month' => 6, 'day' => 20]],
            ['name' => 'Рак', 'start' => ['month' => 6, 'day' => 21], 'end' => ['month' => 7, 'day' => 22]],
            ['name' => 'Лев', 'start' => ['month' => 7, 'day' => 23], 'end' => ['month' => 8, 'day' => 22]],
            ['name' => 'Дева', 'start' => ['month' => 8, 'day' => 23], 'end' => ['month' => 9, 'day' => 22]],
            ['name' => 'Весы', 'start' => ['month' => 9, 'day' => 23], 'end' => ['month' => 10, 'day' => 22]],
            ['name' => 'Скорпион', 'start' => ['month' => 10, 'day' => 23], 'end' => ['month' => 11, 'day' => 21]],
            ['name' => 'Стрелец', 'start' => ['month' => 11, 'day' => 22], 'end' => ['month' => 12, 'day' => 21]]
        ];

        foreach ($zodiacSigns as $sign) {
            if ($this->isDateInRange($month, $day, $sign['start'], $sign['end'])) {
                return $sign['name'];
            }
        }

        return 'Неизвестно';
    }

    /**
     * Проверка попадания даты в диапазон
     */
    private function isDateInRange(int $month, int $day, array $start, array $end): bool
    {
        // Особый случай для Козерога (переход через год)
        if ($start['month'] > $end['month']) {
            return ($month == $start['month'] && $day >= $start['day']) ||
                   ($month == $end['month'] && $day <= $end['day']);
        }

        return ($month > $start['month'] || ($month == $start['month'] && $day >= $start['day'])) &&
               ($month < $end['month'] || ($month == $end['month'] && $day <= $end['day']));
    }

    /**
     * Получение рекомендованных камней
     */
    private function getRecommendedStones(int $month, int $day, int $hour, string $zodiacSign): array
    {
        // Сначала пробуем найти точное правило из базы
        $rule = QuizRule::where('month', $month)
            ->where('day', $day)
            ->where('hour_start', '<=', $hour)
            ->where('hour_end', '>=', $hour)
            ->first();

        if ($rule) {
            return $rule->stones;
        }

        // Если нет точного правила, используем базовые рекомендации по знакам зодиака
        $zodiacStones = [
            'Овен' => ['гранат', 'рубин', 'алмаз'],
            'Телец' => ['изумруд', 'агат', 'нефрит'],
            'Близнецы' => ['агат', 'топаз', 'жемчуг'],
            'Рак' => ['жемчуг', 'лунный камень', 'опал'],
            'Лев' => ['рубин', 'топаз', 'янтарь'],
            'Дева' => ['сапфир', 'агат', 'яшма'],
            'Весы' => ['опал', 'лазурит', 'коралл'],
            'Скорпион' => ['гранат', 'турмалин', 'топаз'],
            'Стрелец' => ['турмалин', 'аметист', 'сапфир'],
            'Козерог' => ['оникс', 'гранат', 'малахит'],
            'Водолей' => ['аметист', 'гранат', 'сапфир'],
            'Рыбы' => ['аметист', 'жемчуг', 'аквамарин']
        ];

        return $zodiacStones[$zodiacSign] ?? ['агат', 'кварц'];
    }

    /**
     * Поиск товаров с рекомендованными камнями
     */
    private function findProductsWithStones(array $stones): array
    {
        $products = Product::whereHas('attributes', function ($query) use ($stones) {
            $query->whereIn('name', $stones);
        })
        ->with(['theme', 'categories', 'attributes'])
        ->limit(10) // Максимум 10 товаров в результате
        ->get();

        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => (float) $product->price,
                'use_matryoshka' => $product->use_matryoshka,
                'image_layers' => $product->image_layers,
                'gallery_images' => $product->gallery_images,
                'main_image' => $product->main_image,
                'matching_stones' => $product->attributes
                    ->filter(function ($attr) use ($stones) {
                        return in_array($attr->name, $stones) && $attr->isStone();
                    })
                    ->pluck('name')
                    ->toArray()
            ];
        })->toArray();
    }
}