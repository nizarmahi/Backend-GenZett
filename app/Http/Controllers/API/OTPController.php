<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class OTPController extends Controller
{
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Email tidak valid atau sudah terdaftar.'], 422);
        }

        $otp = rand(100000, 999999);

        // Simpan OTP di cache selama 10 menit
        Cache::put("otp_{$request->email}", $otp, now()->addMinutes(10));

        // Kirim email
        Mail::raw("Kode OTP kamu adalah: $otp", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Kode OTP Pendaftaran');
        });

        return response()->json(['message' => 'OTP berhasil dikirim ke email.']);
    }

    public function verifyOtpAndRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string',
            'dob' => 'required|date',
            'phone' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid.'], 422);
        }

        $cachedOtp = Cache::get("otp_{$request->email}");

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['message' => 'OTP salah atau sudah kedaluwarsa.'], 400);
        }

        // Simpan user
        User::create([
            'name' => $request->name,
            'username' => $request->name,
            'email' => $request->email,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        // Hapus OTP dari cache
        Cache::forget("otp_{$request->email}");

        return response()->json(['message' => 'Pendaftaran berhasil.'], 201);
    }
}
