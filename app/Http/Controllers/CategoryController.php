<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // عرض كل التصنيفات
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            "status" => 200,
            "message" => "Categories retrieved successfully",
            "data" => $categories
        ]);
    }

    // إنشاء تصنيف جديد
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:categories,name',
            'slug' => 'required|string|unique:categories,slug',
            'parent_id' => 'exists:categories,id',
        ]);

        $category = Category::create($data);

        return response()->json([
            'status' => 201,
            'message' => 'Category created successfully',
            'data' => $category,
        ]);
    }

    // عرض تصنيف معين
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "status" => 404,
                "message" => "Category not found",

            ]);
        }

        return response()->json([
            "status" => 200,
            "message" => "Category retrieved successfully",
            "data" => $category
        ]);
    }

    // تحديث تصنيف
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "status" => 404,
                "message" => "Category not found",
            ]);
        }

        $data = $request->validate([
            'name' => 'required|string|unique:categories,name,' . $category->id,
            'slug' => 'required|string|unique:categories,slug,' . $category->id,
        ]);

        $category->update($data);

        return response()->json([
            'status' => 200,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    // حذف تصنيف
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "status" => 404,
                "message" => "Category not found"
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Category deleted successfully',
        ]);
    }
}
