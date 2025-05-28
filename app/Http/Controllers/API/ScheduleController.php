<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Field;
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
        $sportId = $request->input('sport');
        $locationId = $request->input('locationId');

        // Ambil jadwal reservasi
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
                'fields.name as fieldName',
                'sports.sportName as sport',
                'reservations.paymentStatus'
            ])
            ->whereIn('reservations.paymentStatus', ['pending', 'complete', 'dp', 'closed']);

        if (!empty($sportId) && $sportId !== 'all') {
            $query->where('sports.sportId', $sportId);
        }

        if (!empty($date)) {
            $query->where('reservation_details.date', $date);
        }

        if (!empty($locationId) && $locationId !== 'all') {
            $query->where('locations.locationId', $locationId);
        }

        $schedules = $query->get();

        // Ambil semua field
        $fieldsRaw = DB::table('fields')
            ->leftJoin('locations', 'fields.locationId', '=', 'locations.locationId')
            ->leftJoin('sports', 'fields.sportId', '=', 'sports.sportId')
            ->select([
                'fields.fieldId',
                'fields.name',
            ])
            ->when(!empty($sportId) && $sportId !== 'all', function ($q) use ($sportId) {
                $q->where('fields.sportId', $sportId);
            })
            ->when(!empty($locationId) && $locationId !== 'all', function ($q) use ($locationId) {
                $q->where('fields.locationId', $locationId);
            })
            ->where('fields.deleted_at', null)
            ->get();

        $fieldIds = $fieldsRaw->pluck('fieldId')->toArray();

        // Ambil time berdasarkan field
        $times = DB::table('times')
            ->whereIn('fieldId', $fieldIds)
            ->where('status', 'available')
            ->select('fieldId', 'time')
            ->get()
            ->groupBy('fieldId');

        // Gabungkan times ke fields
        $fields = $fieldsRaw->map(function ($field) use ($times) {
            return [
                'fieldId' => $field->fieldId,
                'name' => $field->name,
                'times' => $times[$field->fieldId]->pluck('time')->toArray() ?? []
            ];
        });

        return response()->json([
            'schedules' => $schedules,
            'fields' => $fields
        ]);
    }
}
