<?php

use Illuminate\Support\Facades\Route;
use NettSite\Messenger\Http\Controllers\AuthController;
use NettSite\Messenger\Http\Controllers\MessagesController;
use NettSite\Messenger\Http\Controllers\RepliesController;

Route::prefix('messenger')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/logout', [AuthController::class, 'logout']);

        Route::get('messages', [MessagesController::class, 'index']);
        Route::get('messages/poll', [MessagesController::class, 'poll']);
        Route::post('messages/{message}/read', [MessagesController::class, 'markRead']);
        Route::post('messages/{message}/replies', [RepliesController::class, 'store']);
    });
});
