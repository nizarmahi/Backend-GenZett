<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Time;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FieldController extends Controller
{
    /**
     * Tampilkan daftar lapangan
     *
     * Mengambil daftar lapangan dengan opsi pencarian dan filter berdasarkan lokasi dan olahraga.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request){
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);
        $search = $request->input('search');
        $sports = $request->input('sports') ? explode('.', $request->input('sports')) : [];
        $locations = $request->input('locations') ? explode('.', $request->input('locations')) : [];

        $admin = auth()->user()->admin;

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda bukan Admin Cabang ini.'
            ], 403);
        }

        // Start query with eager loading of relationships
        $query = Field::with(['location', 'sport', 'times'])
            ->where('locationId', $admin->location_id);

        // Apply filters
        if (!empty($sports)) {
            $query->hasSport($sports);
        }

        if (!empty($locations)) {
            $query->hasLocation($locations);
        }

        if ($search) {
            $query->search($search);
        }

        // Get total count
        $totalFields = $query->count();

        // Calculate offset
        $offset = ($page - 1) * $limit;

        // Get paginated results
        $fields = $query->skip($offset)->take($limit)->get();

        // Format the response
        $formattedFields = $fields->map(function ($field) {
            // Group sports by each field
            $sports = $field->sport->sportName;

            return [
                'id' => $field->fieldId,
                'name' => $field->name,
                'location' => $field->location->locationName,
                'sport' => $sports,
                'startHour' => $field->times->min('time'),
                'endHour' => $field->times->max('time'),
                'desc' => $field->description,
                'created_at' => $field->created_at,
                'updated_at' => $field->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Data lapangan berhasil diambil',
            'totalFields' => $totalFields,
            'offset' => $offset,
            'limit' => $limit,
            'fields' => $formattedFields
        ]);
    }
  
    /**
     * Detail Lapangan
     *
     * Mengambil detail lapangan berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $field = Field::with(['location', 'sport', 'times'])
            ->find($id);

        if (!$field) {
            return response()->json([
                'success' => false,
                'message' => "Lapangan dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $formattedField = [
            'id' => $field->fieldId,
            'name' => $field->name,
            'location' => $field->location->locationName,
            'sport' => $field->sport->sportName,
            'description' => $field->description,
            'startHour' => $field->times->min('time'),
            'endHour' => $field->times->max('time'),
            'created_at' => $field->created_at,
            'updated_at' => $field->updated_at,
        ];

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => "Lapangan dengan ID {$id} ditemukan",
            'field' => $formattedField
        ]);
    }

    /**
     * Update Lapangan
     *
     * Mengupdate data lapangan berdasarkan ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $field = Field::findOrFail($id);

        $admin = auth()->user()->admin;

        if (!$admin || $field->locationId !== $admin->location_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengubah lapangan ini.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'locationId' => 'required|integer',
            'sportId' => 'required|integer',
            'name' => 'required|string|max:255',
            'startHour' => 'required|string',
            'endHour' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        $field = Field::findOrFail($id);
        $field->update($validatedData);

        // Konversi jam
        $startHourRaw = str_replace('.', ':', $validatedData['startHour']);
        $endHourRaw = str_replace('.', ':', $validatedData['endHour']);
        $startHour = Carbon::createFromFormat('H:i', $startHourRaw)->hour;
        $endHour = Carbon::createFromFormat('H:i', $endHourRaw)->hour;

        // Ambil jam yang sudah ada di tabel times
        $existingTimes = Time::where('fieldId', $field->fieldId)->get();
        $existingHours = $existingTimes->pluck('time')->map(function ($time) {
            return Carbon::createFromFormat('H:i:s', $time)->hour;
        })->toArray();

        // Tambahkan jam baru jika diperpanjang
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            if (!in_array($hour, $existingHours)) {
                Time::create([
                    'fieldId'   => $field->fieldId,
                    'time'      => Carbon::createFromTime($hour, 0, 0)->format('H:i:s'),
                    'status'    => 'Available',
                    'price'     => 100000,
                ]);
            }
        }

        foreach ($existingTimes as $time) {
            $hour = Carbon::createFromFormat('H:i:s', $time->time)->hour;
            if ($hour < $startHour || $hour >= $endHour) {
                $time->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Lapangan berhasil diperbarui',
            'field' => $field
        ]);
    }

    /**
     * Hapus Lapangan
     *
     * Menghapus lapangan berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $field = Field::find($id);

        $admin = auth()->user()->admin;

        if (!$admin || $field->locationId !== $admin->location_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus lapangan ini.'
            ], 403);
        }

        if (!$field) {
            return response()->json([
                'success' => false,
                'message' => "Lapangan dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        // Check if there are related reservationDetails before deletion
        if ($field->reservationDetails()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus lapangan dengan laporan yang ada'
            ], 409);
        }

        $field->delete();

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Lapangan berhasil dihapus'
        ]);
    }

    /**
     * Tambah Lapangan
     *
     * Menyimpan data lapangan baru.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $admin = auth()->user()->admin;

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda bukan Admin Cabang ini.'
            ], 403);
        }

        $validated = $request->validate([
            'locationId' => 'required|integer',
            'sportId' => 'required|integer',
            'name' => 'required|string|max:255',
            'startHour' => 'required|string', // format: 'HH:MM'
            'endHour' => 'required|string',   // format: 'HH:MM'
            'description' => 'required|string',
        ]);

        $validated['locationId'] = $admin->location_id;

        $field = Field::create($validated);

        $startHour = Carbon::createFromFormat('H:i', $validated['startHour'])->hour;
        $endHour = Carbon::createFromFormat('H:i', $validated['endHour'])->hour;

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            Time::create([
                // Jika `timeId` auto increment, hapus baris ini
                // 'timeId'  => optional,

                'fieldId'   => $field->fieldId,
                'time'      => Carbon::createFromTime($hour, 0, 0)->format('H:i:s'),
                'status'    => 'Available',
                'price'     => 100000,
            ]);
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Lapangan berhasil ditambahkan',
            'field' => $field
        ], 201);
    }

    /**
     * Mengambil semua olahraga yang tersedia
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
            'time' => now()->toISOString(),
            'message' => 'List of all available sports',
            'sports' => $sports
        ]);
    }

    /**
     * Mengambil semua lokasi yang tersedia
     *
     * Mendapatkan daftar semua lokasi yang tersedia di lapangan.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllLocations()
    {
        // Get unique locations from all fields
        $locations = Location::select('locationId', 'locationName')
            ->whereHas('fields')  // Only locations that are used in fields
            ->get();

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'List of all available locations',
            'locations' => $locations
        ]);
    }
}
