<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // عرض جميع المنتجات مع العلاقة مع vendor و category
    public function index()
    {
        $products = Product::with(['vendor', 'category'])->get();

        return response()->json([
            'status' => 200,
            'data' => $products,
            'message'=>"All products with the vendor and the category"
        ]);
    }

    // إنشاء منتج جديد
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->Vendor) {
            return response()->json([
                'status' => 403,
                'message' => 'Only vendors can create products.'
            ]);
        }

        // التحقق من البيانات المطلوبة مع إضافة stock
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'required|integer|min:0',
        ]);

        $data['vendor_id'] = $user->vendor->id;

        $product = Product::create($data);

        return response()->json([
            'status' => 201,
            'data' => $product,
            'message' => 'Product created successfully'
        ]);
    }

    // عرض منتج معين مع علاقاته
    public function show($id)
    {
        $product = Product::with(['vendor', 'category'])->find($id);

        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => $product,
            'message'=>"This is one product"
        ]);
    }

    // تحديث منتج
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $product = Product::find($id);

        if (!$product || !$user->vendor || $product->vendor->id !== $user->vendor->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'sometimes|integer|min:0',
        ]);

        $product->update($data);

        return response()->json([
            'status' => 200,
            'data' => $product,
            'message' => 'Product updated successfully'
        ]);
    }

    // حذف منتج
    public function destroy($id)
    {
        $user = Auth::user();
        $product = Product::find($id);

        if (!$product || !$user->vendor || $product->vendor->id !== $user->vendor->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Product deleted successfully'
        ]);
    }
}
