<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /**
     * Tampilkan daftar jadwal lapangan
     *
     * Mengambil daftar jadwal lapangan berdasarkan filter olahraga dan lokasi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = DB::table('reservations')
            ->join('reservation_details', 'reservations.reservationId', '=', 'reservation_details.reservationId')
            ->join('fields', 'reservation_details.fieldId', '=', 'fields.fieldId')
            ->join('times', 'reservation_details.timeId', '=', 'times.timeId')
            ->join('sports', 'fields.sportId', '=', 'sports.sportId')
            ->join('locations', 'fields.locationId', '=', 'locations.locationId')
            ->select([
                // 'reservations.reservationId',
                'reservations.name',
                'reservation_details.date',
                'times.time as fieldTime',
                'fields.fieldId',
                // 'sports.sportName as sport',
                // 'locations.locationName as location',
                'reservations.paymentStatus'
            ]);

        // Filter by sport
        if ($request->has('sportId')) {
            $query->where('sports.sportId', $request->sportId);
        }

        // Filter by location
        if ($request->has('locationId')) {
            $query->where('locations.locationId', $request->locationId);
        }

        $schedules = $query->get();

        return response()->json($schedules);
    }
}
