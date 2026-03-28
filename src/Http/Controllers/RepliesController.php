<?php

namespace NettSite\Messenger\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use NettSite\Messenger\Http\Requests\CreateReplyRequest;
use NettSite\Messenger\Models\Message;

class RepliesController extends Controller
{
    public function store(CreateReplyRequest $request, Message $message): JsonResponse
    {
        $user = $request->user();

        $reply = $message->replies()->create([
            'user_type' => get_class($user),
            'user_id' => $user->getAuthIdentifier(),
            'body' => $request->body,
        ]);

        return response()->json($reply, 201);
    }
}
