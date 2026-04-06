<?php

use Illuminate\Support\Facades\Route;
use NettSite\Messenger\Http\Controllers\AuthController;
use NettSite\Messenger\Http\Controllers\ConfigController;
use NettSite\Messenger\Http\Controllers\ConversationsController;
use NettSite\Messenger\Http\Controllers\MessagesController;

Route::prefix('messenger')->group(function () {
    Route::get('config', [ConfigController::class, 'show']);

    Route::middleware('throttle:5,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/device', [AuthController::class, 'refreshDevice']);

        Route::get('messages', [MessagesController::class, 'index']);
        Route::get('messages/poll', [MessagesController::class, 'poll']);
        Route::post('messages/{message}/read', [MessagesController::class, 'markRead']);
        Route::get('messages/{message}/conversation', [ConversationsController::class, 'show']);
        Route::post('messages/{message}/conversation/messages', [ConversationsController::class, 'store']);
    });
});
