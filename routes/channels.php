<?php

use App\Models\Chat;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// // Agent private channel
// Broadcast::channel('agent.{agentId}', function ($user, $agentId) {
//     // Only the authenticated agent can subscribe to their private channel
//     return (int) $user->id === (int) $agentId;
// });

// // Visitor private channel
// Broadcast::channel('visitor.{visitorId}', function ($visitor, $visitorId) {
//     // Only the visitor (logged-in user or guest) can subscribe to their private channel
//     return $visitor->id === (int) $visitorId;
// });

// // Chat channel (shared between agent and visitor)
// Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
//         \Log::info('Auth Attempt', [
//         'session' => session()->all(),
//         'cookie' => $_COOKIE,
//         'chatId' => $chatId
//     ]);
//     return true;
//     // session()->start(); // Force session start
//     // \Log::info('Auth Attempt', [
//     //     'session' => session()->all(),
//     //     'cookie' => $_COOKIE,
//     //     'chatId' => $chatId
//     // ]);
//     // $chat = \App\Models\Chat::find($chatId);

//     // if (!$chat) {
//     //     return false;
//     // }

//     // // Check if the user is the agent
//     // if ($user && $user->id === $chat->agent_id) {
//     //     return true;
//     // }

//     // // Check if the visitor is associated with the chat via session
//     // if (!$user && session()->has('chat_id') && session('chat_id') == $chatId) {
//     //     return true;
//     // }

//     // return false;
// });

// Private channel for agents
Broadcast::channel('chat.agent.{chatId}', function ($user, $chatId) {
    \Log::info('Chat Agent', [
        'chat_id' => $chatId
    ]);
    $chat = Chat::find($chatId);
    return $user && $chat && $user->id === $chat->agent_id;
});

// Public channel for visitors (no authentication required)
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // Ensure the user is either the assigned agent or visitor in the chat
    $chat = Chat::find($chatId);
    \Log::info('Chat Agent', [
        'chat_id' => $chatId
    ]);
    if (!$chat) {
        return false;
    }

    return $chat->visitor_id === $user->id || $chat->agent_id === $user->id;
});
