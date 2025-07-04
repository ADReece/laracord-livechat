<?php

namespace ADReece\LaracordLiveChat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ADReece\LaracordLiveChat\Services\ChatService;
use ADReece\LaracordLiveChat\Http\Requests\SendMessageRequest;
use ADReece\LaracordLiveChat\Http\Requests\StartSessionRequest;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Start a new chat session
     */
    public function startSession(StartSessionRequest $request)
    {
        $session = $this->chatService->createSession([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $request->input('metadata', []),
        ]);

        return response()->json([
            'session_id' => $session->id,
            'status' => 'success',
            'message' => 'Chat session started',
        ]);
    }

    /**
     * Send a message in a chat session
     */
    public function sendMessage(SendMessageRequest $request)
    {
        // Rate limiting check
        if (!$this->checkRateLimit($request)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rate limit exceeded. Please slow down.',
            ], 429);
        }

        // Check if session is still active
        if (!$this->chatService->isSessionActive($request->input('session_id'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chat session is not active or does not exist.',
            ], 400);
        }

        $message = $this->chatService->sendCustomerMessage(
            $request->input('session_id'),
            $request->input('message'),
            $request->input('name')
        );

        return response()->json([
            'status' => 'success',
            'message_id' => $message->id,
            'message' => 'Message sent successfully',
        ]);
    }

    /**
     * Get chat session with messages
     */
    public function getSession(Request $request, string $sessionId)
    {
        $session = $this->chatService->getSession($sessionId);

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session not found',
            ], 404);
        }

        // Mark customer messages as read (from agent perspective)
        $this->chatService->markMessagesAsRead($sessionId, 'customer');

        return response()->json([
            'status' => 'success',
            'session' => [
                'id' => $session->id,
                'customer_name' => $session->customer_name,
                'customer_email' => $session->customer_email,
                'status' => $session->status,
                'created_at' => $session->created_at->toISOString(),
                'messages' => $session->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender_type' => $message->sender_type,
                        'sender_name' => $message->sender_name,
                        'message' => $message->message,
                        'created_at' => $message->created_at->toISOString(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get session messages (for customer chat widget)
     */
    public function getMessages(Request $request, string $sessionId)
    {
        $session = $this->chatService->getSession($sessionId);

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session not found',
            ], 404);
        }

        // Mark agent messages as read (from customer perspective)
        $this->chatService->markMessagesAsRead($sessionId, 'agent');

        return response()->json([
            'status' => 'success',
            'messages' => $session->messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'sender_name' => $message->sender_name,
                    'message' => $message->message,
                    'created_at' => $message->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Close a chat session
     */
    public function closeSession(Request $request, string $sessionId)
    {
        $closed = $this->chatService->closeSession($sessionId);

        if (!$closed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session not found or already closed',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Chat session closed',
        ]);
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(Request $request, string $sessionId)
    {
        try {
            $stats = $this->chatService->getSessionStats($sessionId);

            return response()->json([
                'status' => 'success',
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session not found',
            ], 404);
        }
    }

    /**
     * Simple rate limiting check
     */
    private function checkRateLimit(Request $request): bool
    {
        if (!config('laracord-live-chat.rate_limiting.enabled', true)) {
            return true;
        }

        $key = 'chat_rate_limit:' . $request->ip();
        $maxMessages = config('laracord-live-chat.rate_limiting.max_messages_per_minute', 10);

        return app('cache')->remember($key, 60, function () {
            return 0;
        }) < $maxMessages;
    }
}
