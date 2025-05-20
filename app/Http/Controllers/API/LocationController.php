<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        $sports = $request->input('sports') ? explode('.', $request->input('sports')) : [];

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
                'id' => $location->locationId,
                'img' => $location->locationPath, // Using locationPath as image path
                'name' => $location->locationName,
                'sports' => $sports,
                'countLap' => $location->fields->count(), // Using field count as countLap
                'desc' => $location->description,
                // 'address' => $location->address ?? '', // Add address if it exists in your schema
                'created_at' => $location->created_at,
                'updated_at' => $location->updated_at
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
            'description' => 'required|string',
            'locationPath' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $location = Location::create($validatedData);

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
    public function show($id)
    {
        $location = Location::with(['fields', 'fields.sport'])
            ->find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => "Lokasi dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        // Format the response
        $sports = $location->fields->pluck('sport.sportName')->unique()->values()->all();

        $formattedLocation = [
            'id' => $location->locationId,
            'img' => $location->locationPath,
            'name' => $location->locationName,
            'sports' => $sports,
            'countLap' => $location->fields->count(),
            'desc' => $location->description,
            // 'address' => $location->address ?? '',
            'created_at' => $location->created_at,
            'updated_at' => $location->updated_at,
            'fields' => $location->fields->map(function ($field) {
                return [
                    'id' => $field->fieldId,
                    'name' => $field->name,
                    'sport' => $field->sport->sportName,
                    'description' => $field->description,
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => "Lokasi dengan ID {$id} ditemukan",
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

        $validator = Validator::make($request->all(), [
            'locationName' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'locationPath' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $location->update($validatedData);

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
    public function destroy($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => "Lokasi dengan ID {$id} tidak ditemukan"
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
        $sports = Sport::select('sportId', 'sportName')
            ->whereHas('fields')  // Only sports that are used in fields
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'List of all available sports',
            'sports' => $sports
        ]);
    }
}
