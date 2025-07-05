<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/QuizRuleController.php
| Описание: CRUD контроллер для управления правилами астрологического квиза
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\QuizRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    /**
     * Список всех правил квиза
     * GET /api/admin/quiz_rules
     */
    public function index(Request $request): JsonResponse
    {
        $query = QuizRule::query();
        
        // Фильтр по месяцу
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        
        // Фильтр по дню
        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }
        
        $rules = $query->orderBy('month')
                      ->orderBy('day')
                      ->orderBy('hour_start')
                      ->get();
        
        return response()->json($rules);
    }

    /**
     * Создание нового правила
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
        ], [
            'month.required' => 'Месяц обязателен',
            'month.min' => 'Месяц должен быть от 1 до 12',
            'month.max' => 'Месяц должен быть от 1 до 12',
            'day.required' => 'День обязателен',
            'day.min' => 'День должен быть от 1 до 31',
            'day.max' => 'День должен быть от 1 до 31',
            'hour_start.required' => 'Начальный час обязателен',
            'hour_start.min' => 'Час должен быть от 0 до 23',
            'hour_start.max' => 'Час должен быть от 0 до 23',
            'hour_end.required' => 'Конечный час обязателен',
            'hour_end.gte' => 'Конечный час должен быть больше или равен начальному',
            'stones.required' => 'Необходимо указать хотя бы один камень',
            'stones.*.required' => 'Название камня не может быть пустым'
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
                'error' => 'Некорректная дата',
                'message' => 'День ' . $request->day . ' не существует в месяце ' . $request->month
            ], 422);
        }

        // Проверка пересечения с существующими правилами
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
                'error' => 'Конфликт правил',
                'message' => 'Для указанной даты и времени уже существует правило'
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
     * Получение правила по ID
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
            'month' => 'sometimes|required|integer|min:1|max:12',
            'day' => 'sometimes|required|integer|min:1|max:31',
            'hour_start' => 'sometimes|required|integer|min:0|max:23',
            'hour_end' => 'sometimes|required|integer|min:0|max:23',
            'stones' => 'sometimes|required|array|min:1',
            'stones.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка корректности даты
        $month = $request->month ?? $quizRule->month;
        $day = $request->day ?? $quizRule->day;
        if (!checkdate($month, $day, 2024)) {
            return response()->json([
                'error' => 'Некорректная дата',
                'message' => 'День ' . $day . ' не существует в месяце ' . $month
            ], 422);
        }

        // Проверка hour_end >= hour_start
        $hourStart = $request->hour_start ?? $quizRule->hour_start;
        $hourEnd = $request->hour_end ?? $quizRule->hour_end;
        if ($hourEnd < $hourStart) {
            return response()->json([
                'error' => 'Некорректное время',
                'message' => 'Конечный час должен быть больше или равен начальному'
            ], 422);
        }

        // Проверка пересечения с другими правилами
        $exists = QuizRule::where('id', '!=', $quizRule->id)
            ->where('month', $month)
            ->where('day', $day)
            ->where(function ($query) use ($hourStart, $hourEnd) {
                $query->whereBetween('hour_start', [$hourStart, $hourEnd])
                      ->orWhereBetween('hour_end', [$hourStart, $hourEnd])
                      ->orWhere(function ($q) use ($hourStart, $hourEnd) {
                          $q->where('hour_start', '<=', $hourStart)
                            ->where('hour_end', '>=', $hourEnd);
                      });
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Конфликт правил',
                'message' => 'Для указанной даты и времени уже существует правило'
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
        // Можно получать из атрибутов, которые являются камнями
        $stones = \App\Models\Attribute::all()
            ->filter(function ($attribute) {
                return $attribute->isStone();
            })
            ->pluck('name')
            ->values();

        return response()->json($stones);
    }
}