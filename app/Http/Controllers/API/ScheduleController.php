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
        $date = $request->input('date');
        $sportId = $request->input('sport'); // Ubah ke sportId karena frontend kirim ID
        $locationId = $request->input('locationId');
        
        $query = DB::table('reservations')
            ->join('reservation_details', 'reservations.reservationId', '=', 'reservation_details.reservationId')
            ->join('fields', 'reservation_details.fieldId', '=', 'fields.fieldId')
            ->join('times', 'reservation_details.timeId', '=', 'times.timeId')
            ->join('sports', 'fields.sportId', '=', 'sports.sportId')
            ->join('locations', 'fields.locationId', '=', 'locations.locationId')
            ->select([
                'locations.locationId',
                'reservations.name',
                'reservation_details.date',
                'times.time as fieldTime',
                'fields.name',
                'sports.sportName as sport',
                'reservations.paymentStatus'
            ])
            ->whereIn('reservations.paymentStatus', ['pending', 'complete', 'dp']);

        // Filter by sportId
        if (!empty($sportId) && $sportId !== 'all') {
            $query->where('sports.sportId', $sportId);
        }

        // Filter by date
        if (!empty($date)) {
            $query->where('reservation_details.date', $date);
        }
        
        // Filter by locationId
        if (!empty($locationId) && $locationId !== 'all') {
            $query->where('locations.locationId', $locationId);
        }

        $schedules = $query->get();

        return response()->json($schedules);
    }

}
