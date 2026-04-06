<?php

namespace NettSite\Messenger\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NettSite\Messenger\Contracts\MessengerAuthenticatable;
use NettSite\Messenger\Http\Requests\CreateConversationMessageRequest;
use NettSite\Messenger\Jobs\SendConversationMessageJob;
use NettSite\Messenger\Models\Conversation;
use NettSite\Messenger\Models\Message;

class ConversationsController extends Controller
{
    public function show(Request $request, Message $message): JsonResponse
    {
        $user = $this->messengerUser($request);

        $conversation = Conversation::where('message_id', $message->getKey())
            ->where('user_type', get_class($user))
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($conversation === null) {
            abort(404);
        }

        $messages = $conversation->messages()->oldest()->get();

        $conversation->messages()
            ->where(function ($query) use ($user) {
                $query->where('author_type', '!=', get_class($user))
                    ->orWhere('author_id', '!=', $user->getAuthIdentifier());
            })
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'id' => $conversation->getKey(),
            'message_id' => $conversation->message_id,
            'messages' => $messages,
        ]);
    }

    public function store(CreateConversationMessageRequest $request, Message $message): JsonResponse
    {
        $user = $this->messengerUser($request);

        $conversation = Conversation::firstOrCreate([
            'message_id' => $message->getKey(),
            'user_type' => get_class($user),
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $conversationMessage = $conversation->messages()->create([
            'author_type' => get_class($user),
            'author_id' => $user->getAuthIdentifier(),
            'body' => $request->body,
        ]);

        SendConversationMessageJob::dispatch($conversationMessage);

        return response()->json($conversationMessage, 201);
    }

    private function messengerUser(Request $request): MessengerAuthenticatable
    {
        $user = $request->user();

        if (! $user instanceof MessengerAuthenticatable) {
            abort(403, 'User model must implement MessengerAuthenticatable.');
        }

        return $user;
    }
}
