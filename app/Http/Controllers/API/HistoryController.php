<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationDetail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 5);
            $page = $request->input('page', 1);
            $sortField = $request->input('sort_field', 'paymentId');
            $sortDirection = $request->input('sort_direction', 'asc');
            $statusFilter = $request->input('status');
            $paymentFilter = $request->input('payment');

            $query = Payment::with([
                'reservation.details.field.location',
                'reservation.details.time'
            ]);

            // Filter pencarian
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('paymentId', 'like', "%$search%")
                    ->orWhereHas('reservation', function($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('reservation.details.field.location', function($q) use ($search) {
                        $q->where('locationName', 'like', "%$search%");
                    })
                    ->orWhereHas('reservation.details.field', function($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
                });
            }

            // Filter status
            if ($statusFilter) {
                $query->whereHas('reservation.details', function($q) use ($statusFilter) {
                    $this->applyStatusFilter($q, $statusFilter);
                });
            }

            // Filter pembayaran
            if ($paymentFilter) {
                $query->whereHas('reservation', function($q) use ($paymentFilter) {
                    $q->where('paymentStatus', $paymentFilter);
                });
            }

            // Sorting
            $validSortFields = ['paymentId', 'totalPaid', 'date'];
            if (in_array($sortField, $validSortFields)) {
                if ($sortField === 'date') {
                    $query->join('reservation_details', 'payments.reservationId', '=', 'reservation_details.reservationId')
                        ->orderBy('reservation_details.date', $sortDirection);
                } else {
                    $query->orderBy($sortField, $sortDirection);
                }
            }

            $payments = $query->paginate($perPage, ['*'], 'page', $page);

            // Pagination
            $payments = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform data
            $transformedData = $payments->getCollection()->map(function ($payment) {
                return $this->transformHistory($payment);
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
                'message' => 'Histories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve histories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function applyStatusFilter($query, $status)
    {
        $now = Carbon::now();
        
        if ($status === 'Upcoming') {
            $query->where('date', '>', $now->format('Y-m-d'))
                  ->orWhere(function($q) use ($now) {
                      $q->whereDate('date', $now->format('Y-m-d'))
                        ->whereHas('time', function($q) use ($now) {
                            $q->where('start_time', '>', $now->format('H:i:s'));
                        });
                  });
        } elseif ($status === 'Ongoing') {
            $query->whereDate('date', $now->format('Y-m-d'))
                  ->whereHas('time', function($q) use ($now) {
                      $q->where('start_time', '<=', $now->format('H:i:s'))
                        ->where('end_time', '>=', $now->format('H:i:s'));
                  });
        } elseif ($status === 'Completed') {
            $query->where('date', '<', $now->format('Y-m-d'))
                  ->orWhere(function($q) use ($now) {
                      $q->whereDate('date', $now->format('Y-m-d'))
                        ->whereHas('time', function($q) use ($now) {
                            $q->where('end_time', '<', $now->format('H:i:s'));
                        });
                  });
        }
    }

    private function transformHistory($payment): array
    {
        $reservation = $payment->reservation;
        $reservationDetails = $reservation->details;
        
        $fieldNames = $reservationDetails->map(function ($detail) {
            return $detail->field->name;
        })->unique()->implode(', ');

        $locationName = $reservationDetails->first()->field->location->locationName ?? 'Unknown Location';
        $date = $reservationDetails->first()->date ?? null;
        $status = $this->determineStatus($reservationDetails);

        return [
            'id' => '#' . $payment->paymentId,
            'branch' => $locationName,
            'name' => $reservation->name,
            'court' => $fieldNames,
            'date' => $date ? Carbon::parse($date)->format('d/m/Y') : null,
            'total' => 'Rp. ' . number_format($payment->totalPaid, 0, ',', '.'),
            'payment' => $reservation->paymentStatus,
            'status' => $status
        ];
    }

    private function determineStatus($reservationDetails): string
    {
        $now = Carbon::now();
        $hasUpcoming = false;
        
        foreach ($reservationDetails as $detail) {
            $date = Carbon::parse($detail->date);
            $time = $detail->time;
            
            if (!$time) continue;
            
            $startTime = Carbon::parse($time->start_time);
            $endTime = Carbon::parse($time->end_time);
            
            $startDateTime = $date->copy()->setTime($startTime->hour, $startTime->minute, $startTime->second);
            $endDateTime = $date->copy()->setTime($endTime->hour, $endTime->minute, $endTime->second);
            
            if ($now->between($startDateTime, $endDateTime)) {
                return 'Ongoing';
            }
            
            if ($now->lt($startDateTime)) {
                $hasUpcoming = true;
            }
        }
        
        return $hasUpcoming ? 'Upcoming' : 'Completed';
    }
}