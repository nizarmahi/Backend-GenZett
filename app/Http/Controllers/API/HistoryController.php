<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
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
            $res = $reservation->toArray();
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
