<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\ReservationDetail;
use App\Models\Field;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Time;
use App\Models\HistoryReservationUser;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @group Reservation Management
 *
 * API for managing reservations
 */
class ReservationController extends Controller
{
    /**
     * Tampilkan Semua Reservasi berdasarkan lokasi
     *
     * Mengambil semua reservasi di lokasi tertentu dengan detail lapangan dan waktu.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $locationId = $request->input('locationId');
        $paymentStatus = $request->input('paymentStatus');
        $paymentType = $request->input('paymentType');
        $date = $request->input('date');

        $query = Reservation::with([
            'details.field.location',
            'details.field.sport',
            'details.time',
            'user'
        ])
            ->whereNotIn('paymentStatus', ['closed', 'canceled', 'waiting'])
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereHas('details.field.location', function ($q) use ($locationId) {
                    $q->where('locationId', $locationId);
                });
            })
            ->when($paymentStatus, function ($query) use ($paymentStatus) {
                $query->where('paymentStatus', $paymentStatus);
            })
            ->when($paymentType, function ($query) use ($paymentType) {
                $query->where('paymentType', $paymentType);
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
                    'reservationId' => $reservation->reservationId,
                    // 'userId' => $reservation->userId,
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
     * @bodyParam paymentType string optional The payment type (reguler, membership). Example: reguler
     *
     * @response {
     *   "message": "Reservasi berhasil dibuat",
     *   "reservation": {
     *       "reservationId": 1,
     *      "userId": 6,
     *      "name": "Booking 1",
     *       "paymentStatus": "pending",
     *       "paymentType": "reguler",
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
            'paymentStatus' => 'sometimes|string|in:pending,paid,canceled',
            'paymentType' => 'sometimes|string|in:reguler,membership',
            'total' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->paymentType === 'membership' && $request->membershipId) {
            $membership = Membership::find($request->membershipId);
            // Ambil locationId & sportId dari lapangan yang dipesan
            $fieldIds = collect($request->details)->pluck('fieldId')->unique();
            $fields = Field::whereIn('fieldId', $fieldIds)->get(['fieldId', 'locationId', 'sportId']);

            // Cek apakah semua lapangan sesuai dengan membership
            foreach ($fields as $field) {
                if ($field->locationId != $membership->locationId || $field->sportId != $membership->sportId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Membership tidak berlaku untuk lokasi atau olahraga yang dipilih.',
                        'invalidField' => $field,
                    ], 422);
                }
            }
        }

        if ($request->paymentType === 'membership' && !$request->membershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Membership belum dipilih.',
                'invalidField' => $field,
            ], 422);
        }

        $details = collect($request->details);

        $conflicts = [];
        foreach ($details as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                // Cek apakah sudah ada reservasi dengan fieldId, timeId, dan date yang sama
                $existingReservation = ReservationDetail::where('fieldId', $detail['fieldId'])
                    ->where('timeId', $timeId)
                    ->where('date', $detail['date'])
                    ->exists();

                if ($existingReservation) {
                    $conflicts[] = [
                        'fieldId' => $detail['fieldId'],
                        'timeId' => $timeId,
                        'date' => $detail['date'],
                        'message' => 'Lapangan dan jam sudah dipesan pada tanggal tersebut.'
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
            'membershipId' => $request->paymentType === 'membership' ? $request->membershipId : null,
            'name' => $request->name ?? 'Reservasi ' . now(),
            'paymentStatus' => $request->paymentStatus ?? 'pending',
            'paymentType' => $request->paymentType ?? 'reguler',
            'total' => $request->total ?? 0,
            'remaining' => 0,
        ]);

        if ($request->paymentType === 'membership') {
            $reservation->membership()->attach($request->membershipId);
        }

        // Simpan semua detail
        foreach ($details as $detail) {
            foreach ($detail['timeIds'] as $timeId) {
                $reservation->details()->create([
                    'fieldId' => $detail['fieldId'],
                    'timeId' => $timeId,
                    'date' => $detail['date'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dibuat',
            'reservation' => $reservation->load('details.field', 'details.time', 'membership')
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
     *     "paymentType": "reguler",
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
     * Update status pembayaran reservasi
     *
     * Mengubah status pembayaran reservasi berdasarkan ID.
     *
     * @urlParam id integer required The ID of the reservation. Example: 1
     * @bodyParam paymentStatus string required The new payment status (pending, paid, cancelled). Example: paid
     * @bodyParam paymentType string optional The payment type (reguler, membership). Example: reguler
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
            'paymentStatus' => 'required|string|in:pending,complete,fail,dp',
            'paymentType' => 'sometimes|string|in:reguler,membership',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation->paymentStatus = $request->paymentStatus;

        if ($request->has('paymentType')) {
            $reservation->paymentType = $request->paymentType;
        }

        $reservation->save();

        // Ubah status time menjadi 'available' jika dibatalkan
        if ($request->paymentStatus === 'fail') {
            foreach ($reservation->details as $detail) {
                $time = Time::find($detail->timeId);
                if ($time) {
                    $time->status = 'available';
                    $time->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran reservasi berhasil diperbarui',
            'reservation' => $reservation
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
            $sportFilter = $request->query('sport');

            $locations = DB::table('locations')
                ->select(
                    'locations.locationId',
                    'locations.locationName',
                    'locations.locationPath as imageUrl',
                    'locations.address',
                    DB::raw('MIN(times.price) as minPrice')
                )
                ->leftJoin('fields', 'locations.locationId', '=', 'fields.locationId')
                ->leftJoin('times', 'fields.fieldId', '=', 'times.fieldId')
                ->whereNull('locations.deleted_at')
                ->whereNull('fields.deleted_at')
                ->groupBy(
                    'locations.locationId',
                    'locations.locationName',
                    'locations.locationPath',
                    'locations.address'
                );

            if ($sportFilter) {
                $locations->join('fields as f', 'locations.locationId', '=', 'f.locationId')
                    ->join('sports', 'f.sportId', '=', 'sports.sportId')
                    ->where('sports.sportName', $sportFilter)
                    ->whereNull('f.deleted_at')
                    ->whereNull('sports.deleted_at');
            }

            $locationResults = $locations->get();

            // Format dan ambil data sport untuk setiap lokasi
            $locationsWithSports = $locationResults->map(function ($location) {
                $sportsQuery = DB::table('sports')
                    ->select('sports.sportName')
                    ->join('fields', 'sports.sportId', '=', 'fields.sportId')
                    ->where('fields.locationId', $location->locationId)
                    ->whereNull('fields.deleted_at')
                    ->whereNull('sports.deleted_at')
                    ->distinct();

                $sportNames = $sportsQuery->pluck('sportName')->toArray();
                $sportCount = count($sportNames);

                $formattedPrice = $location->minPrice
                    ? 'Rp ' . number_format($location->minPrice, 0, ',', '.')
                    : 'Rp 0';

                return [
                    'locationId' => $location->locationId,
                    'locationName' => $location->locationName,
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
                ->whereNull('deleted_at')
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
     * Ambil Semua Jadwal Reservasi di Lokasi Tertentu
     *
     * Mengambil semua jadwal reservasi di lokasi tertentu dengan filter berdasarkan olahraga.
     *
     * @urlParam locationId integer required The ID of the location. Example: 1
     */
    public function getSportsByLocation($locationId)
    {
        try {
            $sports = DB::table('fields')
                ->join('sports', 'fields.sportId', '=', 'sports.sportId')
                ->where('fields.locationId', $locationId)
                ->whereNull('sports.deleted_at')
                ->select('sports.sportId', 'sportName')
                ->distinct()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data olahraga.',
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
            $location = DB::table('locations')->where('locationId', $locationId)->first();
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addMonths(2)->format('Y-m-d');
            // $endDate = now()->addDays(6)->format('Y-m-d');

            $dates = collect();
            $current = strtotime($startDate);
            $end = strtotime($endDate);

            while ($current <= $end) {
                $dates->push(date('Y-m-d', $current));
                $current = strtotime('+1 day', $current);
            }

            $sportName = $request->query('sportName');

            $availableSports = DB::table('fields')
                ->join('sports', 'fields.sportId', '=', 'sports.sportId')
                ->where('fields.locationId', $locationId)
                ->select('sports.sportId', 'sportName')
                ->distinct()
                ->get();

            $matchedSport = $availableSports->first(function ($sport) use ($sportName) {
                return strtolower($sport->sportName) === strtolower($sportName);
            });

            if (!$sportName || !$matchedSport) {
                return response()->json([
                    'success' => true,
                    'locationId' => $locationId,
                    'location' => $location ? [
                        'name' => $location->locationName,
                        'imagePath' => $location->locationPath,
                    ] : null,
                    'available_sports' => $availableSports->pluck('sportName'),
                    'fields' => [],
                ]);
            }

            $sportId = $matchedSport->sportId;
            $fields = DB::table('fields')
                ->where('locationId', $locationId)
                ->where('sportId', $sportId)
                ->get();
            $existingReservations = DB::table('reservation_details')
                ->whereIn('fieldId', $fields->pluck('fieldId'))
                ->whereIn('date', $dates)
                ->get()
                ->groupBy(['date', 'fieldId', 'timeId']);

            $scheduleData = [];
            $currentTime = now('Asia/Jakarta');
            $currentHour = $currentTime->format('H:i:s');

            foreach ($fields as $field) {
                $timeSlots = DB::table('times')
                    ->where('fieldId', $field->fieldId)
                    ->where('status', 'available')
                    ->orderBy('time')
                    ->get();

                $dailySchedules = [];

                foreach ($dates as $date) {
                    $isToday = ($date == $startDate);

                    $filteredSlots = $timeSlots->filter(function ($slot) use ($isToday, $currentHour) {
                        if (!$isToday) {
                            return true;
                        }
                        return $slot->time > $currentHour;
                    });

                    $slots = $filteredSlots->map(function ($slot) use ($field, $date, $existingReservations) {

                        $isBooked = false;

                        if (
                            isset($existingReservations[$date]) &&
                            isset($existingReservations[$date][$field->fieldId]) &&
                            isset($existingReservations[$date][$field->fieldId][$slot->timeId])
                        ) {

                            $reservationData = $existingReservations[$date][$field->fieldId][$slot->timeId];
                            $isBooked = $reservationData->isNotEmpty();
                        }

                        return [
                            'timeId' => $slot->timeId,
                            'time' => $slot->time,
                            'price' => 'Rp ' . number_format($slot->price, 0, ',', '.'),
                            'status' => $slot->status,
                            'isBooked' => $isBooked,
                        ];
                    });

                    $dailySchedules[] = [
                        'date' => $date,
                        'schedules' => $slots->values(),
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
                'location' => $location ? [
                    'name' => $location->locationName,
                    'imagePath' => $location->locationPath,
                ] : null,
                'sportName' => $sportName,
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

    public function confirmPayment(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservasi tidak ditemukan'], 404);
        }

        if ($reservation->paymentStatus !== 'dp') {
            return response()->json(['message' => 'Reservasi tidak dalam status DP'], 400);
        }

        // Simulasikan pembayaran sisa (lunas)
        $payment = Payment::create([
            'reservationId' => $reservation->reservationId,
            'invoiceDate' => now(),
            'totalPaid' => $reservation->total / 2,
        ]);

        // Update status pembayaran di reservasi
        $reservation->paymentStatus = 'complete';
        $reservation->save();

        return response()->json([
            'message' => 'Pembayaran berhasil dikonfirmasi',
            'reservation' => $reservation,
            'payment' => $payment
        ]);
    }
    /**
     * Ambil Harga Minimum dan Maksimum Berdasarkan LokasiId
     *
     * Mengambil harga minimum dan maksimum dari tabel times untuk setiap lokasi.
     *
     * @param int $locationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPriceByLocation($locationId)
    {
        try {
            $query = DB::table('fields')
                ->join('times', 'fields.fieldId', '=', 'times.fieldId')
                ->where('fields.locationId', $locationId);

            $minPrice = (clone $query)->min('times.price');
            $maxPrice = (clone $query)->max('times.price');

            if ($minPrice === null || $maxPrice === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada harga ditemukan untuk lokasi ini.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'locationId' => $locationId,
                'minPrice' => 'Rp ' . number_format($minPrice, 0, ',', '.'),
                'maxPrice' => 'Rp ' . number_format($maxPrice, 0, ',', '.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil harga.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /* Ambil Harga Minimum berdasarkan Lokasi dan Sport
    *
    * Mengambil harga minimum dari times untuk setiap lokasi.
    *
    * @return \Illuminate\Http\JsonResponse
    */
    public function getMinPriceByLocationSport(Request $request)
    {
        $locationId = $request->input('locationId');
        $sportName = $request->input('sportName');

        if (!$locationId || !$sportName) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter locationId dan sportName wajib diisi.'
            ], 400);
        }

        try {
            $minPrice = DB::table('fields')
                ->join('times', 'fields.fieldId', '=', 'times.fieldId')
                ->join('sports', 'fields.sportId', '=', 'sports.sportId')
                ->where('fields.locationId', $locationId)
                ->where('sports.sportName', $sportName)
                ->min('times.price');

            if ($minPrice === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada harga ditemukan untuk lokasi dan olahraga ini.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'locationId' => $locationId,
                'sportName' => $sportName,
                'minPrice' => $minPrice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil harga minimum.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function transferToHistory($reservation)
    {
        $user = $reservation->user;
        $detail = $reservation->details->first();

        $now = now('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        // Ambil semua slot waktu
        $sortedTimes = $reservation->details->sortBy('time.time');
        $startTime = $sortedTimes->first()->time->time;
        $lastTime = $sortedTimes->last()->time->time;
        $endTime = date('H:i:s', strtotime($lastTime . ' +1 hour'));

        $reservationDate = $detail->date;

        if ($reservationDate == $today) {
            if ($currentTime < $startTime) {
                $status = 'Upcoming';
            } elseif ($currentTime >= $startTime && $currentTime < $endTime) {
                $status = 'Ongoing';
            } else {
                $status = 'Completed';
            }
        } elseif ($reservationDate < $today) {
            $status = 'Completed';
        } else {
            $status = 'Upcoming';
        }

        $totalAmount = $reservation->details->sum(fn($d) => $d->time->price ?? 0);
        $totalPaid = $reservation->payment->totalPaid ?? 0;
        $remainingAmount = $totalAmount - $totalPaid;
        $courtName = explode(' - ', $detail->field->name ?? '');

        $paymentStatus = 'DP';
        if ($totalPaid >= $totalAmount || $status == 'Completed') {
            $paymentStatus = 'Lunas';
        }

        $detailsArray = $reservation->details->map(function ($d) {
            return [
                "locationName" => $d->field->location->locationName ?? null,
                "sportName" => $d->field->sport->sportName ?? null,
                "time" => date('H:i', strtotime($d->time->time)) . ' - ' . date('H:i', strtotime($d->time->time . ' +1 hour')),
                "lapangan" => $d->field->name ?? null,
                "price" => $d->time->price ?? 0
            ];
        })->toArray();

        return HistoryReservationUser::create([
            'reservationId' => $reservation->reservationId,
            'userId' => $reservation->userId,
            'bookingName' => $reservation->name,
            'cabang' => $detail->field->location->locationName ?? null,
            'lapangan' => (count($courtName) >= 3) ? $courtName[2] . ' - ' . $courtName[0] : $detail->field->name,
            'paymentStatus' => $paymentStatus,
            'paymentType' => $reservation->paymentType,
            'reservationStatus' => $status,
            'totalAmount' => $totalAmount,
            'totalPaid' => $totalPaid,
            'remainingAmount' => $remainingAmount,
            'reservationDate' => $detail->date,
            'details' => $detailsArray
        ]);
    }

    public function userReservations(Request $request)
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json(['success' => false, 'message' => 'Authorization header tidak valid'], 401);
            }

            $token = substr($authHeader, 7);
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return response()->json(['success' => false, 'message' => 'Format token tidak valid'], 400);
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (!$payload || !isset($payload['user_id'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized Access'], 400);
            }

            $userId = $payload['user_id'];
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized Access'], 403);
            }

            // Sync data dari reservations ke history
            $reservations = Reservation::with([
                'details.field.location',
                'details.field.sport',
                'details.time',
                'user',
                'payment'
            ])->where('userId', $userId)->get();

            foreach ($reservations as $reservation) {
                $existingHistory = HistoryReservationUser::where('reservationId', $reservation->reservationId)->first();
                if (!$existingHistory) {
                    $this->transferToHistory($reservation);
                }
            }

            $historyReservations = HistoryReservationUser::where('userId', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $user = User::find($userId);

            $formattedReservations = $historyReservations->map(function ($history) use ($reservations) {
                $now = \Carbon\Carbon::now(config('app.timezone', 'Asia/Jakarta'));

                // fallback manual kalau config tetap UTC:
                if (config('app.timezone') === 'UTC') {
                    $now->addHours(7);
                }
                
                $today = $now->format('Y-m-d');
                $currentTime = $now->format('H:i:s');

                $reservationDate = $history->reservationDate->format('Y-m-d');
                $reservationStatus = $history->reservationStatus;
                $paymentStatusDisplay = $history->paymentStatus;

                $startTime = '00:00:00';
                $endTime = '00:00:00';

                $matchingReservation = $reservations->firstWhere('reservationId', $history->reservationId);
                if ($matchingReservation && $matchingReservation->details->isNotEmpty()) {
                    $sortedTimes = $matchingReservation->details->sortBy('time.time');
                    $startTime = $sortedTimes->first()->time->time;
                    $lastTime = $sortedTimes->last()->time->time;
                    $endTime = date('H:i:s', strtotime($lastTime . ' +1 hour'));
                }

                // Perhitungan status berdasarkan waktu
                if ($reservationDate == $today) {
                    if ($currentTime < $startTime) {
                        $reservationStatus = 'Upcoming';
                    } elseif ($currentTime >= $startTime && $currentTime < $endTime) {
                        $reservationStatus = 'Ongoing';
                    } else {
                        $reservationStatus = 'Completed';
                    }
                } elseif ($reservationDate < $today) {
                    $reservationStatus = 'Completed';
                } else {
                    $reservationStatus = 'Upcoming';
                }

                // Override kondisi khusus
                if ($history->paymentStatus === 'canceled') {
                    $reservationStatus = 'canceled';
                } elseif ($history->paymentStatus === 'waiting') {
                    $reservationStatus = 'waiting';
                } elseif ($history->paymentStatus === 'refund') {
                    $reservationStatus = 'Refund ( Rp. ' . number_format($history->refundAmount, 0, ',', '.') . ' )';
                }

                // Format payment status tampilannya
                if ($history->paymentStatus === 'DP') {
                    $paymentStatusDisplay = 'DP ( Rp. ' . number_format($history->remainingAmount, 0, ',', '.') . ' )';
                }

                return [
                    "historyId" => $history->historyId,
                    "reservationId" => $history->reservationId,
                    "bookingName" => $history->bookingName,
                    "cabang" => $history->cabang,
                    "lapangan" => $history->lapangan,
                    "paymentStatus" => $paymentStatusDisplay,
                    "paymentType" => $history->paymentType,
                    "reservationStatus" => $reservationStatus,
                    "totalAmount" => number_format($history->totalAmount, 0, ',', '.'),
                    "totalPaid" => number_format($history->totalPaid, 0, ',', '.'),
                    "remainingAmount" => number_format($history->remainingAmount, 0, ',', '.'),
                    "date" => $reservationDate,
                    "timeStart" => $startTime,
                    "timeEnd" => $endTime,
                    "details" => $history->details,
                    "bankName" => $history->bankName,
                    "accountName" => $history->accountName,
                    "accountNumber" => $history->accountNumber,
                    "created_at" => $history->created_at,
                    "updated_at" => $history->updated_at
                ];
            });

            $data = [
                'UserName' => $user->name ?? null,
                'whatsapp' => 'wa.me/+62' . ltrim($user->phone ?? '', '0'),
                'email' => $user->email ?? null,
                'count' => $formattedReservations->count(),
                'reservations' => $formattedReservations
            ];

            return response()->json([
                'success' => true,
                'message' => 'Reservasi berhasil diambil',
                'data' => $data
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau tidak ditemukan',
                'error' => $e->getMessage(),
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Cancel reservasi dari history_reservation_user
     * 
     * @bodyParam bankName string optional Nama bank untuk refund (required jika paymentStatus = Lunas). Example: "BCA"
     * @bodyParam accountName string optional Nama pemilik rekening (required jika paymentStatus = Lunas). Example: "John Doe"
     * @bodyParam accountNumber string optional Nomor rekening (required jika paymentStatus = Lunas). Example: "1234567890"
     * @bodyParam cancelReason string optional Alasan pembatalan. Example: "Berhalangan hadir"
     */
    public function cancelStatusReservation(Request $request, $id)
    {
        try {
            // $payload = JWTAuth::parseToken()->getPayload();
            // $userId = $payload->get('user_id');
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json(['success' => false, 'message' => 'Authorization header tidak valid'], 401);
            }

            $token = substr($authHeader, 7); // Ambil string setelah "Bearer "
            // Pisahkan JWT menjadi bagian-bagian: header.payload.signature
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return response()->json(['success' => false, 'message' => 'Format token tidak valid'], 400);
            }

            // Decode payload dari base64
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (!$payload || !isset($payload['user_id'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized Access'], 400);
            }

            $userId = $payload['user_id'];
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized Access'
                ], 403);
            }

            // Cari history reservation berdasarkan historyId
            $historyReservation = HistoryReservationUser::where('historyId', $id)
                ->where('userId', $userId)
                ->first();

            if (!$historyReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservasi tidak ditemukan'
                ], 404);
            }

            // Cek apakah reservasi sudah selesai
            if ($historyReservation->reservationStatus === 'Completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservasi yang sudah selesai tidak dapat dibatalkan'
                ], 400);
            }

            // Cek apakah sudah dalam status canceled atau waiting
            if (in_array($historyReservation->paymentStatus, ['canceled', 'waiting'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservasi sudah dalam proses pembatalan'
                ], 400);
            } else if (in_array($historyReservation->paymentStatus, ['refund'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund sudah dilakukan'
                ], 400);
            }


            // Logika pembatalan berdasarkan paymentStatus
            if ($historyReservation->paymentStatus !== 'Lunas' && $historyReservation->reservationStatus !== 'Completed') {
                // Case 1: paymentStatus bukan Lunas dan reservationStatus bukan Completed
                // Langsung rubah paymentStatus menjadi canceled
                $historyReservation->update([
                    'paymentStatus' => 'canceled',
                    'reservationStatus' => 'canceled',
                    'remainingAmount' => 0,                  
                    'cancelReason' => $request->input('cancelReason', 'Dibatalkan oleh user')
                ]);

                // Update status times menjadi available
                $this->updateTimeAvailability($historyReservation->reservationId, 'available');

                $message = 'Reservasi berhasil dibatalkan';
                
            } elseif ($historyReservation->paymentStatus === 'Lunas') {
                // Case 2: paymentStatus === Lunas
                // Validasi input untuk refund
                $validator = Validator::make($request->all(), [
                    'bankName' => 'required|string|max:255',
                    'accountName' => 'required|string|max:255',
                    'accountNumber' => 'required|string|max:50',
                    'cancelReason' => 'sometimes|string|max:500'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data refund tidak lengkap',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                // Update dengan data refund dan status waiting
                $historyReservation->update([
                    // Update reservation status di tabel utama juga
                    'paymentStatus' => 'waiting',
                    'bankName' => $request->bankName,
                    'accountName' => $request->accountName,
                    'accountNumber' => $request->accountNumber,
                    'cancelReason' => $request->input('cancelReason', 'Dibatalkan oleh user - menunggu refund')
                ]);

                $message = 'Permintaan pembatalan berhasil dikirim. Refund akan diproses dalam 1-3 hari kerja';
            }

            // Update reservation status di tabel utama jika perlu
            $originalReservation = Reservation::find($historyReservation->reservationId);
            if ($originalReservation) {
                $originalReservation->update([
                    'paymentStatus' => $historyReservation->paymentStatus === 'canceled' ? 'canceled' : $originalReservation->paymentStatus,
                    'reservationStatus' => $historyReservation->paymentStatus === 'canceled' ? 'Waiting' : $originalReservation->reservationStatus
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'historyId' => $historyReservation->historyId,
                    'reservationId' => $historyReservation->reservationId,
                    'paymentStatus' => $historyReservation->paymentStatus,
                    'bankName' => $historyReservation->bankName,
                    'accountName' => $historyReservation->accountName,
                    'accountNumber' => $historyReservation->accountNumber,
                    'cancelReason' => $historyReservation->cancelReason,
                ]
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau tidak ditemukan',
                'error' => $e->getMessage(),
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membatalkan reservasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method untuk update status time availability
     */
    private function updateTimeAvailability($reservationId, $status)
    {
        try {
            $reservationDetails = ReservationDetail::where('reservationId', $reservationId)->get();
            
            foreach ($reservationDetails as $detail) {
                $time = Time::find($detail->timeId);
                if ($time) {
                    $time->status = $status;
                    $time->save();
                }
            }
        } catch (\Exception $e) {
            // Log error tapi jangan stop proses
            // \Log::error('Error updating time availability: ' . $e->getMessage());
        }
    }
            

    /**
     * Tampilkan Semua Permintaan Refund
     *
     * Mengambil semua reservasi yang memiliki status waiting (menunggu refund).
     *
     * @queryParam page integer optional Halaman yang diminta. Example: 1
     * @queryParam limit integer optional Jumlah data per halaman. Example: 10
     * @queryParam search string optional Pencarian berdasarkan nama booking. Example: "Booking 1"
     * @queryParam locationId integer optional Filter berdasarkan lokasi. Example: 1
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRefundRequests(Request $request)
    {
        try {
            $page = (int) $request->input('page', 1);
            $limit = (int) $request->input('limit', 10);
            $search = $request->input('search');
            $locationId = $request->input('locationId');

            $query = HistoryReservationUser::with('user')
                ->where('paymentStatus', 'waiting')
                ->orWhere('paymentStatus', 'canceled')
                ->orWhere('paymentStatus', 'refund')
                ->orWhere('paymentStatus', 'rejected')
                ->when($search, function ($query) use ($search) {
                    $query->where('bookingName', 'like', '%' . $search . '%');
                })
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where('cabang', function ($q) use ($locationId) {
                        $location = DB::table('locations')->where('locationId', $locationId)->first();
                        if ($location) {
                            return $q->where('cabang', $location->locationName);
                        }
                    });
                })
                ->orderBy('created_at', 'desc');

            $refundRequests = $query->paginate($limit, ['*'], 'page', $page);

            $formattedData = $refundRequests->getCollection()->map(function ($history) {
                
                // Hitung refund amount
                $calculatedRefundAmount = $history->reservationStatus === 'canceled' ?0 : $history->totalAmount || 0 - $history->totalPaid || 0;
                if ($history->refundAmount !== null) {
                    $calculatedRefundAmount = $history->refundAmount;
                }

                // Update refundAmount di database
                $history->refundAmount = $calculatedRefundAmount;
                $history->save(); // simpan perubahan ke database
              
                return [
                    'historyId' => $history->historyId,
                    'reservationId' => $history->reservationId,
                    'bookingName' => $history->bookingName,
                    'userName' => $history->user->name ?? 'Unknown',
                    'userEmail' => $history->user->email ?? '',
                    'userPhone' => 'wa.me/+62' . ltrim($history->user->phone ?? '', '0'),
                    'cabang' => $history->cabang,
                    'lapangan' => $history->lapangan,
                    'reservationDate' => $history->reservationDate->format('Y-m-d'),
                    'paymentStatus' => $history->paymentStatus,
                    'reservationStatus' => $history->reservationStatus,
                    'totalAmount' => $history->totalAmount,
                    'totalPaid' => $history->totalPaid,
                    'refundAmount' => $calculatedRefundAmount , // Jumlah yang akan di-refund
                    'bankName' => $history->bankName,
                    'accountName' => $history->accountName,
                    'accountNumber' => $history->accountNumber,
                    'cancelReason' => $history->cancelReason,
                    'requestDate' => $history->updated_at->format('Y-m-d H:i:s'),
                    'details' => $history->details
                ];
            });

            // \Log::info('locationId: ' . $locationId);
            // \Log::info('search: ' . $search);

            

            return response()->json([
                'success' => true,
                'message' => 'Daftar permintaan refund berhasil diambil',
                'data' => [
                    'current_page' => $refundRequests->currentPage(),
                    'data' => $formattedData,
                    'first_page_url' => $refundRequests->url(1),
                    'from' => $refundRequests->firstItem(),
                    'last_page' => $refundRequests->lastPage(),
                    'last_page_url' => $refundRequests->url($refundRequests->lastPage()),
                    'next_page_url' => $refundRequests->nextPageUrl(),
                    'path' => $refundRequests->url($refundRequests->currentPage()),
                    'per_page' => $refundRequests->perPage(),
                    'prev_page_url' => $refundRequests->previousPageUrl(),
                    'to' => $refundRequests->lastItem(),
                    'total' => $refundRequests->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data permintaan refund',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Konfirmasi Refund oleh Admin
     *
     * Admin mengonfirmasi bahwa refund sudah diproses dan mengubah status menjadi refunded.
     *
     * @urlParam historyId integer required ID dari history reservation. Example: 1
     * @bodyParam adminNote string optional Catatan admin tentang proses refund. Example: "Refund telah diproses ke rekening BCA"
     * @bodyParam refundAmount numeric optional Jumlah yang di-refund (default: totalPaid). Example: 150000
     *
     * @response {
     *   "success": true,
     *   "message": "Refund berhasil dikonfirmasi",
     *   "data": {
     *     "historyId": 1,
     *     "reservationId": 1,
     *     "bookingName": "Booking 1",
     *     "paymentStatus": "refunded",
     *     "refundAmount": 150000,
     *     "adminNote": "Refund telah diproses ke rekening BCA",
     *     "processedAt": "2025-06-08 15:30:00"
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Permintaan refund tidak ditemukan"
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Permintaan refund tidak dalam status waiting"
     * }
     */
    
    public function confirmRefund(Request $request, $historyId)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'adminNote' => 'sometimes|string|max:500',
                'refundAmount' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ambil history dan relasi user (hindari null saat akses user)
            $historyReservation = HistoryReservationUser::with('user')->find($historyId);

            if (!$historyReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan refund tidak ditemukan'
                ], 404);
            }

            // Cek status pembayaran

            if ($historyReservation->paymentStatus === 'refund') {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan refund sudah dikonfirmasi'
                ], 400);
            }

            if ($historyReservation->paymentStatus !== 'waiting') {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan refund tidak dalam status waiting'
                ], 400);
            }

            // Tambahkan di sini:
            if ($request->filled('refundAmount')) {
                $refundAmount = $request->input('refundAmount');

                if ($refundAmount > $historyReservation->totalPaid) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Jumlah refund tidak boleh lebih besar dari total yang dibayarkan (' . $historyReservation->totalPaid . ')'
                    ], 422);
                }
            }

            // Ambil refund amount dari input (atau pakai nilai default)
            // $refundAmount = $request->input('refundAmount', $historyReservation->refundAmount);
            $refundAmount = $request->filled('refundAmount')
            ? $request->input('refundAmount')
            : $historyReservation->totalPaid;

            // Update data history
            $historyReservation->paymentStatus = 'refund';
            $historyReservation->reservationStatus = 'refund';
            $historyReservation->adminNote = $request->input('adminNote', 'Refund telah diproses oleh admin');
            $historyReservation->refundAmount = $refundAmount;
            $historyReservation->processedAt = now();
            $historyReservation->save();

            // Update time slot availability
            $this->updateTimeAvailability($historyReservation->reservationId, 'available');

            // Update status di tabel Reservation
            $originalReservation = Reservation::find($historyReservation->reservationId);
            if ($originalReservation) {
                $originalReservation->paymentStatus = 'refund';
                $originalReservation->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Refund berhasil dikonfirmasi',
                'data' => [
                    'historyId' => $historyReservation->historyId,
                    'reservationId' => $historyReservation->reservationId,
                    'bookingName' => $historyReservation->bookingName,
                    'userName' => $historyReservation->user->name ?? 'Unknown',
                    'paymentStatus' => $historyReservation->paymentStatus,
                    'refundAmount' => $refundAmount,
                    'bankName' => $historyReservation->bankName,
                    'accountName' => $historyReservation->accountName,
                    'accountNumber' => $historyReservation->accountNumber,
                    'adminNote' => $historyReservation->adminNote,
                    'processedAt' => optional($historyReservation->processedAt)->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            // \Log::error('Error saat konfirmasi refund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengonfirmasi refund',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tolak Permintaan Refund
     *
     * Admin menolak permintaan refund dengan alasan tertentu.
     *
     * @urlParam historyId integer required ID dari history reservation. Example: 1
     * @bodyParam rejectReason string required Alasan penolakan refund. Example: "Tidak memenuhi syarat refund"
     *
     * @response {
     *   "success": true,
     *   "message": "Permintaan refund berhasil ditolak",
     *   "data": {
     *     "historyId": 1,
     *     "reservationId": 1,
     *     "paymentStatus": "rejected",
     *     "rejectReason": "Tidak memenuhi syarat refund"
     *   }
     * }
     */
    public function rejectRefund(Request $request, $historyId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rejectReason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alasan penolakan harus diisi',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Load with user relation to avoid null pointer
            $historyReservation = HistoryReservationUser::with('user')->find($historyId);

            if (!$historyReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan refund tidak ditemukan'
                ], 404);
            }

            if ($historyReservation->paymentStatus !== 'waiting') {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan refund tidak dalam status waiting'
                ], 400);
            }

            // Update status menjadi rejected
            $updateData = [
                'paymentStatus' => 'rejected',
                'reservationStatus' => 'rejected',
                'rejectReason' => $request->rejectReason,
                'processedAt' => now()
            ];

            $historyReservation->update($updateData);

            // Format response data
            $responseData = [
                'historyId' => $historyReservation->historyId,
                'reservationId' => $historyReservation->reservationId,
                'bookingName' => $historyReservation->bookingName,
                'paymentStatus' => $historyReservation->paymentStatus,
                'reservationStatus' => $historyReservation->reservationStatus,
                'rejectReason' => $historyReservation->rejectReason,
            ];

            // Add processedAt if not null
            if ($historyReservation->processedAt) {
                $responseData['processedAt'] = $historyReservation->processedAt->format('Y-m-d H:i:s');
            }

            return response()->json([
                'success' => true,
                'message' => 'Permintaan refund berhasil ditolak',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            // \Log::error('Error rejecting refund: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menolak refund',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail Permintaan Refund
     *
     * Mengambil detail lengkap permintaan refund berdasarkan historyId.
     *
     * @urlParam historyId integer required ID dari history reservation. Example: 1
     */
    public function getRefundDetail($historyId)
    {
        try {
            $historyReservation = HistoryReservationUser::with('user')->find($historyId);

            if (!$historyReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan refund tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail permintaan refund berhasil diambil',
                'data' => [
                    'historyId' => $historyReservation->historyId,
                    'reservationId' => $historyReservation->reservationId,
                    'bookingName' => $historyReservation->bookingName,
                    'user' => [
                        'name' => $historyReservation->user->name ?? 'Unknown',
                        'email' => $historyReservation->user->email ?? '',
                        'phone' => $historyReservation->user->phone ?? '',
                    ],
                    'cabang' => $historyReservation->cabang,
                    'lapangan' => $historyReservation->lapangan,
                    'reservationDate' => $historyReservation->reservationDate->format('Y-m-d'),
                    'paymentStatus' => $historyReservation->paymentStatus,
                    'totalAmount' => $historyReservation->totalAmount,
                    'totalPaid' => $historyReservation->totalPaid,
                    'refundAmount' => $historyReservation->refundAmount ?? $historyReservation->totalPaid,
                    'bankInfo' => [
                        'bankName' => $historyReservation->bankName,
                        'accountName' => $historyReservation->accountName,
                        'accountNumber' => $historyReservation->accountNumber,
                    ],
                    'cancelReason' => $historyReservation->cancelReason,
                    'adminNote' => $historyReservation->adminNote,
                    'rejectReason' => $historyReservation->rejectReason,
                    'requestDate' => $historyReservation->updated_at->format('Y-m-d H:i:s'),
                    'processedAt' => $historyReservation->processedAt?->format('Y-m-d H:i:s'),
                    'details' => $historyReservation->details
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil detail refund',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
