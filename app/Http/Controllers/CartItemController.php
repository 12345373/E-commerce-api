<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    // إضافة أو تحديث عنصر في عربة المستخدم
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $roleName = optional($user->role)->name;

        // تأكد إن الدور هو Customer
        if ($roleName !== 'Customer') {
            return response()->json([
                'status' => 403,
                'message' => 'Only customers can add items to cart.'
            ]);
        }

        // الحصول على الكارت الخاص بالمستخدم أو إنشاؤه
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // البحث عن العنصر في الكارت
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($item) {
            $item->quantity += $request->quantity;
            $item->save();
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        // تحميل علاقات المنتج
        $item->load('product.category', 'product.vendor');

        return response()->json([
            'status' => 201,
            'data' => $item,
            'message' => 'Item quantity updated or added successfully'
        ]);
    }



    // تحديث كمية عنصر في العربة
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $roleName = optional($user->role)->name;

        $item = CartItem::findOrFail($id);
        $cart = Cart::findOrFail($item->cart_id);

        // التحقق من الصلاحيات: Customer فقط لو الكارت تبعه
        if ($roleName === 'Customer' && $cart->user_id !== $user->id) {
            return response()->json([
                'status' => 403,
                'message' => 'You are not allowed to update this item.'
            ], 403);
        }

        $item->update([
            'quantity' => $request->quantity
        ]);

        return response()->json([
            'status' => 200,
            'data' => $item,
            'message' => 'Quantity updated successfully'
        ]);
    }


    // حذف عنصر من العربة
    public function destroy($id)
    {
        $user = Auth::user();
        $roleName = optional($user->role)->name;

        $item = CartItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart item not found.'
            ], 404);
        }

        $cart = Cart::find($item->cart_id);

        if (!$cart) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart not found.'
            ]);
        }

        // السماح للـ Customer فقط لو cart تبعه، والـ Admin على أي حاجة
        if ($roleName === 'Customer' && $cart->user_id !== $user->id) {
            return response()->json([
                'status' => 403,
                'message' => 'You are not allowed to delete this item.'
            ]);
        }

        $item->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Item removed from cart successfully'
        ]);
    }
}
