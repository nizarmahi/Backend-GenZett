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
                'sports.sportId as sport',
                'sports.sportName',
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

        $fieldsQuery = DB::table('fields')
            ->leftJoin('locations', 'fields.locationId', '=', 'locations.locationId')
            ->leftJoin('sports', 'fields.sportId', '=', 'sports.sportId')
            ->select([
                'fields.fieldId',
                'fields.name',
                'fields.sportId',
                'sports.sportName',
                'locations.locationId'
            ])
            ->where('fields.deleted_at', null);

        if (!empty($sportId) && $sportId !== 'all') {
            $fieldsQuery->where('fields.sportId', $sportId);
        }

        if (!empty($locationId) && $locationId !== 'all') {
            $fieldsQuery->where('fields.locationId', $locationId);
        }

        $fieldsRaw = $fieldsQuery->get();
        $fieldIds = $fieldsRaw->pluck('fieldId')->toArray();

        // Ambil time berdasarkan field dengan status available
        $times = DB::table('times')
            ->whereIn('fieldId', $fieldIds)
            ->where('status', 'available')
            ->select('fieldId', 'time')
            ->orderBy('time')
            ->get()
            ->groupBy('fieldId');

        // Gabungkan times ke fields dan filter hanya field yang memiliki waktu tersedia
        $fields = $fieldsRaw->map(function ($field) use ($times) {
            $fieldTimes = $times->get($field->fieldId, collect())->pluck('time')->toArray();
            
            return [
                'fieldId' => $field->fieldId,
                'name' => $field->name,
                'sportId' => $field->sportId,
                'sportName' => $field->sportName,
                'locationId' => $field->locationId,
                'times' => $fieldTimes
            ];
        })->filter(function ($field) {
            return !empty($field['times']);
        })->values();

        if ($fields->isEmpty()) {
            return response()->json([
                'schedules' => [],
                'fields' => [],
                'message' => 'Tidak ada lapangan tersedia untuk filter yang dipilih'
            ]);
        }

        // Add metadata for debugging
        $metadata = [
            'filters_applied' => [
                'date' => $date,
                'sport_id' => $sportId,
                'location_id' => $locationId
            ],
            'total_schedules' => $schedules->count(),
            'total_fields' => $fields->count(),
            'available_times_count' => $fields->sum(function($field) {
                return count($field['times']);
            })
        ];

        return response()->json([
            'schedules' => $schedules,
            'fields' => $fields,
            'metadata' => $metadata
        ]);
    }
}
