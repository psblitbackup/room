## Pusher Setup
run composer require pusher/pusher-php-server

## Add this in the layout page
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>

## Add your Pusher credentials to your .env file:
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

## Add route in web.php file
// Agent routes
Route::middleware(['auth'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/chat', [AgentController::class, 'index'])->name('chat.index');
    Route::get('/chat/{chat}', [AgentController::class, 'show'])->name('chat.show');
    Route::post('/send-message', [AgentController::class, 'sendMessageAsAgent'])->name('send-message');
    Route::post('/end-chat', [AgentController::class, 'endChatSession'])->name('chat.end');
});

// Visitor routes
Route::middleware('web')->group(function () {
Route::post('/visitor/start-chat', [VisitorController::class, 'startChat'])->name('visitor.start-chat');
Route::post('/visitor/send-message', [VisitorController::class, 'sendMessage'])->name('visitor.send-message');
Route::get('/chat/history', [VisitorController::class, 'getChatHistory']);
Route::post('/visitor/leave-chat', [VisitorController::class, 'leaveChat'])->name('visitor.leave-chat');
Route::get('/chat/status', [VisitorController::class, 'getChatStatus']);
Route::post('/visitor/clear-session', [VisitorController::class, 'clearSession'])->name('visitor.clear-session');
});

## Ad database migrations file in Database/migration directory from here

2025_02_12_163826_add_is_online_to_users_table.php
2025_02_12_165903_create_visitors_table.php
2025_02_12_185747_create_chats_table.php
2025_02_17_153136_create_messages_table.php

## Add AgentController methods
## Add VisitorController methods
## Add chat.blade.php
## Add agent.blade.php
## Add agent-show.blade.php file

## copy resources/app.js file code

## Add below code in config/broadcast.php file
    'default' => env('BROADCAST_CONNECTION', 'pusher'),
    'default' => env('BROADCAST_DRIVER', 'pusher'),









