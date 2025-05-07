<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\ReservationDetail;
use App\Models\Field;
use App\Models\Time;

class ReservationController extends Controller
{
    // Buat reservasi baru
    public function store(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:users,userId',
            'details' => 'required|array|min:1',
            'details.*.fieldId' => 'required|exists:fields,fieldId',
            // 'details.*.timeId' => 'required|exists:times,timeId',
            'details.*.timeIds' => 'required|array|min:1',
            'details.*.timeIds.*' => 'required|exists:times,timeId',
            'details.*.date' => 'required|date',
            'name' => 'sometimes|string|max:255',
            'paymentStatus' => 'sometimes|string|in:pending,paid,cancelled',
        ]);

        $details = collect($request->details);

        // Cek konflik
        $conflicts = [];
        foreach ($details as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $exists = ReservationDetail::where('fieldId', $detail['fieldId'])
                    ->where('timeId', $timeId)
                    ->where('date', $detail['date'])
                    ->first();

                if ($exists) {
                    $conflicts[] = [
                        'fieldId' => $detail['fieldId'],
                        'timeId' => $timeId,
                        'date' => $detail['date'],
                    ];
                }
            }
        }

        if (!empty($conflicts)) {
            return response()->json([
                'message' => 'Beberapa lapangan dan jam sudah dipesan',
                'conflicts' => $conflicts
            ], 409);
        }

        // Hitung total biaya
        $total = 0;
        foreach ($details as $detail) {
            $time = Time::find($detail['timeId']);
            $total += $time->price;
        }

        // Buat reservasi utama
        $reservation = Reservation::create([
            'userId' => $request->userId,
            'name' => $request->name ?? 'Reservasi ' . now(),
            'paymentStatus' => $request->paymentStatus ?? 'pending',
            'paymentStatus' => 'pending',
            'total' => $total,
            'remaining' => 0,
        ]);

        // Simpan semua detail
        // Perulangan untuk menyimpan banyak timeId per fieldId
        foreach ($details as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $reservation->details()->create([
                    'fieldId' => $detail['fieldId'],
                    'timeId' => $timeId,
                    'date' => $detail['date'],
                ]);

                $time = Time::find($timeId);
                $total += $time->price;
            }
        }

        return response()->json([
            'message' => 'Reservasi berhasil dibuat',
            'reservation' => $reservation->load('details.field', 'details.time')
        ]);
    }

    public function index()
    {
        $reservations = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time',
            'user'
        ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reservations);
    }

    // Menampilkan detail reservasi tertentu
    public function show($id)
    {
        $reservation = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time',
            'user',
            'payment'
        ])->find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservasi tidak ditemukan'], 404);
        }

        return response()->json($reservation);
    }

    // Update data reservasi
    public function update(Request $request, $id)
    {
        $reservation = Reservation::with('details')->find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservasi tidak ditemukan'], 404);
        }

        $request->validate([
            'details' => 'required|array|min:1',
            'details.*.fieldId' => 'required|exists:fields,fieldId',
            'details.*.timeId' => 'required|exists:times,timeId',
            'details.*.date' => 'required|date',
            'name' => 'sometimes|string|max:255',
            'paymentStatus' => 'sometimes|string|in:pending,paid,cancelled',
        ]);

        $incomingDetails = collect($request->details);

        // Cek konflik
        $conflicts = [];
        foreach ($incomingDetails as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $exists = ReservationDetail::where('fieldId', $detail['fieldId'])
                    ->where('timeId', $timeId)
                    ->where('date', $detail['date'])
                    ->first();

                if ($exists) {
                    $conflicts[] = [
                        'fieldId' => $detail['fieldId'],
                        'timeId' => $timeId,
                        'date' => $detail['date'],
                    ];
                }
            }
        }

        if (!empty($conflicts)) {
            return response()->json([
                'message' => 'Beberapa lapangan dan jam sudah dipesan',
                'conflicts' => $conflicts
            ], 409);
        }

        // Hapus semua detail lama
        $reservation->details()->delete();

        // Tambahkan detail baru
        $total = 0;
        foreach ($incomingDetails as $detail) {
            $time = Time::find($detail['timeId']);
            $total += $time->price;

            $reservation->details()->create([
                'fieldId' => $detail['fieldId'],
                'timeId' => $detail['timeId'],
                'date' => $detail['date'],
            ]);
        }

        // Update total biaya
        $reservation->total = $total;

        // Update nama dan status pembayaran jika ada
        if ($request->has('name')) {
            $reservation->name = $request->name;
        }
        if ($request->has('paymentStatus')) {
            $reservation->paymentStatus = $request->paymentStatus;
        }
        // Jika ada detail yang sudah dibayar, ubah status reservasi menjadi 'paid'
        if ($reservation->details()->where('paymentStatus', 'paid')->exists()) {
            $reservation->paymentStatus = 'paid';
        }
        $reservation->save();

        return response()->json([
            'message' => 'Reservasi berhasil diperbarui',
            'reservation' => $reservation->load('details.field', 'details.time')
        ]);
    }

    // Hapus reservasi
    public function destroy($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservasi tidak ditemukan'], 404);
        }

        // Hapus detail terlebih dahulu karena relasi hasMany
        $reservation->details()->delete();

        // Hapus reservasi
        $reservation->delete();

        return response()->json(['message' => 'Reservasi berhasil dihapus']);
    }

    // Menampilkan detail reservasi tertentu

    // Filter reservasi berdasarkan userId, fieldId, dan date
    public function filter(Request $request)
    {
        $query = Reservation::with(['details.field', 'details.time', 'user']);

        if ($request->has('userId')) {
            $query->where('userId', $request->userId);
        }

        if ($request->has('fieldId')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('fieldId', $request->fieldId);
            });
        }

        if ($request->has('date')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('date', $request->date);
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Filtered reservations retrieved successfully',
            'data' => $query->get(),
        ]);
    }

    // Function untuk mengubah status pembayaran reservasi
    public function updatePaymentStatus(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservasi tidak ditemukan'], 404);
        }

        $request->validate([
            'paymentStatus' => 'required|string|in:pending,paid,cancelled',
        ]);

        $reservation->paymentStatus = $request->paymentStatus;
        $reservation->save();

        return response()->json([
            'message' => 'Status pembayaran reservasi berhasil diperbarui',
            'reservation' => $reservation
        ]);
    }

    // function untuk melakukan konfitmasi pembayaran
    public function confirmPayment(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservasi tidak ditemukan'], 404);
        }

        $request->validate([
            'paymentStatus' => 'required|string|in:paid,cancelled',
        ]);

        $reservation->paymentStatus = $request->paymentStatus;
        $reservation->save();

        return response()->json([
            'message' => 'Konfirmasi pembayaran berhasil',
            'reservation' => $reservation
        ]);
    }
}
