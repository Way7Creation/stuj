<?php

/*
|--------------------------------------------------------------------------
| app/Http/Controllers/CategoryController.php
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Ошибка валидации', 'errors' => $validator->errors()], 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name)
        ]);

        return response()->json(['message' => 'Категория создана', 'category' => $category], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Ошибка валидации', 'errors' => $validator->errors()], 422);
        }

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name)
        ]);

        return response()->json(['message' => 'Категория обновлена', 'category' => $category]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'Категория удалена']);
    }
}

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

/*
|--------------------------------------------------------------------------
| app/Http/Controllers/AttributeController.php
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    public function index(): JsonResponse
    {
        $attributes = Attribute::orderBy('name')->get();
        return response()->json($attributes);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:attributes'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Ошибка валидации', 'errors' => $validator->errors()], 422);
        }

        $attribute = Attribute::create(['name' => $request->name]);
        return response()->json(['message' => 'Атрибут создан', 'attribute' => $attribute], 201);
    }

    public function show(Attribute $attribute): JsonResponse
    {
        return response()->json($attribute);
    }

    public function update(Request $request, Attribute $attribute): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:attributes,name,' . $attribute->id
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Ошибка валидации', 'errors' => $validator->errors()], 422);
        }

        $attribute->update(['name' => $request->name]);
        return response()->json(['message' => 'Атрибут обновлен', 'attribute' => $attribute]);
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $attribute->delete();
        return response()->json(['message' => 'Атрибут удален']);
    }
}