<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/QuizController.php
| Описание: ПОЛНЫЙ контроллер астрологического квиза
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\QuizRule;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QuizController extends Controller
{
    /**
     * Расчет рекомендаций на основе данных рождения
     * POST /api/quiz
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'day' => 'required|integer|min:1|max:31',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:1920|max:2024',
            'hour' => 'required|integer|min:0|max:23'
        ], [
            'day.required' => 'День рождения обязателен',
            'day.integer' => 'День должен быть числом',
            'day.min' => 'День не может быть меньше 1',
            'day.max' => 'День не может быть больше 31',
            'month.required' => 'Месяц рождения обязателен',
            'month.integer' => 'Месяц должен быть числом',
            'month.min' => 'Месяц не может быть меньше 1',
            'month.max' => 'Месяц не может быть больше 12',
            'year.required' => 'Год рождения обязателен',
            'year.integer' => 'Год должен быть числом',
            'year.min' => 'Год не может быть меньше 1920',
            'year.max' => 'Год не может быть больше 2024',
            'hour.required' => 'Час рождения обязателен',
            'hour.integer' => 'Час должен быть числом',
            'hour.min' => 'Час не может быть меньше 0',
            'hour.max' => 'Час не может быть больше 23'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Проверка корректности даты
            if (!checkdate($request->month, $request->day, $request->year)) {
                return response()->json([
                    'error' => 'Некорректная дата рождения'
                ], 422);
            }

            // Поиск подходящего правила
            $rule = $this->findMatchingRule($request->month, $request->day, $request->hour);
            
            if (!$rule) {
                // Возвращаем общие рекомендации если нет точного правила
                return $this->getGeneralRecommendations($request);
            }

            // Поиск товаров с рекомендуемыми камнями
            $products = $this->findProductsByStones($rule->stones);

            // Дополнительная астрологическая информация
            $astroInfo = $this->getAstrologicalInfo($request->month, $request->day);

            return response()->json([
                'recommended_stones' => $rule->stones,
                'astrological_info' => $astroInfo,
                'products' => $products,
                'rule_description' => $rule->description,
                'message' => 'Рекомендации составлены на основе даты и времени рождения'
            ]);

        } catch (\Exception $e) {
            Log::error('Quiz calculation error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка при расчете рекомендаций'
            ], 500);
        }
    }

    /**
     * Поиск подходящего правила квиза
     */
    private function findMatchingRule(int $month, int $day, int $hour): ?QuizRule
    {
        return QuizRule::where('month', $month)
            ->where('day', $day)
            ->where('hour_start', '<=', $hour)
            ->where('hour_end', '>=', $hour)
            ->first();
    }

    /**
     * Общие рекомендации если нет точного правила
     */
    private function getGeneralRecommendations(Request $request): JsonResponse
    {
        // Базовые рекомендации по знакам зодиака
        $zodiacStones = $this->getZodiacStones($request->month, $request->day);
        
        // Поиск товаров с этими камнями
        $products = $this->findProductsByStones($zodiacStones);
        
        // Астрологическая информация
        $astroInfo = $this->getAstrologicalInfo($request->month, $request->day);

        return response()->json([
            'recommended_stones' => $zodiacStones,
            'astrological_info' => $astroInfo,
            'products' => $products,
            'message' => 'Общие рекомендации на основе знака зодиака'
        ]);
    }

    /**
     * Получить камни по знаку зодиака
     */
    private function getZodiacStones(int $month, int $day): array
    {
        $zodiacSigns = [
            'Овен' => ['агат', 'гранат', 'рубин'],
            'Телец' => ['изумруд', 'малахит', 'агат'],
            'Близнецы' => ['топаз', 'агат', 'хризолит'],
            'Рак' => ['жемчуг', 'лунный камень', 'изумруд'],
            'Лев' => ['янтарь', 'топаз', 'рубин'],
            'Дева' => ['сапфир', 'агат', 'хризолит'],
            'Весы' => ['опал', 'лазурит', 'турмалин'],
            'Скорпион' => ['гранат', 'топаз', 'малахит'],
            'Стрелец' => ['турмалин', 'топаз', 'гранат'],
            'Козерог' => ['агат', 'гранат', 'малахит'],
            'Водолей' => ['аметист', 'сапфир', 'гранат'],
            'Рыбы' => ['аметист', 'жемчуг', 'лунный камень']
        ];

        $sign = $this->getZodiacSign($month, $day);
        return $zodiacSigns[$sign] ?? ['агат', 'турмалин'];
    }

    /**
     * Определить знак зодиака
     */
    private function getZodiacSign(int $month, int $day): string
    {
        $signs = [
            ['name' => 'Козерог', 'start' => [12, 22], 'end' => [1, 19]],
            ['name' => 'Водолей', 'start' => [1, 20], 'end' => [2, 18]],
            ['name' => 'Рыбы', 'start' => [2, 19], 'end' => [3, 20]],
            ['name' => 'Овен', 'start' => [3, 21], 'end' => [4, 19]],
            ['name' => 'Телец', 'start' => [4, 20], 'end' => [5, 20]],
            ['name' => 'Близнецы', 'start' => [5, 21], 'end' => [6, 20]],
            ['name' => 'Рак', 'start' => [6, 21], 'end' => [7, 22]],
            ['name' => 'Лев', 'start' => [7, 23], 'end' => [8, 22]],
            ['name' => 'Дева', 'start' => [8, 23], 'end' => [9, 22]],
            ['name' => 'Весы', 'start' => [9, 23], 'end' => [10, 22]],
            ['name' => 'Скорпион', 'start' => [10, 23], 'end' => [11, 21]],
            ['name' => 'Стрелец', 'start' => [11, 22], 'end' => [12, 21]]
        ];

        foreach ($signs as $sign) {
            [$startMonth, $startDay] = $sign['start'];
            [$endMonth, $endDay] = $sign['end'];
            
            if ($startMonth == $endMonth) {
                if ($month == $startMonth && $day >= $startDay && $day <= $endDay) {
                    return $sign['name'];
                }
            } else {
                if (($month == $startMonth && $day >= $startDay) || 
                    ($month == $endMonth && $day <= $endDay)) {
                    return $sign['name'];
                }
            }
        }

        return 'Козерог'; // по умолчанию
    }

    /**
     * Поиск товаров по камням
     */
    private function findProductsByStones(array $stones): \Illuminate\Support\Collection
    {
        $products = collect();
        
        foreach ($stones as $stone) {
            // Ищем атрибуты камней
            $stoneAttribute = Attribute::where('name', 'like', '%' . $stone . '%')->first();
            
            if ($stoneAttribute) {
                // Ищем товары с этим атрибутом
                $stoneProducts = Product::whereHas('attributes', function ($query) use ($stoneAttribute) {
                    $query->where('attributes.id', $stoneAttribute->id);
                })
                ->with(['theme', 'categories'])
                ->take(3)
                ->get();
                
                $products = $products->concat($stoneProducts);
            }
        }

        // Убираем дубликаты и берем топ-9
        return $products->unique('id')->take(9);
    }

    /**
     * Получить астрологическую информацию
     */
    private function getAstrologicalInfo(int $month, int $day): array
    {
        $sign = $this->getZodiacSign($month, $day);
        
        $info = [
            'zodiac_sign' => $sign,
            'element' => $this->getElement($sign),
            'lucky_day' => $this->getLuckyDay($sign),
            'characteristics' => $this->getCharacteristics($sign)
        ];

        return $info;
    }

    /**
     * Получить стихию знака
     */
    private function getElement(string $sign): string
    {
        $elements = [
            'Огонь' => ['Овен', 'Лев', 'Стрелец'],
            'Земля' => ['Телец', 'Дева', 'Козерог'],
            'Воздух' => ['Близнецы', 'Весы', 'Водолей'],
            'Вода' => ['Рак', 'Скорпион', 'Рыбы']
        ];

        foreach ($elements as $element => $signs) {
            if (in_array($sign, $signs)) {
                return $element;
            }
        }

        return 'Неизвестно';
    }

    /**
     * Получить счастливый день
     */
    private function getLuckyDay(string $sign): string
    {
        $days = [
            'Овен' => 'Вторник',
            'Телец' => 'Пятница',
            'Близнецы' => 'Среда',
            'Рак' => 'Понедельник',
            'Лев' => 'Воскресенье',
            'Дева' => 'Среда',
            'Весы' => 'Пятница',
            'Скорпион' => 'Вторник',
            'Стрелец' => 'Четверг',
            'Козерог' => 'Суббота',
            'Водолей' => 'Суббота',
            'Рыбы' => 'Четверг'
        ];

        return $days[$sign] ?? 'Неизвестно';
    }

    /**
     * Получить характеристики знака
     */
    private function getCharacteristics(string $sign): array
    {
        $characteristics = [
            'Овен' => ['энергичность', 'смелость', 'лидерство'],
            'Телец' => ['стабильность', 'упорство', 'практичность'],
            'Близнецы' => ['общительность', 'гибкость', 'любознательность'],
            'Рак' => ['чувствительность', 'заботливость', 'интуиция'],
            'Лев' => ['великодушие', 'творчество', 'благородство'],
            'Дева' => ['аналитичность', 'практичность', 'внимательность'],
            'Весы' => ['гармония', 'справедливость', 'дипломатичность'],
            'Скорпион' => ['страстность', 'проницательность', 'загадочность'],
            'Стрелец' => ['оптимизм', 'философия', 'свобода'],
            'Козерог' => ['амбициозность', 'дисциплина', 'ответственность'],
            'Водолей' => ['независимость', 'оригинальность', 'гуманность'],
            'Рыбы' => ['интуиция', 'сострадание', 'творчество']
        ];

        return $characteristics[$sign] ?? ['индивидуальность'];
    }

    // ============ ADMIN МЕТОДЫ ============

    /**
     * Список всех правил квиза (админка)
     * GET /api/admin/quiz_rules
     */
    public function index(): JsonResponse
    {
        $rules = QuizRule::orderBy('month')
            ->orderBy('day')
            ->orderBy('hour_start')
            ->get();

        return response()->json($rules);
    }

    /**
     * Создание правила квиза (админка)
     * POST /api/admin/quiz_rules
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'day' => 'required|integer|min:1|max:31',
            'hour_start' => 'required|integer|min:0|max:23',
            'hour_end' => 'required|integer|min:0|max:23|gte:hour_start',
            'stones' => 'required|array|min:1',
            'stones.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка корректности даты
        if (!checkdate($request->month, $request->day, 2024)) {
            return response()->json([
                'error' => 'Некорректная дата'
            ], 422);
        }

        // Проверка пересечения с другими правилами
        $exists = QuizRule::where('month', $request->month)
            ->where('day', $request->day)
            ->where(function ($query) use ($request) {
                $query->whereBetween('hour_start', [$request->hour_start, $request->hour_end])
                      ->orWhereBetween('hour_end', [$request->hour_start, $request->hour_end])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('hour_start', '<=', $request->hour_start)
                            ->where('hour_end', '>=', $request->hour_end);
                      });
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Для указанной даты и времени уже существует правило'
            ], 409);
        }

        $rule = QuizRule::create($request->only([
            'month', 'day', 'hour_start', 'hour_end', 'stones'
        ]));

        return response()->json([
            'message' => 'Правило успешно создано',
            'rule' => $rule
        ], 201);
    }

    /**
     * Получение правила
     * GET /api/admin/quiz_rules/{id}
     */
    public function show(QuizRule $quizRule): JsonResponse
    {
        return response()->json($quizRule);
    }

    /**
     * Обновление правила
     * PUT /api/admin/quiz_rules/{id}
     */
    public function update(Request $request, QuizRule $quizRule): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'day' => 'required|integer|min:1|max:31',
            'hour_start' => 'required|integer|min:0|max:23',
            'hour_end' => 'required|integer|min:0|max:23|gte:hour_start',
            'stones' => 'required|array|min:1',
            'stones.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка корректности даты
        if (!checkdate($request->month, $request->day, 2024)) {
            return response()->json([
                'error' => 'Некорректная дата'
            ], 422);
        }

        // Проверка пересечения с другими правилами (исключаем текущее)
        $exists = QuizRule::where('id', '!=', $quizRule->id)
            ->where('month', $request->month)
            ->where('day', $request->day)
            ->where(function ($query) use ($request) {
                $query->whereBetween('hour_start', [$request->hour_start, $request->hour_end])
                      ->orWhereBetween('hour_end', [$request->hour_start, $request->hour_end])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('hour_start', '<=', $request->hour_start)
                            ->where('hour_end', '>=', $request->hour_end);
                      });
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Для указанной даты и времени уже существует правило'
            ], 409);
        }

        $quizRule->update($request->only([
            'month', 'day', 'hour_start', 'hour_end', 'stones'
        ]));

        return response()->json([
            'message' => 'Правило успешно обновлено',
            'rule' => $quizRule
        ]);
    }

    /**
     * Удаление правила
     * DELETE /api/admin/quiz_rules/{id}
     */
    public function destroy(QuizRule $quizRule): JsonResponse
    {
        $quizRule->delete();

        return response()->json([
            'message' => 'Правило успешно удалено'
        ]);
    }

    /**
     * Получение списка доступных камней
     * GET /api/admin/quiz_rules/stones
     */
    public function stones(): JsonResponse
    {
        // Получаем камни из атрибутов
        $stones = Attribute::all()
            ->filter(function ($attribute) {
                return $attribute->isStone();
            })
            ->pluck('name')
            ->values();

        return response()->json($stones);
    }
}