<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Tampilkan daftar admin
     *
     * Mengambil daftar admin dengan opsi pencarian dan filter berdasarkan lokasi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function index(Request $request){
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);
        $search = $request->input('search', '');
        $locationIds = $request->input('locationIds', []);

        // Pastikan locationIds array
        if (!is_array($locationIds)) {
            $locationIds = explode(',', $locationIds); // jika dikirim string "1,2,3"
        }

        // Start query with eager loading of relationships
        $query = Admin::with(['user', 'location']);

         // Filter lokasi jika ada input locationIds
        if (!empty($locationIds)) {
            $query->whereIn('locationId', $locationIds);
        }

        // Filter pencarian nama user atau lokasi
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('location', function($q3) use ($search) {
                    $q3->where('locationName', 'like', "%{$search}%");
                });
            });
        }

        // Get total count
        $totalAdmins = $query->count();

        // Calculate offset
        $offset = ($page - 1) * $limit;

        // Get paginated results
        $admins = $query->skip($offset)->take($limit)->get();

        // Format the response
        $formattedAdmins = $admins->map(function ($admin) {
            return [
                'id' => $admin->adminId,
                'name' => $admin->user->name,
                'email' => $admin->user->email,
                'location' => $admin->location->locationName,
                'phone' => $admin->user->phone,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Data admin berhasil diambil',
            'totalAdmins' => $totalAdmins,
            'offset' => $offset,
            'limit' => $limit,
            'admins' => $formattedAdmins
        ]);
    }
    /**
     * Detail Admin
     *
     * Mengambil detail admin berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $admin = Admin::with(['user', 'location'])
            ->find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => "Admin dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $formattedAdmin = [
            'id' => $admin->adminId,
            'name' => $admin->user->name,
            'email' => $admin->user->email,
            'location' => $admin->location->locationName,
            'phone' => $admin->user->phone,
            'created_at' => $admin->created_at,
            'updated_at' => $admin->updated_at,
        ];

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => "Admin dengan ID {$id} ditemukan",
            'admin' => $formattedAdmin
        ]);
    }

    /**
     * Update Admin
     *
     * Mengupdate data admin berdasarkan ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->userId . ',userId',
            'phone' => 'required|string|max:255',
            'locationId' => 'required|integer',
        ]);

        $admin = Admin::findOrFail($id);
        
        // Update the Admin model (only locationId is in this table)
        $admin->update([
            'locationId' => $validated['locationId']
        ]);
        
        // Update the associated User model
        $user = User::findOrFail($admin->userId);
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
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

    /**
     * Hapus Admin
     *
     * Menghapus admin berdasarkan ID
     *
     */
    public function destroy($id)
    {
        $admin = Admin::with('user')->find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => "Admin dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        // Get the user ID before deleting the admin
        $user = $admin->user;
        
        // Delete the admin record first (due to foreign key constraints)
        $admin->delete();
        
        // Delete the associated user record
        if ($user) {
            $user->delete();
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Admin berhasil dihapus'
        ]);
    }

    /**
     * Tambah Admin
     *
     * Menyimpan data admin baru.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'locationId' => 'required|integer',
        ]);

        $user = User::create([
            'password' => bcrypt($validated['password']),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => 'admin',
        ]);
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User gagal dibuat'], 500);
        }

        $admin = Admin::create([
            'userId'     => $user->userId,
            'locationId' => $validated['locationId'],
        ]);

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Admin gagal dibuat'], 500);
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Admin berhasil ditambahkan',
            'admin' => $admin,
            'user' => $user
        ], 201);
    }
}
