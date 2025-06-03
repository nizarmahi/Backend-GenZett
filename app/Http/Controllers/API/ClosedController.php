<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\ReservationDetail;
use App\Models\Field;
use App\Models\Payment;
use App\Models\Time;
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
        // ->where('paymentStatus', 'closed')
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
                        // 'reservationId' => $reservation->reservationId,
                        // 'userId' => $reservation->userId,
                        'name' => $reservation->name,
                        'paymentStatus' => $reservation->paymentStatus,
                        // 'total' => $reservation->total,
                        'created_at' => $reservation->created_at,
                        // 'status' => 'upcoming',
                        // 'updated_at' => $reservation->updated_at,
                        'details' => $reservation->details->map(function ($detail) {
                            return [
                                // 'detailId' => $detail->detailId,
                                // 'reservationId' => $detail->reservationId,
                                'fieldName' => $detail->field->name,
                                'time' => $detail->time->time,
                                'date' => $detail->date,
                                // 'status' => $detail->status,
                            ];
                        })
                    ];
                })
            ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId',
            'name' => 'required|string|max:255',
            'fieldId' => 'required|exists:fields,fieldId',
            'date' => 'required|date',
            'time' => 'required|array|min:1',
            'time.*' => 'required|string', // Format waktu seperti "08:00:00"
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
            // PERBAIKAN: Cari timeId berdasarkan time string DAN fieldId
            $timeRecord = DB::table('times')
                ->where('time', $timeString)
                ->where('fieldId', $request->fieldId) // TAMBAHKAN FILTER FIELD
                ->where('status', 'available')
                ->first();
                
            if ($timeRecord) {
                $availableTimeIds[] = $timeRecord->timeId;
            } else {
                $skippedTimes[] = $timeString;
            }
        }

        // Jika tidak ada waktu yang available
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
                ->whereDate('date', $request->date) // Gunakan whereDate untuk handle datetime
                ->exists();

            if ($existingReservation) {
                // Ambil time string untuk konflik dari field yang sama
                $timeRecord = DB::table('times')
                    ->where('timeId', $timeId)
                    ->where('fieldId', $request->fieldId) // Pastikan dari field yang sama
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

        // Filter timeIds yang tidak conflict
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

        // Buat reservasi utama dengan data statis
        $reservation = Reservation::create([
            'userId' => $request->userId,
            'name' => $request->name,
            'paymentStatus' => 'closed', // Statis
            'paymentType' => 'reguler', // Statis
            'total' => 0, // Statis, bisa dihitung berdasarkan jumlah waktu yang berhasil
            'remaining' => 0, // Statis
        ]);

        // Simpan detail reservasi untuk waktu yang valid
        foreach ($validTimeIds as $timeId) {
            $reservation->details()->create([
                'fieldId' => $request->fieldId,
                'timeId' => $timeId,
                'date' => $request->date,
            ]);
        }

        // Siapkan response dengan informasi tambahan
        // PERBAIKAN: Ambil waktu yang booked dari field yang spesifik
        $bookedTimes = DB::table('times')
            ->whereIn('timeId', $validTimeIds)
            ->where('fieldId', $request->fieldId) // Pastikan dari field yang sama
            ->pluck('time')
            ->toArray();

        $response = [
            'success' => true,
            'message' => 'Reservasi berhasil dibuat',
            'reservation' => $reservation->load('details.field', 'details.time'),
            'booked_times' => $bookedTimes,
        ];

        // Tambahkan informasi waktu yang diskip jika ada
        if (!empty($skippedTimes)) {
            $response['skipped_times'] = $skippedTimes;
            $response['skipped_message'] = 'Beberapa waktu tidak tersedia atau tidak ada untuk field ini';
        }

        // Tambahkan informasi konflik jika ada
        if (!empty($conflicts)) {
            $response['conflicts'] = $conflicts;
            $response['conflict_message'] = 'Beberapa waktu sudah dipesan dan diskip';
        }

        return response()->json($response, 201);
    }
}