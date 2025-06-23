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
    /**
     `* @OA\Post(
    *     path="/api/payments/qr/{contractId}",
    *     summary="Generate QR Payment untuk kontrak",
    *     tags={"Payment"},
    *     security={{"sanctum":{}}},
    *     @OA\Parameter(
    *         name="contractId",
    *         in="path",
    *         required=true,
    *         description="ID dari kontrak",
    *         @OA\Schema(type="integer", example=1)
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="QR code berhasil dibuat",
    *         @OA\JsonContent(
    *             @OA\Property(property="status", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="QR code berhasil dibuat."),
    *             @OA\Property(
    *                 property="data",
    *                 type="object",
    *                 @OA\Property(property="qr_code_url", type="string", example="https://dummy.dana.qr/scan/JOB-uuid123"),
    *                 @OA\Property(property="payment_id", type="integer", example=101),
    *                 @OA\Property(property="merchant_trans_id", type="string", example="JOB-uuid123"),
    *                 @OA\Property(property="status", type="string", example="waiting_for_payment")
    *             )
    *         )
    *     ),
    *     @OA\Response(response=401, description="User belum terautentikasi"),
    *     @OA\Response(response=403, description="Email belum diverifikasi atau bukan recruiter"),
    *     @OA\Response(response=404, description="Kontrak tidak ditemukan"),
    *     @OA\Response(response=400, description="Kontrak sudah dibayar"),
    *     @OA\Response(response=500, description="Kesalahan server")
    * )
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
    /**
 * @OA\Post(
 *     path="/api/payments/callback",
 *     summary="Callback DANA untuk update status pembayaran",
 *     tags={"Payment"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"merchantTransId", "status"},
 *             @OA\Property(property="merchantTransId", type="string", example="JOB-uuid123"),
 *             @OA\Property(property="status", type="string", example="success", enum={"success", "failed", "pending"})
 *         )
 *     ),
 *     @OA\Response(response=200, description="Status pembayaran diperbarui"),
 *     @OA\Response(response=404, description="Pembayaran tidak ditemukan"),
 *     @OA\Response(response=422, description="Validasi gagal")
 * )
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
