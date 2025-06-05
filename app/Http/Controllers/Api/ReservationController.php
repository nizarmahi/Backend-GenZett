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
            'paymentStatus' => 'sometimes|string|in:pending,paid,cancelled',
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
            // Ambil filter dari query string jika ada (misalnya: ?sport=Futsal)
            $sportFilter = $request->query('sport');

            // Ambil data lokasi dengan harga minimum dari times
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
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addDays(6)->format('Y-m-d');

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

    public function userReservations(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter user_id wajib diisi.',
                'data' => []
            ], 400);
        }

        // Ambil semua reservasi dengan relasi user dan details
        $reservations = Reservation::with(['details', 'user'])
            ->where('userId', $userId)
            ->get();

        // Ambil 1 data user dari salah satu reservasi (karena user-nya pasti sama)
        $user = $reservations->first()?->user;

        // Hilangkan properti 'user' dari setiap item dalam data
        $cleanedReservations = $reservations->map(function ($reservation) {
            $res = $reservation->totoArray();
            unset($res['user']);
            return $res;
        });

        return response()->json([
            'success' => true,
            'message' => 'User reservations retrieved successfully',
            'user' => $user,
            'data' => $cleanedReservations
        ]);
    }
}
