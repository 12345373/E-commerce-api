<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // عرض كل المراجعات لمنتج معين
    public function index($productId)
    {
        $reviews = Review::where('product_id', $productId)->with('user')->get();

        return response()->json([
            'status' => 200,
            'data' => $reviews,
        ]);
    }

    // إضافة مراجعة جديدة (فقط للـ customers المسجلين)
public function store(Request $request)
{
    $user = Auth::user();

    if ($user->role->name !== 'Customer') {
        return response()->json([
            'status' => 403,
            'message' => 'Only customers can add reviews.',
        ], 403);
    }

    $request->validate([
        'product_id' => 'required|exists:products,id',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string',
    ]);

    // تحقق إذا المستخدم قام بتقييم هذا المنتج من قبل
    $existingReview = Review::where('user_id', $user->id)
                            ->where('product_id', $request->product_id)
                            ->first();

    if ($existingReview) {
        return response()->json([
            'status' => 409,
            'message' => 'You have already reviewed this product.'
        ], 409);
    }

    $review = Review::create([
        'user_id' => $user->id,
        'product_id' => $request->product_id,
        'rating' => $request->rating,
        'comment' => $request->comment,
    ]);

    return response()->json([
        'status' => 201,
        'data' => $review,
        'message' => 'Review added successfully',
    ]);
}


    // حذف مراجعة (العميل نفسه أو الادمن فقط)
    public function destroy($id)
    {
        $user = Auth::user();
        $review = Review::findOrFail($id);

        if ($user->role !== 'Admin' && $user->id !== $review->user_id) {
            return response()->json([
                'status' => 403,
                'message' => 'You are not authorized to delete this review.',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Review deleted successfully',
        ]);
    }
}
