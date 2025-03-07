<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class VisitorController extends Controller
{
    /**
     * Assign an available agent to the chat.
     */
    private function assignAvailableAgent($visitorId)
    {
        // Find an agent who is online and has fewer than 3 active chats
        $agent = User::where('is_online', true)
            ->whereHas('chats', function ($query) {
                $query->where('status', 'active');
            }, '<', 3) // Agents with fewer than 3 active chats
            ->role('Agent')
            ->first();
    
        if ($agent) {
            // Assign the agent to the chat
            $chat = Chat::create([
                'visitor_id' => $visitorId,
                'agent_id' => $agent->id,
                'status' => 'active',
            ]);
    
            return $chat->load('agent');
        }
    
        return null;
    }

    /**
     * Start a chat session for the visitor.
     */
    public function startChat(Request $request)
    {
        $request->validate([
            'name' => 'required_if:user_id,null',
            // 'email' => 'required_if:user_id,null|email',
            'contact' => 'required_if:user_id,null',
        ]);
    
        $userId = Auth::id();
        if ($userId) {
            $visitor = Visitor::firstOrCreate(
                ['user_id' => $userId],
                [
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'contact' => 'N/A',
                ]
            );
        } else {
            $visitor = Visitor::create($request->only('name', 'email', 'contact'));
        }
    
        // Store visitor_id in session
        $request->session()->put('visitor_id', $visitor->id);
    
        // Try to assign an available agent
        $retryCount = config('chat.retry_count', 3);
        $retryDelay = config('chat.retry_delay', 5);
    
        for ($i = 0; $i < $retryCount; $i++) {
            $chat = $this->assignAvailableAgent($visitor->id);
    
            if ($chat) {
                // Store chat_id in session
                $request->session()->put('chat_id', $chat->id);
                $request->session()->save();
                // Notify the agent that a visitor has connected
                $pusher = new Pusher(
                    env('PUSHER_APP_KEY'),      // App Key
                    env('PUSHER_APP_SECRET'),   // App Secret
                    env('PUSHER_APP_ID'),       // App ID
                    [
                        'cluster' => env('PUSHER_APP_CLUSTER'), // Cluster
                        'useTLS' => true,                       // Use TLS
                    ]
                );
                $pusher->trigger('chat.' . $chat->id, 'visitor.connected', [
                    'message' => 'A visitor has connected to the chat.',
                    'visitor_name' => $visitor->name,
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Agent assigned successfully.',
                    'chat_id' => $chat->id,
                    'agent_name' => $chat->agent->name,
                ]);
            }
    
            sleep($retryDelay);
        }
    
        return response()->json([
            'status' => 'waiting',
            'message' => 'No agents are available at the moment. Please wait or try again later.',
        ]);
    }

    /**
     * Send a message from the visitor to the agent.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'message' => 'required|string',
        ]);
    
        // Ensure the chat belongs to the visitor or the logged-in user
        if ($request->chat_id != session('chat_id')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized chat session']);
        }
        // Retrieve the chat
        $chat = Chat::findOrFail($request->chat_id);

        // Ensure the chat is active
        if ($chat->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'This chat session has ended. You can no longer send messages.',
            ], 202);
        }
        // Save the message
        $message = Message::create([
            'chat_id' => $request->chat_id,
            'sender_type' => 'visitor',
            'message' => $request->message,
        ]);
    
        // Get the sender name
        $senderName = 'You'; // For visitor messages, the sender is "You"
        if ($message->sender_type === 'agent') {
            $agent = User::find($message->chat->agent_id);
            $senderName = $agent ? $agent->name : 'Agent';
        }
    
        // Broadcast the message to the chat channel
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),      // App Key
            env('PUSHER_APP_SECRET'),   // App Secret
            env('PUSHER_APP_ID'),       // App ID
            [
                'cluster' => env('PUSHER_APP_CLUSTER'), // Cluster
                'useTLS' => true,                       // Use TLS
            ]
        );
    
        $pusher->trigger('chat.' . $request->chat_id, 'message.sent', [
            'sender' => $senderName,
            'senderType' => $message->sender_type,
            'message' => $message->message,
        ]);
    
        return response()->json([
            'status' => 'success',
            'sender' => $senderName,
            'senderType' => $message->sender_type,
            'message' => $message->message,
        ]);
    }

    /**
        * Destroy the chat session when the visitor leaves.
    */
    /**
     * Destroy the chat session when the visitor leaves.
     */
    public function leaveChat(Request $request)
    {
        $chatId = $request->session()->get('chat_id');
        if ($chatId) {
            // Mark the chat as inactive
            $chat = Chat::with('visitor')->find($chatId);
            $chat->update(['status' => 'inactive']);
    
            // Clear the chat_id from the session
            $request->session()->forget('chat_id');
            $request->session()->save();
    
            // Log the action
            Log::info('Visitor left the chat', [
                'chat_id' => $chatId,
            ]);
    
            // Notify the agent via Pusher
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),      // App Key
                env('PUSHER_APP_SECRET'),   // App Secret
                env('PUSHER_APP_ID'),       // App ID
                [
                    'cluster' => env('PUSHER_APP_CLUSTER'), // Cluster
                    'useTLS' => true,                       // Use TLS
                ]
            );
            $pusher->trigger('chat.' . $chatId, 'chat.ended', [
                'message' => 'The '.$chat->visitor->name.' has ended the chat session.',
            ]);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Chat session ended.',
            ]);
        }
    
        return response()->json([
            'status' => 'error',
            'message' => 'No active chat session found.',
        ]);
    }

    public function getChatHistory(Request $request)
    {
        $chatId = $request->query('chat_id');
        $messages = Message::with('chat','chat.agent')->where('chat_id', $chatId)->get();

        return response()->json([
            'messages' => $messages->map(function ($message) {
                return [
                    'sender' => $message->sender_type === 'visitor' ? 'You' : $message->chat->agent->name,
                    'message' => $message->message,
                ];
            }),
        ]);
    }
    public function getChatStatus(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
        ]);
        $chat = Chat::findOrFail($request->query('chat_id'));
        return response()->json([
            'status' => $chat->status,
        ]);
    }

    public function clearSession(Request $request)
    {
        // Clear the chat_id from the session
        $request->session()->forget('chat_id');
        $request->session()->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Session cleared successfully.',
        ]);
    }
}
