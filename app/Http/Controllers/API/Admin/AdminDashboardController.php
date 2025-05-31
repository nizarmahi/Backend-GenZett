<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function getDashboardAdmin($locationId)
    {
        $today = Carbon::today();
        $threeMonthsAgo = $today->copy()->subMonths(3)->startOfDay();
        $startOfMonth = $today->copy()->startOfMonth();

        // 1. Total Lapangan
        $totalFields = DB::table('fields')
            ->where('locationId', $locationId)
            ->count();

        // 2. Total Paket Langganan
        $totalMemberships = DB::table('memberships')
            ->where('locationId', $locationId)
            ->count();

        // 3. Total Pesanan Paket Langganan Bulan Ini
        $totalMembershipOrders = DB::table('reservation_members')
            ->join('memberships', 'reservation_members.membershipId', '=', 'memberships.membershipId')
            ->where('memberships.locationId', $locationId)
            ->whereBetween('reservation_members.created_at', [$startOfMonth, $today])
            ->count();

        // 4. Grafik Reservasi per Hari (3 bulan terakhir) di cabang ini
        $dailyReservations = DB::select("
            SELECT
                DATE(rd.date) AS date,
                COUNT(*) AS total_reservasi
            FROM reservation_details rd
            JOIN reservations r ON rd.reservationId = r.reservationId
            JOIN fields f ON rd.fieldId = f.fieldId
            WHERE
                f.locationId = ?
                AND rd.date BETWEEN ? AND ?
            GROUP BY DATE(rd.date)
            ORDER BY DATE(rd.date) ASC
        ", [$locationId, $threeMonthsAgo->toDateString(), $today->toDateString()]);

        // Format data untuk Recharts (JS-friendly)
        $chartData = array_map(function ($item) {
            return [
                'date' => $item->date,
                'total_reservasi' => (int) $item->total_reservasi
            ];
        }, $dailyReservations);

        return response()->json([
            'total_lapangan' => $totalFields,
            'total_paket_langganan' => $totalMemberships,
            'total_pesanan_langganan_bulan_ini' => $totalMembershipOrders,
            'reservasi_per_hari' => $chartData,
        ]);
    }
}
