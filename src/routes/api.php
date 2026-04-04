<?php

use Illuminate\Support\Facades\Route;
use NettSite\Messenger\Http\Controllers\AuthController;
use NettSite\Messenger\Http\Controllers\MessagesController;
use NettSite\Messenger\Http\Controllers\RepliesController;

Route::prefix('messenger')->group(function () {
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
        Route::get('messages/{message}/replies', [RepliesController::class, 'index']);
        Route::post('messages/{message}/replies', [RepliesController::class, 'store']);
    });
});
