<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function userReservations(Request $request, $id)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');

        $query = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time',
            // 'user'
        ])
            ->where('userId', $id)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderByDesc('created_at');

        $reservations = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'Semua reservasi berhasil diambil',
            'data' => $reservations->map(function ($reservation) {
                return [
                    'reservationId' => $reservation->reservationId,
                    'userId' => $reservation->userId,
                    'locationName' => $reservation->details->first()?->field->location->locationName,
                    'name' => $reservation->name,
                    'paymentStatus' => $reservation->paymentStatus,
                    'paymentType' => $reservation->paymentType,
                    'total' => $reservation->total,
                    'created_at' => $reservation->created_at,
                    'status' => 'upcoming',
                    // 'updated_at' => $reservation->updated_at,
                    'details' => $reservation->details->map(function ($detail) {
                        return [
                            // 'detailId' => $detail->detailId,
                            // 'reservationId' => $detail->reservationId,
                            'fieldName' => $detail->field->name,
                            'time' => $detail->time,
                            'date' => $detail->date,
                            // 'status' => $detail->status,
                        ];
                    })
                ];
            })
        ]);
    }
}