<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\Empty_;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all()->select('id', 'name');
        return response()->json([
            "status" => 200,
            "data" => $roles,
            "message" => "all roles data"
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "name" => "required|string|unique:roles,name"
        ]);

        $role = Role::create($data);

        return response()->json([
            "status" => 201,
            "data" => $role,
            "message" => "Role created successfully"
        ]);
    }

    public function show($id)
    {
        $role = Role::select('id', 'name')->where('id', $id)->first();
        if (!$role) {
            return response()->json([
                "status" => 404,
                "message" => "Role not found"
            ]);
        }

        return response()->json([
            "status" => 200,
            "data" => $role,
            "message" => "here u are one role"
        ]);
    }

    // تحديث رول
    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                "status" => 404,
                "message" => "Role not found"
            ]);
        }

        $data = $request->validate([
            "name" => "required|string|unique:roles,name," . $id
        ]);
        if (!empty($data)) {
            $role->update($data);
            return response()->json([
                "status" => 200,
                "message" => "No changes detected"
            ]);
        }
        return response()->json([
            "status" => 200,
            "data" => $role,
            "message" => "Role updated successfully"
        ]);
    }

    // حذف رول
    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                "status" => 404,
                "message" => "Role not found"
            ]);
        }

        $role->delete();

        return response()->json([
            "status" => 200,
            "message" => "Role deleted successfully"
        ]);
    }
}
