<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // حفظ الصورة
        $path = $request->file('image')->store('product_images', 'public');

        $image = ProductImage::create([
            'product_id' => $request->product_id,
            'image_path' => $path,
        ]);

        return response()->json([
            'status' => 201,
            'data' => $image,
            'message' => 'Product image uploaded successfully'
        ]);
    }

    // عرض صور منتج معين
    public function index($productId)
    {
        $product = Product::with('images')->find($productId);

        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'product not found'
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => $product->images,
            'message' => 'All product images'
        ]);
    }

    // حذف صورة
    public function destroy($id)
    {
        $image = ProductImage::find($id);

        if (!$image) {
            return response()->json([
                'status' => 404,
                'message' => 'Image not found'
            ]);
        }

        // حذف الصورة من التخزين
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Image deleted successfully'
        ]);
    }
}
