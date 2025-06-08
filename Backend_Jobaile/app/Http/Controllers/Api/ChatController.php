<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkerModel;
use App\Models\RecruiterModel;
use App\Models\ChatModel;
use App\Events\ChatEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/chat/send",
     *     summary="Kirim pesan dari user yang login ke user lain",
     *     tags={"Chat"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"receiver_id", "message"},
     *             @OA\Property(property="receiver_id", type="string", description="ID user penerima pesan"),
     *             @OA\Property(property="message", type="string", description="Isi pesan yang dikirim")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pesan berhasil dikirim",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id_chat", type="integer"),
     *                 @OA\Property(property="id_sender", type="string"),
     *                 @OA\Property(property="id_receiver", type="string"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="send_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Email belum diverifikasi atau role tidak sesuai"),
     *     @OA\Response(response=422, description="Validasi input gagal"),
     * )
     */
    public function sendMessage(Request $request)
    {
        $sender = Auth::user();

        if (!$sender->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email belum diverifikasi.'], 403);
        }

        // Validasi input
        $request->validate([
            'receiver_id' => 'required|string|exists:users,id_user',
            'message' => 'required|string',
        ]);

        $receiver = User::findOrFail($request->receiver_id);

        // Pastikan role berbeda (Worker <-> Recruiter)
        if ($sender->role === $receiver->role) {
            return response()->json(['error' => 'Pesan hanya boleh antar Worker dan Recruiter.'], 403);
        }

        // Ambil id_worker dan id_recruiter dari masing-masing user
        if ($sender->role === 'Worker') {
            $worker = WorkerModel::where('id_user', $sender->id_user)->firstOrFail();
            $recruiter = RecruiterModel::where('id_user', $receiver->id_user)->firstOrFail();
        } else {
            $worker = WorkerModel::where('id_user', $receiver->id_user)->firstOrFail();
            $recruiter = RecruiterModel::where('id_user', $sender->id_user)->firstOrFail();
        }

        // Simpan pesan
        $chat = ChatModel::create([
            'id_sender' => $sender->id_user,
            'id_receiver' => $receiver->id_user,
            'message' => $request->message,
            'send_at' => now(),
        ]);
        

        event(new ChatEvent($chat));

        return response()->json([
            'message' => 'Pesan terkirim.',
            'data' => $chat,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/chat/{id_user_b}",
     *     summary="Ambil riwayat pesan antara user login dan user lain",
     *     tags={"Chat"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id_user_b",
     *         in="path",
     *         required=true,
     *         description="ID user yang akan diambil riwayat pesannya",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil pesan",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="fullname", type="string"),
     *                 @OA\Property(property="profile_picture", type="string", nullable=true)
     *             ),
     *             @OA\Property(property="messages", type="object",
     *                 @OA\Property(property="chats", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id_chat", type="integer"),
     *                         @OA\Property(property="id_sender", type="string"),
     *                         @OA\Property(property="id_receiver", type="string"),
     *                         @OA\Property(property="message", type="string"),
     *                         @OA\Property(property="send_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="send_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Email belum diverifikasi atau role sama"),
     *     @OA\Response(response=500, description="Kesalahan server"),
     * )
     */
    public function getMessages($id_user_b)
    {
        try {
            $userA = Auth::user();

            if (!$userA->hasVerifiedEmail()) {
                return response()->json(['error' => 'Email belum diverifikasi.'], 403);
            }

            $userB = User::findOrFail($id_user_b);

            if ($userA->role === $userB->role) {
                return response()->json(['error' => 'Riwayat hanya antara Worker dan Recruiter.'], 403);
            }

            // Ambil profile picture jika perlu
            $profilePicture = null;
            if ($userB->role === 'Worker') {
                $profilePicture = optional(WorkerModel::where('id_user', $userB->id_user)->first())->profile_picture;
            } elseif ($userB->role === 'Recruiter') {
                $profilePicture = optional(RecruiterModel::where('id_user', $userB->id_user)->first())->profile_picture;
            }

            // Ambil semua pesan dua arah antara userA dan userB
            $chats = ChatModel::where(function ($query) use ($userA, $userB) {
                    $query->where('id_sender', $userA->id_user)
                        ->where('id_receiver', $userB->id_user);
                })
                ->orWhere(function ($query) use ($userA, $userB) {
                    $query->where('id_sender', $userB->id_user)
                        ->where('id_receiver', $userA->id_user);
                })
                ->orderBy('send_at', 'asc')
                ->get();

            $lastSentAt = $chats->isNotEmpty() ? $chats->last()->send_at : null;

            return response()->json([
                'status' => true,
                'data' => [
                    'fullname' => $userB->fullname,
                    'profile_picture' => $profilePicture,
                ],
                'messages' => [
                    'chats' => $chats,
                    'send_at' => $lastSentAt,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil pesan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/getchat",
     *     summary="Ambil daftar percakapan terakhir user yang login",
     *     tags={"Chat"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil daftar percakapan",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="conversations", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id_chat", type="integer"),
     *                     @OA\Property(property="id_user", type="string"),
     *                     @OA\Property(property="fullname", type="string"),
     *                     @OA\Property(property="profile_picture", type="string", nullable=true),
     *                     @OA\Property(property="last_message", type="string"),
     *                     @OA\Property(property="send_at", type="string", format="date-time"),
     *                     @OA\Property(property="from_me", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Email belum diverifikasi"),
     *     @OA\Response(response=500, description="Kesalahan server"),
     * )
     */
    public function getConversations()
    {
        try {
            $authUser = Auth::user();

            if (!$authUser->hasVerifiedEmail()) {
                return response()->json(['error' => 'Email belum diverifikasi.'], 403);
            }

            // Ambil semua pesan yang melibatkan user (baik sebagai pengirim atau penerima)
            $messages = ChatModel::where('id_sender', $authUser->id_user)
                ->orWhere('id_receiver', $authUser->id_user)
                ->orderBy('send_at', 'desc')
                ->get();

            $conversations = [];

            foreach ($messages as $message) {
                $otherUserId = $message->id_sender === $authUser->id_user
                    ? $message->id_receiver
                    : $message->id_sender;

                $otherUser = User::find($otherUserId);
                if (!$otherUser) continue;

                // Ambil foto profil sesuai role
                $profilePicture = null;
                if ($otherUser->role === 'Worker') {
                    $profilePicture = optional(WorkerModel::where('id_user', $otherUserId)->first())->profile_picture;
                } elseif ($otherUser->role === 'Recruiter') {
                    $profilePicture = optional(RecruiterModel::where('id_user', $otherUserId)->first())->profile_picture;
                }

                // Simpan hanya pesan terakhir (paling baru) per user
                if (!isset($conversations[$otherUserId]) || $message->send_at > $conversations[$otherUserId]['send_at']) {
                    $conversations[$otherUserId] = [
                        'id_chat' => $message->id_chat,
                        'id_user' => $otherUserId,
                        'fullname' => $otherUser->fullname,
                        'profile_picture' => $profilePicture,
                        'last_message' => $message->message,
                        'send_at' => $message->send_at,
                        'from_me' => $message->id_sender === $authUser->id_user,
                    ];
                }
            }

            // Urutkan berdasarkan send_at terbaru
            $sorted = collect($conversations)->sortByDesc('send_at')->values();

            return response()->json([
                'status' => true,
                'conversations' => $sorted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil daftar percakapan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
