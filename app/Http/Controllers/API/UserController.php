<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    /**
     * Tampilkan daftar pengguna
     *
     * Mengambil daftar pengguna dengan opsi pencarian.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);
        $search = $request->input('search');

        // Start query with eager loading of relationships
        $query = User::where('role', 'user');

        if (!empty($search)) {
            $query->search($search);
        }
        $totalUsers = $query->count();

        // Calculate offset
        $offset = ($page - 1) * $limit;

        // Get paginated results
        $users = $query->skip($offset)->take($limit)->get();

        // Format the response
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->userId,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Data user berhasil diambil',
            'totalUsers' => $totalUsers,
            'offset' => $offset,
            'limit' => $limit,
            'users' => $formattedUsers
        ]);
    }

    /**
     * Detail Pengguna
     *
     * Menampilkan detail pengguna berdasarkan ID.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::where('role', 'user')
            ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $formattedUser = [
            'id' => $user->userId,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => "User dengan ID {$id} ditemukan",
            'user' => $formattedUser
        ]);
    }

    /**
     * Update Pengguna
     *
     * Memperbarui data pengguna berdasarkan ID.
     *
     * @param Request $request
     * @param int $id
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User dengan ID {$id} tidak ditemukan"
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id . ',userId',
            'phone' => 'sometimes|required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        $user->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui',
            'user' => $user
        ]);
    }

    /**
     * Ubah Password Pengguna
     *
     * Mengubah password pengguna berdasarkan ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request, $id)
    {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User dengan ID {$id} tidak ditemukan"
            ], 404);
        }
    
        // Validasi input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Periksa password lama
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak cocok'
            ], 403);
        }
    
        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui'
        ]);
    }

    /**
     * Hapus Pengguna
     *
     * Menghapus pengguna berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'User berhasil dihapus'
        ]);
    }

}
