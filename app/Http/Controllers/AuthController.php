<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|email|unique:users,email",
            "password" => "required|string|min:6",
            "role_id" => "required|exists:roles,id",
        ]);

        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        $user->load('role:id,name');

        $token = JWTAuth::fromUser($user);

        return response()->json([
            "status" => 201,
            "data" => $user,
            "token" => $token,
            "message" => "User registered successfully"
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            "email" => "required|email",
            "password" => "required|string"
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                "status" => 401,
                "message" => "Invalid email or password"
            ]);
        }

        $user = auth()->user()->load('role:id,name');
        $data = $user->only(['id', 'name', 'email']); // تحديد الأعمدة

        return response()->json([
            "status" => 200,
            "data" => $data,
            "token" => $token,
            "message" => "Login successful"
        ]);
    }

    public function profile()
    {
        $user = auth()->user()->load('role:id,name');


        return response()->json([
            "status" => 200,
            "data" => $user,
            "message" => "User profile data"
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . auth()->id(),
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                "status" => 400,
                "message" => "Incorrect password"
            ]);
        }

        $dataToUpdate = $request->only(['name', 'email']);

        if (!empty($dataToUpdate)) {
            $user->update($dataToUpdate);

            return response()->json([
                "status" => 200,
                "message" => "Profile updated successfully"
            ]);
        }

        return response()->json([
            "status" => 400,
            "message" => "No changes detected"
        ], 400);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status' => 400,
                'message' => 'Old password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => bcrypt($request->new_password)
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Password changed successfully. Please log in again.'
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $data = $request->validate([
            "password" => "required|string"
        ]);

        $user = auth()->user();

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json([
                "status" => 400,
                "message" => "Incorrect password"
            ], 400);
        }

        $user->delete();

        return response()->json([
            "status" => 200,
            "message" => "Account deleted successfully"
        ]);
    }

    public function logout()
    {

        auth('api')->logout();

        return response()->json([
            "status" => 200,
            "message" => "Logged out successfully"
        ]);
    }
}
