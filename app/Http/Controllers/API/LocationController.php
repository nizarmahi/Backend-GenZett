<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class LocationController extends Controller
{
    /**
     * Tampilkan daftar lokasi
     *
     * Mengambil daftar lokasi dengan opsi pencarian dan filter berdasarkan olahraga.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);
        $search = $request->input('search');
        $sports = $request->input('sports');

        // Start query with eager loading of relationships
        $query = Location::with(['fields', 'fields.sport']);

        // Apply filters
        if (!empty($sports)) {
            $query->whereHas('fields.sport', function ($q) use ($sports) {
                $q->whereIn('sportName', $sports); // atau key lain sesuai field kamu
            });
        }

        if ($search) {
            $query->search($search);
        }

        // Get total count
        $totalLocations = $query->count();

        // Calculate offset
        $offset = ($page - 1) * $limit;

        // Get paginated results
        $locations = $query->skip($offset)->take($limit)->get();

        // Format the response
        $formattedLocations = $locations->map(function ($location) {
            // Group sports by each location
            $sports = $location->fields->pluck('sport.sportName')->unique()->values()->all();

            return [
                'locationId' => $location->locationId,
                'img' => $location->locationPath, // Using locationPath as image path
                'locationName' => $location->locationName,
                'sports' => $sports,
                'countLap' => $location->fields->count(), // Using field count as countLap
                'description' => $location->description,
                'address' => $location->address ?? '', // Add address if it exists in your schema
            ];
        });
        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Data lokasi untuk keperluan testing',
            'totalLocations' => $totalLocations,
            'offset' => $offset,
            'limit' => $limit,
            'locations' => $formattedLocations
        ]);
    }

    /**
     * Tambah lokasi baru
     *
     * Menyimpan lokasi baru ke dalam database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'locationName' => 'required|string|max:255',
            'locationPath' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simpan gambar
        $path = $request->file('locationPath')->store('locations', 'public');

        $location = Location::create([
            'locationName' => $request->locationName,
            'description' => $request->description,
            'address' => $request->address,
            'locationPath' => $path, // simpan path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'location' => $location
        ], 201);
    }

    /**
     * Detail Lokasi
     *
     * Mengambil detail lokasi berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($locationId)
    {
        $location = Location::with(['fields', 'fields.sport'])
            ->find($locationId);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => "Lokasi dengan Id {$locationId} tidak ditemukan"
            ], 404);
        }

        // Format the response
        // $sports = $location->fields->pluck('sport.sportName')->unique()->values()->all();

        $formattedLocation = [
            'locationId' => $location->locationId,
            'img' => $location->locationPath,
            'locationName' => $location->locationName,
            // 'sports' => $sports,
            // 'countLap' => $location->fields->count(),
            'address' => $location->address ?? '',
            'description' => $location->description,
            // 'created_at' => $location->created_at,
            // 'updated_at' => $location->updated_at,

            // 'fields' => $location->fields->map(function ($field) {
            //     return [
            //         'id' => $field->fieldId,
            //         'name' => $field->name,
            //         'sports' => $field->sport->sportName,
            //         'description' => $field->description,
            //     ];
            // })
        ];

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => "Lokasi dengan ID {$locationId} ditemukan",
            'location' => $formattedLocation
        ]);
    }

    /**
     * Update Lokasi
     *
     * Memperbarui data lokasi berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
{
    $location = Location::find($id);

    if (!$location) {
        return response()->json([
            'success' => false,
            'message' => "Lokasi dengan ID {$id} tidak ditemukan"
        ], 404);
    }

    $validated = $request->validate([
        'locationName' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'address' => 'nullable|string',
        'locationPath' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    if ($request->hasFile('locationPath')) {
        // Hapus gambar lama
        if ($location->locationPath && Storage::disk('public')->exists($location->locationPath)) {
            Storage::disk('public')->delete($location->locationPath);
        }

        // Simpan gambar baru
        $path = $request->file('locationPath')->store('locations', 'public');
        $validated['locationPath'] = $path;
    }

    Log::info('Validated data:', $validated);


    $location->fill($validated);
    $location->save();

    return response()->json([
        'success' => true,
        'message' => 'Location berhasil diperbarui',
        'location' => $location
    ]);
}

    /**
     * Hapus Lokasi
     *
     * Menghapus lokasi berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($locationId)
    {
        $location = Location::find($locationId);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => "Lokasi dengan Id {$locationId} tidak ditemukan"
            ], 404);
        }

        // Check if there are related fields before deletion
        if ($location->fields()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus lokasi dengan lapangan yang ada'
            ], 409);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil dihapus'
        ]);
    }

    /**
     * Mengambil semua Olahraga yang tersedia
     *
     * Mendapatkan daftar semua olahraga yang tersedia di lapangan.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSports()
    {
        // Get unique sports from all fields
        $sports = Sport::select('sportId as id', 'sportName as name')
            // ->whereHas('fields')  // Only sports that are used in fields
            ->get();

        return response()->json($sports);
    }
    public function getAllLocations() {
        $locations = Location::select('locationId as id', 'locationName as name')->get();
        return response()->json($locations);
    }
}
