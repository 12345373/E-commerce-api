<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::with(['user:id,name,email,role_id', 'user.role:id,name'])->get();
        return response()->json([
            'status' => 200,
            'data' => $vendors,
            'message' => 'Vendors retrieved successfully'
        ]);
    }


    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role->name, ['Admin', 'Vendor'])) {
            return response()->json([
                'status' => 403,
                'message' => 'Only admins and vendors can create a shop.'
            ], 403);
        }

        $data = $request->validate([
            'shop_name' => 'required|string|max:255',
            'shop_slug' => 'required|string|unique:vendors,shop_slug',
            'description' => 'required|string',
        ]);

        $data['user_id'] = $user->id;

        $vendor = Vendor::create($data);

        return response()->json([
            'status' => 201,
            'data' => $vendor,
            'message' => 'Shop created successfully'
        ]);
    }

    /**
     * عرض Vendor حسب ID
     */
    public function show($id)
    {
         $vendor = Vendor::with(['user:id,name,email,role_id', 'user.role:id,name'])
        ->where('id', $id)
        ->first();

        if (!$vendor) {
            return response()->json([
                'status' => 404,
                'message' => 'Vendor not found'
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => $vendor,
            'message' => 'Vendor retrieved successfully'
        ]);
    }

    /**
     * تحديث بيانات Vendor
     */
public function update(Request $request, $id)
{
    $vendor = Vendor::find($id);

    if (!$vendor) {
        return response()->json([
            'status' => 404,
            'message' => 'Vendor not found'
        ], 404);
    }

    $user = auth()->user();

    // التحقق: لو مش Admin ولا صاحب المتجر نفسه، ممنوع التعديل
    if ($user->role->name !== 'Admin' && $vendor->user_id !== $user->id) {
        return response()->json([
            'status' => 403,
            'message' => 'You are not authorized to update this vendor'
        ], 403);
    }

    $data = $request->validate([
        'shop_name' => 'sometimes|string|max:255',
        'shop_slug' => 'sometimes|string|unique:vendors,shop_slug,' . $vendor->id,
    ]);

    $vendor->update($data);

    return response()->json([
        'status' => 200,
        'data' => $vendor,
        'message' => 'Vendor updated successfully'
    ]);
}



    /**
     * حذف Vendor
     */
  public function destroy($id)
{
    $vendor = Vendor::find($id);

    if (!$vendor) {
        return response()->json([
            'status' => 404,
            'message' => 'Vendor not found'
        ]);
    }

    $user = auth()->user();


    if ($user->role->name !== 'Admin' && $vendor->user_id !== $user->id) {
        return response()->json([
            'status' => 403,
            'message' => 'You are not authorized to delete this vendor'
        ], 403);
    }

    $vendor->delete();

    return response()->json([
        'status' => 200,
        'message' => 'Vendor deleted successfully'
    ]);
}

}
