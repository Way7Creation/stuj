<?php


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