<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\ContractsModel;
use App\Models\DanaModel;

class DanaPaymentsController extends Controller
{
    /**
     * Generate QR Payment untuk kontrak tertentu.
     */
    public function createQrPayment($contractId)
    {
        $user = auth()->user();

        // Validasi otentikasi dan role
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User belum terautentikasi.'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email belum diverifikasi. Silakan verifikasi terlebih dahulu.'
            ], 403);
        }

        if ($user->role !== 'Recruiter') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya recruiter yang dapat membuat pembayaran.'
            ], 403);
        }

        // Cek kontrak
        $contract = ContractsModel::find($contractId);

        if (!$contract) {
            return response()->json([
                'status' => false,
                'message' => 'Kontrak tidak ditemukan.'
            ], 404);
        }

        if ($contract->status_pay !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Kontrak sudah dibayar sebelumnya.'
            ], 400);
        }

        // Generate ID untuk transaksi
        $merchantTransId = 'JOB-' . Str::uuid();
        $acquirementId   = 'ACQ-' . Str::uuid();

        // Simpan ke tabel dana_models
        $payment = DanaModel::create([
            'id_contract'        => $contract->id_contract,
            'merchant_trans_id'  => $merchantTransId,
            'acquirement_id'     => $acquirementId,
            'status'             => 'pending',
        ]);

        // Dummy URL QR
        $qrCodeUrl = "https://dummy.dana.qr/scan/{$merchantTransId}";

        return response()->json([
            'status' => true,
            'message' => 'QR code berhasil dibuat.',
            'data' => [
                'qr_code_url' => $qrCodeUrl,
                'payment_id'  => $payment->id_payments,
                'merchant_trans_id' => $merchantTransId,
                'status'      => 'waiting_for_payment'
            ]
        ]);
    }

    /**
     * Callback dari DANA ketika status pembayaran berubah.
     */
    public function handleCallback(Request $request)
    {
        // Ambil data JSON dari body
        $data = $request->json()->all();

        $merchantTransId = $data['merchantTransId'] ?? null;
        $status = strtolower($data['status'] ?? '');

        if (!$merchantTransId || !$status) {
            return response()->json([
                'message' => 'merchantTransId dan status wajib diisi.'
            ], 422);
        }

        $payment = DanaModel::where('merchant_trans_id', $merchantTransId)->first();

        if (!$payment) {
            \Log::warning("DANA callback: Transaksi tidak ditemukan untuk merchantTransId: {$merchantTransId}");
            return response()->json(['message' => 'Pembayaran tidak ditemukan.'], 404);
        }

        // Update status pembayaran
        $payment->status = $status;
        $payment->save();

        // Jika pembayaran sukses, update kontrak
        if ($status === 'success') {
            $contract = $payment->contract;

            if ($contract) {
                $contract->status_pay = 'success';
                $contract->save();
            } else {
                \Log::error("Contract tidak ditemukan untuk id_contract: {$payment->id_contract}");
            }
        }

        return response()->json(['message' => 'Status pembayaran diperbarui.']);
    }
}
