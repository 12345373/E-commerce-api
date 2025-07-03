<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // عرض جميع الطلبات (للـ admin فقط)
    public function index()
    {
        $user = Auth::user();

        if ($user->role->name !== 'Admin') {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized'
            ], 403);
        }

        $orders = Order::with(['user', 'vendor', 'items.product'])->get();

        return response()->json([
            'status' => 200,
            'data' => $orders
        ]);
    }

    // عرض طلب معين
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::with(['items.product', 'user', 'vendor'])->find($id);

        if (!$order) {
            return response()->json([
                'status' => 404,
                'message' => 'Order not found'
            ], 404);
        }

        // السماح فقط للـ admin أو صاحب الطلب (customer) بالاطلاع على الطلب
        if ($user->role->name !== 'Admin' && $order->user_id !== $user->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => 200,
            'data' => $order
        ]);
    }

    // إنشاء طلب جديد (للـ customer فقط)
    public function store(Request $request)
    {
        $user = Auth::user();

            if ($user->role->name !== 'Customer') {
            return response()->json([
                'status' => 403,
                'message' => 'Only customers can create orders.'
            ], 403);
        }

        // الحصول على عربة المستخدم مع عناصرها
        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => 400,
                'message' => 'Cart is empty. Add items before placing an order.'
            ], 400);
        }

        // افترض أن جميع المنتجات من نفس vendor
        $vendorId = $cart->items->first()->product->vendor_id;

        // حساب المجموع الكلي
        $totalPrice = 0;
        foreach ($cart->items as $item) {
            $totalPrice += $item->quantity * $item->product->price;
        }

        // إنشاء الطلب
        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendorId,
            'status' => 'pending',
            'total' => $totalPrice,
        ]);

        // إنشاء عناصر الطلب (OrderItems)
        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ]);
        }

        // حذف عربة المستخدم بعد الطلب
        $cart->items()->delete();
        $cart->delete();

        return response()->json([
            'status' => 201,
            'data' => $order->load('items.product'),
            'message' => 'Order created successfully'
        ]);
    }

    // تحديث حالة الطلب (admin فقط)
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'Admin') {
            return response()->json([
                'status' => 403,
                'message' => 'Only admin can update order status.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,canceled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status' => 404,
                'message' => 'Order not found'
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 200,
            'data' => $order,
            'message' => 'Order status updated successfully'
        ]);
    }

    // حذف طلب (admin فقط)
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->role !== 'Admin') {
            return response()->json([
                'status' => 403,
                'message' => 'Only admin can delete orders.'
            ], 403);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status' => 404,
                'message' => 'Order not found'
            ], 404);
        }

        $order->items()->delete();
        $order->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Order deleted successfully'
        ]);
    }
}
