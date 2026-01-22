<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Get conversation between current user and another user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otherUserId = $request->user_id;

        // Get all messages between these two users
        $messages = Message::with(['sender', 'recipient'])
            ->where(function($query) use ($user, $otherUserId) {
                $query->where('sender_id', $user->id)
                      ->where('recipient_id', $otherUserId);
            })
            ->orWhere(function($query) use ($user, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                      ->where('recipient_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read (where current user is recipient)
        Message::where('recipient_id', $user->id)
            ->where('sender_id', $otherUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $formattedMessages = $messages->map(function ($message) use ($user) {
            return [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->first_name . ' ' . $message->sender->last_name,
                'recipient_id' => $message->recipient_id,
                'recipient_name' => $message->recipient->first_name . ' ' . $message->recipient->last_name,
                'message' => $message->message,
                'message_type' => $message->message_type,
                'is_read' => $message->is_read,
                'is_mine' => $message->sender_id === $user->id,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'time_ago' => $message->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $formattedMessages,
                'total' => $formattedMessages->count(),
            ],
        ]);
    }

    /**
     * Send a new message
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
            'message_type' => 'nullable|in:text,voice,video',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Verify recipient exists and is different from sender
        if ($user->id == $request->recipient_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send message to yourself',
            ], 422);
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $request->recipient_id,
            'message' => $request->message,
            'message_type' => $request->message_type ?? 'text',
            'is_read' => false,
        ]);

        $message->load(['sender', 'recipient']);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => [
                'message' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->first_name . ' ' . $message->sender->last_name,
                    'recipient_id' => $message->recipient_id,
                    'recipient_name' => $message->recipient->first_name . ' ' . $message->recipient->last_name,
                    'message' => $message->message,
                    'message_type' => $message->message_type,
                    'is_read' => $message->is_read,
                    'is_mine' => true,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $message->created_at->diffForHumans(),
                ],
            ],
        ], 201);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        // Only recipient can mark as read
        if ($message->recipient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $message->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
        ]);
    }

    /**
     * Get unread message count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = Message::where('recipient_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Get list of conversations (users with whom current user has messages)
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get unique user IDs with whom current user has exchanged messages
        $sentTo = Message::where('sender_id', $user->id)
            ->distinct()
            ->pluck('recipient_id');
        
        $receivedFrom = Message::where('recipient_id', $user->id)
            ->distinct()
            ->pluck('sender_id');

        $userIds = $sentTo->merge($receivedFrom)->unique();

        $conversations = [];

        foreach ($userIds as $userId) {
            $otherUser = User::find($userId);
            
            if (!$otherUser) continue;

            // Get last message
            $lastMessage = Message::where(function($query) use ($user, $userId) {
                    $query->where('sender_id', $user->id)
                          ->where('recipient_id', $userId);
                })
                ->orWhere(function($query) use ($user, $userId) {
                    $query->where('sender_id', $userId)
                          ->where('recipient_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            // Count unread messages from this user
            $unreadCount = Message::where('sender_id', $userId)
                ->where('recipient_id', $user->id)
                ->where('is_read', false)
                ->count();

            $conversations[] = [
                'user_id' => $userId,
                'user_name' => $otherUser->first_name . ' ' . $otherUser->last_name,
                'user_email' => $otherUser->email,
                'user_role' => $otherUser->role,
                'last_message' => $lastMessage ? $lastMessage->message : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : null,
                'unread_count' => $unreadCount,
            ];
        }

        // Sort by last message time (most recent first)
        usort($conversations, function($a, $b) {
            if ($a['last_message_time'] === $b['last_message_time']) return 0;
            return ($a['last_message_time'] < $b['last_message_time']) ? 1 : -1;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversations,
                'total' => count($conversations),
            ],
        ]);
    }
}
