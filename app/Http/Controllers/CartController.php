<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // عرض عربة المستخدم (customer يشوف عربته فقط، admin يشوف كل العربات)
public function index()
{
    $user = Auth::user();
    $roleName = optional($user->role)->name;

    if ($roleName === 'Customer') {
    $cart = Cart::where('user_id', $user->id)
    ->select('id', 'user_id') // أعمدة cart
    ->with([
        'items:id,cart_id,product_id,quantity', // أعمدة CartItem
        'items.product:id,name,price,category_id,vendor_id', // أعمدة Product
        'items.product.category:id,name', // أعمدة Category
        'items.product.vendor:id,shop_name' // أعمدة Vendor
    ])
    ->first();


        if (!$cart) {
            return response()->json([
                'status' => 404,
                'message' => 'You do not have a cart yet.'
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => $cart,
            'message'=>'the cart with products and vendor'
        ]);
    }

    if ($roleName === 'Admin') {
$carts = Cart::select('id', 'user_id') // الأعمدة من carts
    ->with([
        'user:id,name,email', // الأعمدة من users
        'items:id,cart_id,product_id,quantity', // الأعمدة من cart_items
        'items.product:id,name,price,category_id,vendor_id', // الأعمدة من products
        'items.product.category:id,name', // الأعمدة من categories
        'items.product.vendor:id,shop_name' // الأعمدة من vendors
    ])
    ->get();

        return response()->json([
            'status' => 200,
            'data' => $carts,
            'message'=>'all carts data'
        ]);
    }

    return response()->json([
        'status' => 403,
        'message' => 'Access denied.'
    ]);
}


    public function store()
    {
        $user = Auth::user();

        if (!in_array($user->role->name, ['Customer', 'Admin'])) {
            return response()->json([
                'status' => 403,
                'message' => 'Only customers or admins can create carts.'
            ]);
        }

        if ($user->role->name === 'Customer') {
            $existingCart = Cart::where('user_id', $user->id)->first();

            if ($existingCart) {
                return response()->json([
                    'status' => 409,
                    'data'=>$existingCart,
                    'message' => 'You already have a cart.'
                ]);
            }

            $cart = Cart::create(['user_id' => $user->id]);

            return response()->json([
                'status' => 201,
                'data' => $cart,
                'message' => 'Cart created successfully.'
            ]);
        }

        // لو admin ينشئ عربة بدون user_id محدد (اختياري) — ممكن تسمحله يدخل user_id في الطلب أو تتركها فارغة
        $cart = Cart::create([
            'user_id' => request('user_id') // لو جاي من الطلب أو تقدر تتركها null
        ]);

        return response()->json([
            'status' => 201,
            'data' => $cart,
            'message' => 'Cart created successfully by admin.'
        ]);
    }

    // حذف العربة (customer يحذف بس عربته، admin يحذف أي عربة)
    public function destroy($id)
    {
        $user = Auth::user();
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart not found.'
            ], 404);
        }

        if ($user->role->name === 'Customer' && $cart->user_id !== $user->id) {
            return response()->json([
                'status' => 403,
                'message' => 'You can only delete your own cart.'
            ], 403);
        }

        if (!in_array($user->role->name, ['Customer', 'Admin'])) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied.'
            ], 403);
        }

        $cart->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Cart deleted successfully.'
        ]);
    }
}
