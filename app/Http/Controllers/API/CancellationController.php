<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cancellation;
use App\Models\Reservation;
use Illuminate\Http\Request;

class CancellationController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $locationId = $request->input('locationId');
        $paymentStatus = $request->input('paymentStatus');
        $date = $request->input('date');

        $query = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time',
            'user',
            'cancellation'
        ])
            ->whereIn('paymentStatus', ['waiting', 'canceled'])
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereHas('details.field.location', function ($q) use ($locationId) {
                    $q->where('locationId', $locationId);
                });
            })
            ->when($paymentStatus, function ($query) use ($paymentStatus) {
                $query->where('paymentStatus', $paymentStatus);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->when($date, function ($query) use ($date) {
                $query->whereHas('details', function ($q) use ($date) {
                    $q->where('date', $date);
                });
            })
            ->orderByRaw("FIELD(paymentStatus, 'waiting', 'canceled')")
            ->orderByDesc('updated_at');


        $reservations = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'Semua reservasi berhasil diambil',
            'data' => $reservations->map(function ($reservation) {
                return [
                    'reservationId' => $reservation->reservationId,
                    // 'userId' => $reservation->userId,
                    'name' => $reservation->name,
                    'paymentStatus' => $reservation->paymentStatus,
                    'paymentType' => $reservation->paymentType,
                    'total' => $reservation->total,
                    'created_at' => $reservation->created_at,
                    // 'status' => 'upcoming',
                    'updated_at' => $reservation->updated_at,
                    'details' => $reservation->details->map(function ($detail) {
                        return [
                            // 'detailId' => $detail->detailId,
                            // 'reservationId' => $detail->reservationId,
                            'fieldName' => $detail->field->name,
                            'time' => $detail->time,
                            'date' => $detail->date,
                            // 'status' => $detail->status,
                        ];
                    }),
                    'cancellation' => $reservation->cancellation
                ];
            })
        ]);
    }
    public function refund($id){
        try {
            $reservation = Reservation::with('cancellation')->find($id);
            
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => "Reservasi tidak ditemukan"
                ], 404);
            }

            $reservation->paymentStatus = 'canceled';
            $reservation->save();

            // Hapus cancellation jika ada
            if ($reservation->cancellation) {
                $reservation->cancellation->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil diubah dan data pembatalan dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    public function refundApplication(Request $request){
        $reservation = Reservation::find($request->reservationId);
        $cancellation = Cancellation::create([
            'reservationId' => $reservation->reservationId,
            'accountName' => $request->accountName,
            'accountNumber' => $request->accountNumber,
            'paymentPlatform' => $request->paymentPlatform,
            'reason' => $request->reason,
        ]);
        $reservation->paymentStatus = 'waiting';
        $reservation->save();
        return response()->json([
            'success' => true,
            'message' => 'Pembatalan berhasil dibuat',
            'cancellation' => $cancellation
        ]);
    }
    public function cancellationDP(Request $request){
        $reservation = Reservation::find($request->reservationId);
        $reservation->paymentStatus = 'canceled';
        $reservation->save();
        return response()->json([
            'success' => true,
            'message' => 'Pembatalan berhasil dibuat',
        ]);
    }
}
