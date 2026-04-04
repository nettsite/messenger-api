<?php

namespace NettSite\Messenger\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'poll_interval' => config('messenger.polling.interval'),
        ]);
    }
}
