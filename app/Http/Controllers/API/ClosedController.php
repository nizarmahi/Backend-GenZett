<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\ReservationDetail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ClosedController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $locationId = $request->input('locationId');
        $sportId = $request->input('sportId');
        $date = $request->input('date');

        $query = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time'
        ])
        ->where('paymentStatus', 'closed')
        ->when($locationId, function ($query) use ($locationId) {
            $query->whereHas('details.field.location', function ($q) use ($locationId) {
                $q->where('locationId', $locationId);
            });
        })
        ->when($sportId, function ($query) use ($sportId) {
            $query->whereHas('details.field.sport', function ($q) use ($sportId) {
                $q->where('sportId', $sportId);
            });
        })
        ->when($search, function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        })
        ->when($date, function ($query) use ($date) {
            $query->whereHas('details', function ($q) use ($date) {
                $q->where('date', $date);
            });
        })
        ->orderByDesc('created_at');

        $reservations = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
                'success' => true,
                'message' => 'Semua reservasi berhasil diambil',
                'data' => $reservations->map(function ($reservation) {
                    return [
                        'reservationId' => $reservation->reservationId,
                        'name' => $reservation->name,
                        'paymentStatus' => $reservation->paymentStatus,
                        'created_at' => $reservation->created_at,
                        'details' => $reservation->details->map(function ($detail) {
                            return [
                                'fieldName' => $detail->field->name,
                                'time' => $detail->time->time,
                                'date' => $detail->date,
                            ];
                        })
                    ];
                })
            ]);
    }

    public function show($id)
    {
        try {
            $reservation = Reservation::with(['details.field', 'details.time', 'user'])
                ->where('paymentStatus', 'closed')
                ->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Closed field tidak ditemukan'
                ], 404);
            }

            // Format data untuk edit
            $formattedData = [
                'id' => $reservation->reservationId,
                'name' => $reservation->name,
                'fieldId' => $reservation->details->first()->fieldId ?? null,
                'date' => $reservation->details->first()->date ?? null,
                'time' => $reservation->details->pluck('time.time')->toArray(),
                'userId' => $reservation->userId,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil',
                'data' => $formattedData,
                'reservation' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data closed field'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId',
            'name' => 'required|string|max:255',
            'fieldId' => 'required|exists:fields,fieldId',
            'date' => 'required|date',
            'time' => 'required|array|min:1',
            'time.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Konversi time string ke timeId dan filter yang available BERDASARKAN FIELD
        $availableTimeIds = [];
        $skippedTimes = [];
        
        foreach ($request->time as $timeString) {
            $timeRecord = DB::table('times')
                ->where('time', $timeString)
                ->where('fieldId', $request->fieldId)
                ->where('status', 'available')
                ->first();
                
            if ($timeRecord) {
                $availableTimeIds[] = $timeRecord->timeId;
            } else {
                $skippedTimes[] = $timeString;
            }
        }

        if (empty($availableTimeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada waktu yang tersedia untuk reservasi pada field ini.',
                'skipped_times' => $skippedTimes
            ], 422);
        }

        // Cek konflik reservasi untuk waktu yang available
        $conflicts = [];
        foreach ($availableTimeIds as $timeId) {
            $existingReservation = ReservationDetail::where('fieldId', $request->fieldId)
                ->where('timeId', $timeId)
                ->whereDate('date', $request->date)
                ->exists();

            if ($existingReservation) {
                $timeRecord = DB::table('times')
                    ->where('timeId', $timeId)
                    ->where('fieldId', $request->fieldId)
                    ->first();
                    
                $conflicts[] = [
                    'fieldId' => $request->fieldId,
                    'timeId' => $timeId,
                    'time' => $timeRecord->time ?? 'Unknown',
                    'date' => $request->date,
                    'message' => 'Lapangan dan jam sudah dipesan pada tanggal tersebut.'
                ];
            }
        }

        $conflictTimeIds = collect($conflicts)->pluck('timeId')->toArray();
        $validTimeIds = array_diff($availableTimeIds, $conflictTimeIds);

        if (empty($validTimeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Semua waktu yang tersedia sudah dipesan',
                'conflicts' => $conflicts,
                'skipped_times' => $skippedTimes
            ], 409);
        }

        // Buat reservasi utama
        $reservation = Reservation::create([
            'userId' => $request->userId,
            'name' => $request->name,
            'paymentStatus' => 'closed',
            'paymentType' => 'reguler',
            'total' => 0,
            'remaining' => 0,
        ]);

        // Simpan detail reservasi
        foreach ($validTimeIds as $timeId) {
            $reservation->details()->create([
                'fieldId' => $request->fieldId,
                'timeId' => $timeId,
                'date' => $request->date,
            ]);
        }

        $bookedTimes = DB::table('times')
            ->whereIn('timeId', $validTimeIds)
            ->where('fieldId', $request->fieldId)
            ->pluck('time')
            ->toArray();

        $response = [
            'success' => true,
            'message' => 'Reservasi berhasil dibuat',
            'reservation' => $reservation->load('details.field', 'details.time'),
            'booked_times' => $bookedTimes,
        ];

        if (!empty($skippedTimes)) {
            $response['skipped_times'] = $skippedTimes;
            $response['skipped_message'] = 'Beberapa waktu tidak tersedia atau tidak ada untuk field ini';
        }

        if (!empty($conflicts)) {
            $response['conflicts'] = $conflicts;
            $response['conflict_message'] = 'Beberapa waktu sudah dipesan dan diskip';
        }

        return response()->json($response, 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $reservation = Reservation::with('details')
                ->where('paymentStatus', 'closed')
                ->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Closed field tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'userId' => 'sometimes|exists:users,userId',
                'name' => 'required|string|max:255',
                'fieldId' => 'required|exists:fields,fieldId',
                'date' => 'required|date',
                'time' => 'required|array|min:1',
                'time.*' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang diberikan tidak valid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Hapus detail reservasi lama
            $reservation->details()->delete();

            // Konversi time string ke timeId
            $availableTimeIds = [];
            $skippedTimes = [];
            
            foreach ($request->time as $timeString) {
                $timeRecord = DB::table('times')
                    ->where('time', $timeString)
                    ->where('fieldId', $request->fieldId)
                    ->where('status', 'available')
                    ->first();
                    
                if ($timeRecord) {
                    $availableTimeIds[] = $timeRecord->timeId;
                } else {
                    $skippedTimes[] = $timeString;
                }
            }

            if (empty($availableTimeIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada waktu yang tersedia untuk reservasi pada field ini.',
                    'skipped_times' => $skippedTimes
                ], 422);
            }

            // Cek konflik reservasi (exclude current reservation)
            $conflicts = [];
            foreach ($availableTimeIds as $timeId) {
                $existingReservation = ReservationDetail::where('fieldId', $request->fieldId)
                    ->where('timeId', $timeId)
                    ->whereDate('date', $request->date)
                    ->whereHas('reservation', function($q) use ($id) {
                        $q->where('reservationId', '!=', $id);
                    })
                    ->exists();

                if ($existingReservation) {
                    $timeRecord = DB::table('times')
                        ->where('timeId', $timeId)
                        ->where('fieldId', $request->fieldId)
                        ->first();
                        
                    $conflicts[] = [
                        'fieldId' => $request->fieldId,
                        'timeId' => $timeId,
                        'time' => $timeRecord->time ?? 'Unknown',
                        'date' => $request->date,
                        'message' => 'Lapangan dan jam sudah dipesan pada tanggal tersebut.'
                    ];
                }
            }

            $conflictTimeIds = collect($conflicts)->pluck('timeId')->toArray();
            $validTimeIds = array_diff($availableTimeIds, $conflictTimeIds);

            if (empty($validTimeIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semua waktu yang tersedia sudah dipesan',
                    'conflicts' => $conflicts,
                    'skipped_times' => $skippedTimes
                ], 409);
            }

            // Update reservasi utama
            $reservation->update([
                'userId' => $request->userId ?? $reservation->userId,
                'name' => $request->name,
            ]);

            // Buat detail reservasi baru
            foreach ($validTimeIds as $timeId) {
                $reservation->details()->create([
                    'fieldId' => $request->fieldId,
                    'timeId' => $timeId,
                    'date' => $request->date,
                ]);
            }

            $bookedTimes = DB::table('times')
                ->whereIn('timeId', $validTimeIds)
                ->where('fieldId', $request->fieldId)
                ->pluck('time')
                ->toArray();

            $response = [
                'success' => true,
                'message' => 'Reservasi berhasil diperbarui',
                'reservation' => $reservation->load('details.field', 'details.time'),
                'booked_times' => $bookedTimes,
            ];

            if (!empty($skippedTimes)) {
                $response['skipped_times'] = $skippedTimes;
                $response['skipped_message'] = 'Beberapa waktu tidak tersedia atau tidak ada untuk field ini';
            }

            if (!empty($conflicts)) {
                $response['conflicts'] = $conflicts;
                $response['conflict_message'] = 'Beberapa waktu sudah dipesan dan diskip';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate closed field'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reservation = Reservation::where('paymentStatus', 'closed')->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Closed field tidak ditemukan'
                ], 404);
            }

            // Hapus data detail reservasi dan reservasi sebelumnya terlebih dahulu
            $reservation->details()->delete();
            $reservation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Closed field berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus closed field'
            ], 500);
        }
    }
}