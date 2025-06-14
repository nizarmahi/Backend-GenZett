<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;

class PaymentController extends Controller
{
    protected $invoiceApi;

    public function __construct()
    {
        $apiKey = config('services.xendit.api_key');
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey($apiKey);

        $this->invoiceApi = new InvoiceApi(null, $config);
    }

    /**
     * Tampilkan semua Payment
     *
     * @param \Illuminate\Http\Request $request
     *
     */
    public function index(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);

        $query = Payment::with('reservation');
        $total = $query->count();
        $offset = ($page - 1) * $limit;

        $payments = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pembayaran',
            'total' => $total,
            'payments' => $payments
        ]);
    }
    /**
     * Simpan data Payment baru
     *
     * @param \Illuminate\Http\Request $request
     *
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservationId' => 'required|exists:reservations,reservationId',
            'totalPaid' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $id = $request->reservationId;
        $reservation = Reservation::find($id);
        $reservationTotal = $reservation->total;
        $totalPaid = $request->totalPaid;

        if ($reservationTotal != $totalPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Total yang diberikan tidak sama',
                'total reservation' => $reservationTotal,
                'total bayar' => $totalPaid
            ]);
        }

        try {
            $external_id = 'invoice-' . uniqid();
            $createInvoiceRequest = [
                'external_id' => $external_id,
                'payer_email' => 'user@email.com', // ubah sesuai kebutuhan
                'description' => 'Pembayaran reservasi',
                'amount' => $request->totalPaid,
                'payment_methods' => ['QRIS'],
                'success_redirect_url' => 'https://resports.web.id/history',
                // 'success_redirect_url' => 'http://localhost:3000/history',
                'failure_redirect_url' => 'https://resports.web.id/reservation',
                'invoice_duration' => 900

            ];

            $xenditInvoice = $this->invoiceApi->createInvoice($createInvoiceRequest);

            $payment = Payment::create([
                'reservationId' => $request->reservationId,
                'invoiceDate' => now(),
                'totalPaid' => $request->totalPaid,
                'xendit_invoice_id' => $xenditInvoice->getId(),
                'xendit_invoice_url' => $xenditInvoice->getInvoiceUrl(),
                'xendit_status' => $xenditInvoice->getStatus(),
                'expiry_date' => $xenditInvoice->getExpiryDate(),
                'payment_methods' => ['QRIS'],
                'success_redirect_url' => 'https://resports.web.id/history',
                // 'success_redirect_url' => 'http://localhost:3000/history',
                'failure_redirect_url' => 'https://resports.web.id/reservation',
                'invoice_duration' => 900
            ]);

            // Logging sebelum request
            // Log::info('Mengirim data ke POS:', [
            //     'endpoint' => 'http://20.189.122.35/api/transaction',
            //     'payload' => [
            //         'user_id' => 1,
            //         'branch_id' => 1,
            //         'category_id' => 1,
            //         'amount' => $totalPaid,
            //         'description' => 'Pemasukan dari reservasi ID #' . $id,
            //         'transaction_date' => now()->format('Y-m-d')
            //     ]
            // ]);

            // $response = Http::post('http://20.189.122.35/api/pos/transaction', [
            //     'user_id' => 21,
            //     'branch_id' => 4,
            //     'category_id' => 3,
            //     'amount' => $totalPaid,
            //     'description' => 'Pemasukan dari reservasi ID #' . $id,
            //     'transaction_date' => now()->format('Y-m-d')
            // ]);

            // Logging setelah request
            // Log::info('Respons dari POS:', [
            //     'status' => $response->status(),
            //     'body' => $response->body()
            // ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dibuat',
                'payment' => $payment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saat membuat pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan detail Payment
     *
     * @param int $id
     *
     */

    public function show($id)
    {
        $payment = Payment::with('reservation')->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => "Pembayaran dengan ID {$id} tidak ditemukan"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Detail pembayaran",
            'payment' => $payment
        ]);
    }
    /**
     * Perbarui data Payment
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
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
            'totalPaid' => 'sometimes|required|numeric|min:0',
            'xendit_status' => 'sometimes|required|string',
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
     * Hapus data Payment
     *
     * @param int $id
     *
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
    /**
     * Webhook
     *
     * @param \Illuminate\Http\Request $request
     */
    public function handleWebhook(Request $request)
    {
        $invoiceId = $request->input('id');
        $invoice = $this->invoiceApi->getInvoiceById($invoiceId);

        $payment = Payment::where('xendit_invoice_id', $invoice->getId())->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        // Validasi status invoice apakah sudah SETTLED
        if ($invoice->getStatus() == 'SETTLED') {
            return response()->json(['message' => 'Invoice already settled'], 400);
        }
        $payment->update([
            'xendit_status' => $invoice->getStatus(),
            'expiry_date' => $invoice->getExpiryDate()
        ]);
        // Jika invoice sudah dibayar, update juga status di tabel reservation
        if ($invoice->getStatus() === 'PAID') {
            if ($payment->reservation->paymentStatus !== 'dp') {
                $payment->reservation->update([
                    'paymentStatus' => 'complete'
                ]);
            }
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
