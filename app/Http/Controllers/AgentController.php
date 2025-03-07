<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatSession;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Auth;
use App\Events\NewMessage;
use Pusher\Pusher;

class AgentController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $chatSessions = auth()->user()->chats()->with('visitor')->latest()->paginate(20);
        return view('agent', compact('chatSessions'));
    }

    public function show(Chat $chat)
    {
        $chat->load(['visitor','messages']);
        return view('agent-show', compact('chat'));
    }

    public function sendMessageAsAgent(Request $request)
    {
        // Validate the request data
        $request->validate([
            'chat_id' => 'required|exists:chats,id', // Ensure the chat exists
            'message' => 'required|string',         // Ensure the message is a string
        ]);
    
        // Retrieve the chat and ensure the agent is authorized to send a message
        $chat = Chat::findOrFail($request->chat_id);
        // Ensure the chat is active
        if ($chat->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'This chat session has ended. You can no longer send messages.',
            ], 403);
        }
        // Optional: Add check to ensure the logged-in user is the assigned agent for the chat
        if ($chat->agent_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to send messages in this chat.',
            ], 403);
        }
    
        // Save the message
        $message = Message::create([
            'chat_id' => $request->chat_id,
            'sender_type' => 'agent',
            'message' => $request->message,
        ]);
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),      // App Key
            env('PUSHER_APP_SECRET'),   // App Secret
            env('PUSHER_APP_ID'),       // App ID
            [
                'cluster' => env('PUSHER_APP_CLUSTER'), // Cluster
                'useTLS' => true,                       // Use TLS
            ]
        );
        $senderName = $message->chat->agent->name; // Get the agent's name
        $pusher->trigger('chat.' . $request->chat_id, 'message.sent', [
            'sender' => $senderName,
            'senderType' => $message->sender_type,
            'message' => $message->message,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Message sent successfully.',
            'data' => [
                'message' => $message->message,
                'sender' => 'Agent',
                'chat_id' => $request->chat_id,
            ],
        ]);
    }
    // end chat agent
    public function endChatSession(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
        ]);
    
        $chat = Chat::findOrFail($request->chat_id);
    
        // Ensure the logged-in user is the assigned agent
        if ($chat->agent_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to end this chat session.',
            ], 403);
        }
    
        // Update chat status to inactive
        $chat->update(['status' => 'inactive']);
    
    
        // Notify the visitor via Pusher
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),      // App Key
            env('PUSHER_APP_SECRET'),   // App Secret
            env('PUSHER_APP_ID'),       // App ID
            [
                'cluster' => env('PUSHER_APP_CLUSTER'), // Cluster
                'useTLS' => true,                       // Use TLS
            ]
        );
        $pusher->trigger('chat.' . $chat->id, 'chat.ended', [
            'message' => 'The agent has ended the chat session.',
        ]);
        // Clear the visitor's session
        if ($chat->visitor_session_id) {
            session()->getHandler()->destroy($chat->visitor_session_id);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Chat session ended successfully.',
        ]);
    }
}