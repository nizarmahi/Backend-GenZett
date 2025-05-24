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
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $sports = $request->input('sports') ? explode('.', $request->input('sports')) : [];
        $locations = $request->input('locations') ? explode('.', $request->input('locations')) : [];

        $query = Field::with(['location', 'sport', 'times']);

        // Apply filters
        if (!empty($sports)) {
            $query->whereIn('sportId', $sports);
        }

        if (!empty($locations)) {
            $query->whereIn('locationId', $locations);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhereHas('location', function ($q) use ($search) {
                    $q->where('locationName', 'like', '%' . $search . '%');
                })
                ->orWhereHas('sport', function ($q) use ($search) {
                    $q->where('sportName', 'like', '%' . $search . '%');
                });
            });
        }

        $totalFields = $query->count();
        $offset = ($page - 1) * $limit;
        $fields = $query->skip($offset)->take($limit)->get();

        $formattedFields = $fields->map(function ($field) {
           $availableTimes = $field->times->where('status', 'available');
            return [
                'id' => $field->fieldId,
                'name' => $field->name,
                'location' => $field->location->locationName ?? null,
                'sport' => $field->sport->sportName ?? null,
                'startHour' => $availableTimes->min('time'),
                'endHour' => $availableTimes->max('time'),
                'description' => $field->description,
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
            'location' => $field->location->locationId,
            'sport' => $field->sport->sportId,
            'description' => $field->description,
            'startHour' => $field->times->min('time'),
            'endHour' => $field->times->max('time'),
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
        // /** @var \App\Models\User $user */
        // $user = auth()->user();
        // $admin = $user->admin;

        $field = Field::findOrFail($id);

        // Cek izin admin cabang
        // if (!$admin || $field->locationId !== $admin->location_id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Anda tidak memiliki izin untuk mengubah lapangan ini.'
        //     ], 403);
        // }

        // Validasi input
        $validated = $request->validate([
            'locationId' => 'required|integer',
            'sportId' => 'required|integer',
            'name' => 'required|string|max:255',
            'startHour' => 'required|date_format:H:i',
            'endHour' => 'required|date_format:H:i|after:startHour',
            'description' => 'required|string',
        ]);

        // Update data field (termasuk jam operasi)
        $field->update([
            'locationId' => $validated['locationId'],
            'sportId' => $validated['sportId'],
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        // Ambil jam awal dan akhir
        $startHour = Carbon::createFromFormat('H:i', $validated['startHour'])->hour;
        $endHour = Carbon::createFromFormat('H:i', $validated['endHour'])->hour;

        // Ambil semua time slot yang sudah ada
        $existingTimes = Time::where('fieldId', $field->fieldId)->get();
        $existingHours = $existingTimes->pluck('time')->map(function ($time) {
            return Carbon::createFromFormat('H:i:s', $time)->hour;
        })->toArray();

        // Tambah jam baru jika perlu
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

        // Ubah status time slot menjadi 'Available' atau 'Non-available' sesuai jam operasi
        foreach ($existingTimes as $time) {
            $hour = Carbon::createFromFormat('H:i:s', $time->time)->hour;

            if ($hour >= $startHour && $hour < $endHour) {
                // Dalam jam operasi
                $time->status = 'available';
            } else {
                // Di luar jam operasi
                $time->status = 'non-available';
            }

            $time->save();
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
    public function delete($id)
    {
        $field = Field::find($id);

        // $admin = auth()->user()->admin;

        // if (!$admin || $field->locationId !== $admin->location_id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Anda tidak memiliki izin untuk menghapus lapangan ini.'
        //     ], 403);
        // }

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

        foreach ($field->times as $time) {
            $time->status = 'non-available';
            $time->save();
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
    //     $user = auth()->user();
    //     $admin = $user->admin;

        $validated = $request->validate([
            'locationId' => 'required|integer',
            'sportId' => 'required|integer',
            'name' => 'required|string',
            'startHour' => 'required|date_format:H:i',
            'endHour' => 'required|date_format:H:i|after:startHour',
            'description' => 'required|string',
        ]);

        // Jika user adalah admin cabang, pakai lokasi miliknya
        // if ($admin) {
        //     $validated['locationId'] = $admin->location_id;
        // }
        $field = Field::create($validated);

        $start = Carbon::createFromFormat('H:i', $validated['startHour']);
        $end = Carbon::createFromFormat('H:i', $validated['endHour']);

        while ($start < $end) {
            Time::create([
                'fieldId' => $field->fieldId,
                'time'    => $start->format('H:i'),
                'status'  => 'available',
                'price'   => 100000,
            ]);

            $start->addHour();
        }


        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
        ]);
    }

    // Untuk olahraga
    public function getAllSports() {
        $sports = Sport::select('sportId as id', 'sportName as name')->get();
        return response()->json($sports);
    }
}
