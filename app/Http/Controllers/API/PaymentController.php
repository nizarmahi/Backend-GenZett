<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Tampilkan semua data pembayaran
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);

        $query = Payment::query();
        $totalPayments = $query->count();
        $offset = ($page - 1) * $limit;

        $payments = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Data pembayaran berhasil diambil',
            'totalPayments' => $totalPayments,
            'offset' => $offset,
            'limit' => $limit,
            'payments' => $payments
        ]);
    }

    /**
     * Simpan data pembayaran baru
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_detail_id' => 'required|exists:reservation_details,id',
            'payment_method' => 'required|string|max:255',
            'payment_proof' => 'nullable|string|max:255',
            'dp_price' => 'required|numeric|min:0',
            'full_price' => 'required|numeric|min:0',
            'status' => 'required|in:pending,confirmed,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment = Payment::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dibuat',
            'payment' => $payment
        ], 201);
    }

    /**
     * Tampilkan detail pembayaran
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $payment = Payment::with('reservationDetail')->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => "Pembayaran dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => "Detail pembayaran dengan ID {$id}",
            'payment' => $payment
        ]);
    }

    /**
     * Perbarui data pembayaran
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => "Pembayaran dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'sometimes|required|string|max:255',
            'payment_proof' => 'nullable|string|max:255',
            'dp_price' => 'sometimes|required|numeric|min:0',
            'full_price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:pending,confirmed,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil diperbarui',
            'payment' => $payment
        ]);
    }

    /**
     * Hapus pembayaran
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => "Pembayaran dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dihapus'
        ]);
    }
}
