<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\ReservationDetail;
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
        $field = Field::with(['location', 'sport', 'times'])->find($id);

        if (!$field) {
            return response()->json([
                'success' => false,
                'message' => "Lapangan dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $times = $field->times->where('fieldId', $field->fieldId);
        $formattedField = [
            'id' => $field->fieldId,
            'name' => $field->name,
            'location' => $field->location->locationId,
            'sport' => $field->sport->sportId,
            'description' => $field->description,
            'startHour' => $field->times->min('time'),
            'endHour' => $field->times->max('time'),
            'times' => $times->map(function ($time) {
                return [
                    'time' => $time->time,
                    'price' => $time->price,
                    'status' => $time->status,
                ];
            })->values(),
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
        $validated = $request->validate([
            'locationId' => 'required|integer',
            'sportId' => 'required|integer',
            'name' => 'required|string',
            'startHour' => 'required|date_format:H:i',
            'endHour' => 'required|date_format:H:i|after:startHour',
            'description' => 'required|string',
            'start' => 'required|array',
            'start.*' => 'required|date_format:H:i',
            'end' => 'required|array',
            'end.*' => 'required|date_format:H:i',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
        ]);

        // Find the field to update
        $field = Field::findOrFail($id);

        // Update the field data
        $field->update([
            'locationId' => $validated['locationId'],
            'sportId' => $validated['sportId'],
            'name' => $validated['name'],
            'startHour' => $validated['startHour'],
            'endHour' => $validated['endHour'],
            'description' => $validated['description'],
        ]);

        // Mark old time slots as unavailable
        Time::where('fieldId', $field->fieldId)->update(['status' => 'non-available']);

        // Recreate new time slots
        foreach ($validated['start'] as $index => $startTime) {
            $start = Carbon::createFromFormat('H:i', $startTime);
            $end = Carbon::createFromFormat('H:i', $validated['end'][$index]);
            $slotPrice = $validated['price'][$index];

            while ($start < $end) {
                $timeString = $start->format('H:i');
                
                $existingSlot = Time::where('fieldId', $field->fieldId)
                                    ->where('time', $timeString)
                                    ->first();
                
                if (!$existingSlot) {
                    Time::create([
                        'fieldId' => $field->fieldId,
                        'time'    => $timeString,
                        'status'  => 'available',
                        'price'   => $slotPrice,
                    ]);
                } else {
                    // Optional: update existing slot if needed
                    $existingSlot->update([
                        'status' => 'available',
                        'price' => $slotPrice,
                    ]);
                }

                $start->addHour();
            }
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
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
        $validated = $request->validate([
            'locationId' => 'required|integer',
            'sportId' => 'required|integer',
            'name' => 'required|string',
            'startHour' => 'required|date_format:H:i',
            'endHour' => 'required|date_format:H:i|after:startHour',
            'description' => 'required|string',
            'start' => 'required|array',
            'start.*' => 'required|date_format:H:i',
            'end' => 'required|array',
            'end.*' => 'required|date_format:H:i',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
        ]);

        $field = Field::create([
            'locationId' => $validated['locationId'],
            'sportId' => $validated['sportId'],
            'name' => $validated['name'],
            'startHour' => $validated['startHour'],
            'endHour' => $validated['endHour'],
            'description' => $validated['description'],
        ]);

        foreach ($validated['start'] as $index => $startTime) {
            $start = Carbon::createFromFormat('H:i', $startTime);
            $end = Carbon::createFromFormat('H:i', $validated['end'][$index]);
            $slotPrice = $validated['price'][$index];

            while ($start < $end) {
                Time::create([
                    'fieldId' => $field->fieldId,
                    'time'    => $start->format('H:i'),
                    'status'  => 'available',
                    'price'   => $slotPrice,
                ]);

                $start->addHour();
            }
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

    public function getAllFields(Request $request) {
        $locationId = $request->input('locationId');
        $query = Field::select('fieldId as id', 'name');
        
        if ($locationId) {
            $query->where('locationId', $locationId);
        }
        $fields = $query->get();
        
        return response()->json($fields);
    }
    
    public function getAvailableTimes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fieldId' => 'required|exists:fields,fieldId',
                'date' => 'required|date',
                'excludeClosedId' => 'sometimes|integer|exists:reservations,reservationId'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fieldId = $request->fieldId;
            $date = $request->date;
            $excludeClosedId = $request->excludeClosedId;

            // Ambil semua waktu untuk field ini
            $allTimes = Time::select('timeId', 'time', 'status')
                ->where('fieldId', $fieldId)
                ->orderBy('time')
                ->get();

            // Ambil waktu yang sudah di-booking (exclude yang sedang di-edit)
            $bookedQuery = ReservationDetail::where('fieldId', $fieldId)
                ->whereDate('date', $date);

            if ($excludeClosedId) {
                $bookedQuery->whereHas('reservation', function($q) use ($excludeClosedId) {
                    $q->where('reservationId', '!=', $excludeClosedId);
                });
            }

            $bookedTimeIds = $bookedQuery->pluck('timeId')->toArray();

            // Format response
            $times = $allTimes->map(function($time) use ($bookedTimeIds) {
                $status = 'available';
                
                if ($time->status !== 'available') {
                    $status = 'non-available';
                } elseif (in_array($time->timeId, $bookedTimeIds)) {
                    $status = 'booked';
                }

                return [
                    'timeId' => (string)$time->timeId,
                    'time' => $time->time,
                    'status' => $status
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Waktu tersedia berhasil diambil',
                'times' => $times->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil waktu tersedia'
            ], 500);
        }
    }

    public function getPrice($id)
    {
        $price = Time::select('time', 'price')
            ->where('fieldId', $id)
            ->where('status', 'available')
            ->orderBy('time')
            ->get();
            
        return response()->json($price);
    }

}
