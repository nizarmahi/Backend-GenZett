<?php

namespace App\Http\Controllers\API\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Total totalan
        $totalLapangan = DB::selectOne("SELECT COUNT(*) as total FROM fields")->total;
        $totalCabang = DB::selectOne("SELECT COUNT(*) as total FROM locations")->total;
        $totalAdmin = DB::selectOne("SELECT COUNT(*) as total FROM admins")->total;
        $totalCabor = DB::selectOne("SELECT COUNT(*) as total FROM sports")->total;

        // Range tanggal: 3 bulan terakhir sampai hari ini
        $today = Carbon::today();
        $threeMonthsAgo = $today->copy()->subMonths(3)->startOfDay(); // tetap 3 bulan termasuk hari ini

        // Line chart: total reservasi per hari
        $dailyReservations = DB::select("
            SELECT
                DATE(reservation_details.date) AS DATE,
                COUNT(*) AS total_reservasi
            FROM
                reservation_details
            INNER JOIN reservations ON reservation_details.reservationId = reservations.reservationId
            WHERE
                reservation_details.date BETWEEN ? AND ?
            GROUP BY
                DATE(reservation_details.date)
            ORDER BY
                DATE ASC
        ", [$threeMonthsAgo->toDateString(), $today->toDateString()]);

        // Pie chart: total reservasi per cabang
        $reservasiPerCabang = DB::select("
            SELECT locations.locationName, COUNT(*) AS total_reservasi
            FROM reservation_details
            JOIN reservations ON reservation_details.reservationId = reservations.reservationId
            JOIN fields ON reservation_details.fieldId = fields.fieldId
            JOIN locations ON fields.locationId = locations.locationId
            GROUP BY locations.locationName
        ");

        return response()->json([
            'total_lapangan' => $totalLapangan,
            'total_cabang' => $totalCabang,
            'total_admin' => $totalAdmin,
            'total_cabor' => $totalCabor,
            'daily_reservations' => $dailyReservations,
            'reservasi_per_cabang' => $reservasiPerCabang,
        ]);
    }
}
