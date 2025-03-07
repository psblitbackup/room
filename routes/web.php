<?php

use Illuminate\Support\Facades\Route;
use App\Events\TestBroadcastEvent;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\VisitorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test-broadcast', function () {
    broadcast(new TestBroadcastEvent('This is a test message!'));
    return 'Broadcast event fired!';
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/agent', fn() => view('agent'))->name('agent.dashboard');
    
});

Route::get('/visitor', fn() => view('chat'))->name('visitor.dashboard');


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

// Broadcast::routes(['middleware' => 'web']);
