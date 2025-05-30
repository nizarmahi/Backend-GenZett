<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:255|unique:users,phone',
            'password' => 'required|string|min:8|confirmed', // Pastikan password dikonfirmasi
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.unique' => 'Nomor HP sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        event(new \Illuminate\Auth\Events\Registered($user));

        $user->sendEmailVerificationNotification();


        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $user = Auth::user();

        $userInfo = [
            'id' => $user->userId,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'phone' => $user->phone,
        ];

        // Jika role-nya admin, tambahkan location_id
        if ($user->role === 'admin') {
            $admin = $user->admin;
            if ($admin) {
                $userInfo['locationId'] = $admin->locationId;
            }
        }

        $customClaims = [
            'role' => $user->role,
            'user_id' => $user->userId,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'created_at' => $user->created_at,
        ];
        if ($user->role === 'admin' && $user->admin) {
            $customClaims['locationId'] = $user->admin->locationId;
        }

        $tokenWithClaims = JWTAuth::claims($customClaims)->attempt($credentials);

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $userInfo,
            'token' => $tokenWithClaims,
        ]);
    }

    public function logout(Request $request)
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => 'success',
            'message' => 'You have been logged out successfully.'
        ]);
    }
    public function editAdminProfile(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        $admin = Admin::findOrFail($id);
        
        $user = User::findOrFail($admin->userId);
        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Admin berhasil diperbarui',
            'admin' => $admin,
            'user' => $user
        ]);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'oldPassword' => 'required|string|min:8',
            'newPassword' => 'required|string|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'oldPassword' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['message' => 'Password berhasil diubah'], 200);
    }

}
