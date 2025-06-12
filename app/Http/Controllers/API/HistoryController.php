<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Location;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function userReservations(Request $request, $id)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $location = $request->input('location'); 
        $paymentStatus = $request->input('paymentStatus'); 
        $paymentType = $request->input('paymentType'); 

        $locationIds = [];
        if ($location) {
            $locationIds = Location::where('locationName', 'like', '%' . $location . '%')->pluck('locationId')->toArray();
        }
        
        $query = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time',
        ])
            ->where('userId', $id)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->when(!empty($locationIds), function ($query) use ($locationIds) {
                $query->whereHas('details.field', function ($q) use ($locationIds) {
                    $q->whereIn('locationId', $locationIds);
                });
            })
            ->when($paymentStatus, function ($query) use ($paymentStatus) {
                $query->where('paymentStatus', $paymentStatus);
            })
            ->when($paymentType, function ($query) use ($paymentType) {
                $query->where('paymentType', $paymentType);
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
                    'details' => $reservation->details->map(function ($detail) {
                        return [
                            'fieldName' => $detail->field->name,
                            'time' => $detail->time,
                            'date' => $detail->date,
                        ];
                    })
                ];
            }),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
        ]);
    }
}