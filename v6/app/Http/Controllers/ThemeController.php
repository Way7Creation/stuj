<?php

/*
|--------------------------------------------------------------------------
| app/Http/Controllers/ThemeController.php  
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ThemeController extends Controller
{
    public function index(): JsonResponse
    {
        $themes = Theme::orderBy('name')->get();
        return response()->json($themes);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:themes'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Ошибка валидации', 'errors' => $validator->errors()], 422);
        }

        $theme = Theme::create(['name' => $request->name]);
        return response()->json(['message' => 'Тема создана', 'theme' => $theme], 201);
    }

    public function show(Theme $theme): JsonResponse
    {
        return response()->json($theme);
    }

    public function update(Request $request, Theme $theme): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:themes,name,' . $theme->id
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Ошибка валидации', 'errors' => $validator->errors()], 422);
        }

        $theme->update(['name' => $request->name]);
        return response()->json(['message' => 'Тема обновлена', 'theme' => $theme]);
    }

    public function destroy(Theme $theme): JsonResponse
    {
        $theme->delete();
        return response()->json(['message' => 'Тема удалена']);
    }
}
