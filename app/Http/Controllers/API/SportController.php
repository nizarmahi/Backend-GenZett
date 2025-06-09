<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SportController extends Controller
{
    /**
     * Tampilkan daftar olahraga
     *
     * Mengambil daftar olahraga dengan opsi pencarian.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $search = $request->input('search');

        $query = Sport::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sportName', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totalSports = $query->count();
        $offset = ($page - 1) * $limit;
        $sports = $query->skip($offset)->take($limit)->get();

        foreach ($sports as $sport) {
            $sport->countLocation = DB::table('fields')
                ->join('locations', 'fields.locationId', '=', 'locations.locationId')
                ->where('fields.sportId', $sport->sportId)
                ->where('fields.deleted_at', null)
                ->distinct('locations.locationId')
                ->count('locations.locationId');
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Data olahraga berhasil diambil',
            'totalSports' => $totalSports,
            'offset' => $offset,
            'limit' => $limit,
            'sports' => $sports
        ]);
    }

    /**
     * Tambah olahraga baru
     *
     * Menyimpan olahraga baru ke dalam database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sportName' => 'required|string|max:255|unique:sports,sportName',
            'description' => 'required|string'
        ]);

        $sport = Sport::create($validated);

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Sport berhasil ditambahkan',
            'sport' => $sport
        ], 201);
    }

    /**
     * Tampilkan detail olahraga
     *
     * Mengambil detail olahraga berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $sport = Sport::find($id);

        if (!$sport) {
            return response()->json([
                'success' => false,
                'time' => now()->toISOString(),
                'message' => "Sport dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $sport->countLocation = DB::table('fields')
            ->join('locations', 'fields.locationId', '=', 'locations.locationId')
            ->where('fields.sportId', $sport->sportId)
            ->distinct('locations.locationId')
            ->count('locations.locationId');

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'sport' => $sport
        ]);
    }

    /**
     * Perbarui olahraga
     *
     * Memperbarui data olahraga berdasarkan ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $sport = Sport::find($id);

        if (!$sport) {
            return response()->json([
                'success' => false,
                'time' => now()->toISOString(),
                'message' => "Sport dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $validated = $request->validate([
            'sportName' => 'string|max:255|unique:sports,sportName,' . $id . ',sportId',
            'description' => 'string'
        ]);

        $sport->update($validated);

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Sport berhasil diperbarui',
            'sport' => $sport
        ]);
    }

    /**
     * Hapus olahraga
     *
     * Menghapus olahraga berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $sport = Sport::find($id);

        if (!$sport) {
            return response()->json([
                'success' => false,
                'time' => now()->toISOString(),
                'message' => "Sport dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        // Cek apakah masih digunakan pada field
        $fieldsCount = DB::table('fields')->where('sportId', $id)->where('deleted_at', null)->count();

        if ($fieldsCount > 0) {
            return response()->json([
                'success' => false,
                'time' => now()->toISOString(),
                'message' => "Sport tidak dapat dihapus karena sedang digunakan oleh {$fieldsCount} lapangan"
            ], 400);
        }

        $sport->delete();

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Sport berhasil dihapus'
        ]);
    }
    public function getAllSports()
    {
        // Get unique sports from all fields
        $sports = Sport::select('sportId as id', 'sportName as name')
            ->whereHas('fields')  // Only sports that are used in fields
            ->get();

        return response()->json($sports);
    }

    public function FieldsCount(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $search = $request->input('search');

        $query = Sport::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sportName', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totalSports = $query->count();
        $offset = ($page - 1) * $limit;

        // Ambil data olahraga dengan pagination
        $sports = $query->skip($offset)->take($limit)->get();

        // Hitung jumlah lapangan (fields) per olahraga
        foreach ($sports as $sport) {
            $sport->totalFields = DB::table('fields')
                ->where('sportId', $sport->sportId)
                ->whereNull('deleted_at')
                ->count();
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Data olahraga dengan jumlah lapangan berhasil diambil',
            'totalSports' => $totalSports,
            'offset' => $offset,
            'limit' => $limit,
            'sports' => $sports
        ]);
    }
}
