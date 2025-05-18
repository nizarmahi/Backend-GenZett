<?php

namespace App\Http\Controllers\API; // Perhatikan perubahan dari Api menjadi API

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\ReservationDetail;
use App\Models\Field;
use App\Models\Time;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @group Reservation Management
 *
 * API for managing reservations
 */
class ReservationController extends Controller
{
    /**
     * Tampilkan Semua Reservasi
     *
     * Mengambil semua reservasi dengan detail lapangan dan waktu.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $paymentStatus = $request->input('paymentStatus');
        $date = $request->input('date');

        $query = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time',
            'user'
        ])
        ->orderByDesc('created_at');

        if (!empty($paymentStatus)) {
            $query->where('paymentStatus', $paymentStatus);
        }

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (!empty($date)) {
            $query->whereHas('details', function ($q) use ($date) {
                $q->where('date', $date);
            });
        }

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

    /**
     * Buat Reservasi Baru
     *
     * Membuat reservasi baru dengan detail lapangan dan waktu.
     *
     * @bodyParam userId integer required The ID of the user making the reservation. Example: 1
     * @bodyParam details array required Array of reservation details.
     * @bodyParam details.*.fieldId integer required The ID of the field to reserve. Example: 1
     * @bodyParam details.*.timeIds array required Array of time slot IDs to reserve. Example: [1, 2]
     * @bodyParam details.*.date string required The date for the reservation in Y-m-d format. Example: 2025-05-15
     * @bodyParam name string optional The name for this reservation. Example: "Weekend Match"
     * @bodyParam paymentStatus string optional The payment status (pending, paid, cancelled). Example: pending
     *
     * @response {
     *   "message": "Reservasi berhasil dibuat",
     *   "reservation": {
     *       "reservationId": 1,
     *      "userId": 6,
     *      "name": "Booking 1",
     *       "paymentStatus": "pending",
     *       "total": 150000,
     *
     *
     *       "details": [
     *           {
     *           "detailId": 1,
     *           "reservationId": 1,
     *           "fieldId": 1,
     *           "timeId": 1,
     *           "date": "2025-04-20",
     *
     *
     *           "field": {
     *               "fieldId": 1,
     *               "locationId": 1,
     *               "sportId": 1,
     *               "name": "Lapangan Futsal 1",
     *               "description": "Indoor",
     *
     *
     *               "location": {
     *               "locationId": 1,
     *               "locationName": "Lowokwaru",
     *               "description": "Daerah kampus dan pemukiman",
     *               "locationPath": "lowokwaru.jpg",
     *               "address": "Jl. Soekarno Hatta, Malang",
     *
     *
     *               },
     *               "sport": {
     *               "sportId": 1,
     *               "sportName": "Futsal",
     *               "description": "Olahraga mirip sepak bola, dimainkan di dalam ruangan.",
     *
     *
     *              }
     *           },
     *           "time": {
     *               "timeId": 1,
     *               "fieldId": 1,
     *               "time": "06:00:00",
     *               "status": "booked",
     *               "price": 144408,
     *
     *
     *           }
     *           }
     *       ],
     *       "user": {
     *           "userId": 6,
     *           "role": "user",
     *           "username": "user01",
     *           "name": "Ali",
     *           "email": "ali@mail.com",
     *           "phone": "0811111111",
     *
     *
     *       }
     *   }    },
     *
     * @response 409 {
     *   "message": "Beberapa lapangan dan jam sudah dipesan",
     *   "conflicts": [
     *     {
     *       "fieldId": 1,
     *       "timeId": 1,
     *       "date": "2025-05-15"
     *       "status": "booked"
     *     }
     *   ]
     * }
     *
     * @response 422 {
     *   "message": "data yang diberikan tidak valid",
     *   "errors": {
     *     "userId": ["userId field dibutuhkan."],
     *     "details": ["details field dibutuhkan."],
     *     "details.0.fieldId": ["fieldId field dibutuhkan."],
     *     "details.0.timeIds": ["timeIds field dibutuhkan."],
     *     "details.0.date": ["date field dibutuhkan."],
     *     "details.0.timeIds.0": ["timeIds.0 field dibutuhkan."],
     *   }
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId',
            'details' => 'required|array|min:1',
            'details.*.fieldId' => 'required|exists:fields,fieldId',
            'details.*.timeIds' => 'required|array|min:1',
            'details.*.timeIds.*' => 'required|exists:times,timeId',
            'details.*.date' => 'required|date',
            'name' => 'sometimes|string|max:255',
            'paymentStatus' => 'sometimes|string|in:pending,paid,cancelled',
            'total' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $details = collect($request->details);

        // Cek konflik
        $conflicts = [];
        foreach ($details as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $exists = ReservationDetail::where('fieldId', $detail['fieldId'])
                    ->where('timeId', $timeId)
                    ->where('date', $detail['date'])
                    ->where('status', 'booked')
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
                'success' => false,
                'message' => 'Beberapa lapangan dan jam sudah dipesan',
                'conflicts' => $conflicts
            ], 409);
        }

        // Buat reservasi utama
        $reservation = Reservation::create([
            'userId' => $request->userId,
            'name' => $request->name ?? 'Reservasi ' . now(),
            'paymentStatus' => $request->paymentStatus ?? 'pending',
            'total' => $request->total ?? 0,
            'remaining' => 0,
        ]);

        // Simpan semua detail
        foreach ($details as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $reservation->details()->create([
                    'fieldId' => $detail['fieldId'],
                    'timeId' => $timeId,
                    'date' => $detail['date'],
                    'status' => '',
                ]);
            }
        }

        // Ubah status times yang sudah dipesan menjadi 'booked'
        foreach ($details as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $time = Time::find($timeId);
                $time->status = 'booked';
                $time->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dibuat',
            'reservation' => $reservation->load('details.field', 'details.time')
        ], 201);
    }

    /**
     * Detail Reservasi
     *
     * Mengambil detail reservasi berdasarkan ID.
     *
     * @urlParam id integer required The ID of the reservation. Example: 1
     *
     * @response {
     *   "success": true,
     *   "message": "Detail reservasi berhasil diambil",
     *   "data": {
     *     "id": 1,
     *     "userId": 1,
     *     "name": "Weekend Match",
     *     "paymentStatus": "pending",
     *     "total": 200000,
     *     "remaining": 0,
     *     "created_at": "2025-05-12T10:00:00.000000Z",
     *     "updated_at": "2025-05-12T10:00:00.000000Z",
     *     "details": [],
     *     "user": {},
     *     "payment": {}
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Reservasi tidak ditemukan"
     * }
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
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
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan. Detail Reservasi'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation details retrieved successfully',
            'data' => $reservation
        ]);
    }

    /**
     * Update Reservasi
     *
     * Mengubah reservasi yang sudah ada dengan detail baru.
     *
     * @urlParam id integer required The ID of the reservation to update. Example: 1
     * @bodyParam details array required Array of reservation details.
     * @bodyParam details.*.fieldId integer required The ID of the field to reserve. Example: 1
     * @bodyParam details.*.timeId integer required The ID of the time slot to reserve. Example: 1
     * @bodyParam details.*.date string required The date for the reservation in Y-m-d format. Example: 2025-05-15
     * @bodyParam name string optional The name for this reservation. Example: "Updated Weekend Match"
     * @bodyParam paymentStatus string optional The payment status (pending, paid, cancelled). Example: paid
     *
     * @response {
     *   "success": true,
     *   "message": "Reservasi berhasil diperbarui",
     *   "reservation": {}
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Reservasi tidak ditemukan"
     * }
     *
     * @response 409 {
     *   "success": false,
     *   "message": "Beberapa lapangan dan jam sudah dipesan",
     *   "conflicts": []
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $reservation = Reservation::with('details')->find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan. Update Reservasi.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'details' => 'required|array|min:1',
            'details.*.fieldId' => 'required|exists:fields,fieldId',
            'details.*.timeId' => 'required|exists:times,timeId',
            'details.*.date' => 'required|date',
            'name' => 'sometimes|string|max:255',
            'paymentStatus' => 'sometimes|string|in:pending,paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $incomingDetails = collect($request->details);

        // Cek konflik
        $conflicts = [];
        foreach ($incomingDetails as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $exists = ReservationDetail::where('fieldId', $detail['fieldId'])
                    ->where('timeId', $timeId)
                    ->where('date', $detail['date'])
                    ->where('status', 'booked')
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
                'success' => false,
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
            'success' => true,
            'message' => 'Reservasi berhasil diperbarui',
            'reservation' => $reservation->load('details.field', 'details.time')
        ]);
    }

    /**
     * Hapus Reservasi
     *
     * Menghapus reservasi berdasarkan ID.
     *
     * @urlParam id integer required The ID of the reservation to delete. Example: 1
     *
     * @response {
     *   "success": true,
     *   "message": "Reservasi berhasil dihapus"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Reservasi tidak ditemukan"
     * }
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan. Hapus Reservasi'
            ], 404);
        }

        // Ubah status time menjadi 'available'
        foreach ($reservation->details as $detail) {
            $time = Time::find($detail->timeId);
            if ($time) {
                $time->status = 'available';
                $time->save();
            }
        }

        // Hapus detail terlebih dahulu karena relasi hasMany
        $reservation->details()->delete();

        // Hapus reservasi
        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dihapus'
        ]);
    }

    /**
     * Filter Reservasi
     *
     * Filter reservasi berdasarkan userId, fieldId, dan date.
     *
     * @queryParam userId integer optional Filter by user ID. Example: 1
     * @queryParam fieldId integer optional Filter by field ID. Example: 2
     * @queryParam date string optional Filter by date in Y-m-d format. Example: 2025-05-15
     *
     * @response {
     *   "success": true,
     *   "message": "Filter reservasi berhasil diambil",
     *   "data": []
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Update status pembayaran reservasi
     *
     * Mengubah status pembayaran reservasi berdasarkan ID.
     *
     * @urlParam id integer required The ID of the reservation. Example: 1
     * @bodyParam paymentStatus string required The new payment status (pending, paid, cancelled). Example: paid
     *
     * @response {
     *   "success": true,
     *   "message": "Status pembayaran reservasi berhasil diperbarui",
     *   "reservation": {}
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Reservasi tidak ditemukan"
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan. Update Payment Status.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'paymentStatus' => 'required|string|in:pending,paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation->paymentStatus = $request->paymentStatus;
        $reservation->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran reservasi berhasil diperbarui',
            'reservation' => $reservation
        ]);
    }

    /**
     * Konfirmasi Pembayaran
     *
     * Mengonfirmasi pembayaran reservasi.
     *
     * @urlParam id integer required The ID of the reservation. Example: 1
     * @bodyParam paymentStatus string required The payment status (paid, cancelled). Example: paid
     *
     * @response {
     *   "success": true,
     *   "message": "Konfirmasi pembayaran berhasil",
     *   "reservation": {}
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Reservasi tidak ditemukan"
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmPayment(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan. Konfirmasi Pembayaran'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'paymentStatus' => 'required|string|in:paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation->paymentStatus = $request->paymentStatus;
        $reservation->save();

        return response()->json([
            'success' => true,
            'message' => 'Konfirmasi pembayaran berhasil',
            'reservation' => $reservation
        ]);
    }
    /**
     * Batalkan Reservasi
     *
     * Membatalkan reservasi berdasarkan ID.
     *
     * @urlParam id integer required The ID of the reservation. Example: 1
     *
     * @response {
     *   "success": true,
     *   "message": "Reservasi berhasil dibatalkan"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Reservasi tidak ditemukan"
     * }
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan. Cancel Reservasi'
            ], 404);
        }

        // Ubah status time menjadi 'available'
        foreach ($reservation->details as $detail) {
            $time = Time::find($detail->timeId);
            if ($time) {
                $time->status = 'available';
                $time->save();
            }
        }

        $reservation->paymentStatus = 'cancelled';
        $reservation->save();

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dibatalkan'
        ]);
    }

    /**
     * Ambil Semua Lokasi dan Olahraga
     *
     * Mengambil semua lokasi dengan harga minimum dari times dan olahraga yang tersedia.
     *
     * @queryParam sport string optional Filter by sport name. Example: "Futsal"
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllLocations(Request $request)
    {
        try {
            // Ambil filter dari query string jika ada (misalnya: ?sport=Futsal)
            $sportFilter = $request->query('sport');

            // Ambil data lokasi dengan harga minimum dari times
            $locations = DB::table('locations')
                ->select(
                    'locations.locationId',
                    'locations.locationName as name',
                    'locations.locationPath as imageUrl',
                    'locations.address',
                    DB::raw('MIN(times.price) as minPrice')
                )
                ->leftJoin('fields', 'locations.locationId', '=', 'fields.locationId')
                ->leftJoin('times', 'fields.fieldId', '=', 'times.fieldId')
                ->groupBy('locations.locationId', 'locations.locationName', 'locations.locationPath', 'locations.address');

            // Jika ada filter sport, lakukan join tambahan
            if ($sportFilter) {
                $locations->join('fields as f', 'locations.locationId', '=', 'f.locationId')
                    ->join('sports', 'f.sportId', '=', 'sports.sportId')
                    ->where('sports.sportName', $sportFilter);
            }

            $locationResults = $locations->get();

            // Format dan ambil data sport untuk setiap lokasi
            $locationsWithSports = $locationResults->map(function ($location) {
                // Ambil semua sport yang ada di lokasi ini melalui tabel fields
                $sportsQuery = DB::table('sports')
                    ->select('sports.sportName')
                    ->join('fields', 'sports.sportId', '=', 'fields.sportId')
                    ->where('fields.locationId', $location->locationId)
                    ->distinct();

                $sportNames = $sportsQuery->pluck('sportName')->toArray();
                $sportCount = $sportsQuery->count();

                // Format harga ke format rupiah
                $formattedPrice = $location->minPrice
                    ? 'Rp ' . number_format($location->minPrice, 0, ',', '.')
                    : 'Rp 0';

                return [
                    'locationId' => $location->locationId,
                    'name' => $location->name,
                    'address' => $location->address,
                    'imageUrl' => $location->imageUrl
                        ? asset('storage/' . $location->imageUrl)
                        : asset('/images/futsal.png'),
                    'price' => $formattedPrice,
                    'sports' => $sportNames,
                    'sportCount' => $sportCount,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $locationsWithSports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Ambil Semua Olahraga
     *
     * Mengambil semua olahraga yang tersedia
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSports()
    {
        try {
            $sports = DB::table('sports')
                ->select('sportName')
                ->pluck('sportName')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $sports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Olahraga.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Ambil semua jadwal reservasi di lokasi tertentu
     *
     * @queryParam locationId integer required The ID of the location. Example: 1
     * @queryParam sport string optional Filter by sport name. Example: "Futsal"
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScheduleByLocation(Request $request, $locationId)
    {
        try {
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addDays(7)->format('Y-m-d');
            $sportId = $request->query('sportId'); // Ambil sportId dari query

            $dates = collect();
            $current = strtotime($startDate);
            $end = strtotime($endDate);

            while ($current <= $end) {
                $dates->push(date('Y-m-d', $current));
                $current = strtotime('+1 day', $current);
            }

            $fieldQuery = DB::table('fields')
                ->where('locationId', $locationId);

            if ($sportId) {
                $fieldQuery->where('sportId', $sportId);
            }

            $fields = $fieldQuery->get();

            $scheduleData = [];

            foreach ($fields as $field) {
                $timeSlots = DB::table('times')
                    ->where('fieldId', $field->fieldId)
                    ->orderBy('time')
                    ->get();

                $dailySchedules = [];

                foreach ($dates as $date) {
                    $slots = $timeSlots->map(function ($slot) use ($field, $date) {
                        $isReserved = DB::table('reservation_details')
                            ->where('fieldId', $field->fieldId)
                            ->where('timeId', $slot->timeId)
                            ->where('date', $date)
                            ->exists();

                        return [
                            'timeId' => $slot->timeId,
                            'time' => $slot->time,
                            'price' => 'Rp ' . number_format($slot->price, 0, ',', '.'),
                            'status' => $isReserved ? 'booked' : 'available',
                        ];
                    });

                    $dailySchedules[] = [
                        'date' => $date,
                        'schedules' => $slots,
                    ];
                }

                $scheduleData[] = [
                    'fieldId' => $field->fieldId,
                    'fieldName' => $field->name,
                    'description' => $field->description,
                    'dailySchedules' => $dailySchedules,
                ];
            }

            return response()->json([
                'success' => true,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'locationId' => $locationId,
                'sportId' => $sportId,
                'fields' => $scheduleData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jadwal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
