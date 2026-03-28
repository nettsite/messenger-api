<?php

namespace NettSite\Messenger\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NettSite\Messenger\Contracts\MessengerAuthenticatable;
use NettSite\Messenger\Models\Message;

class MessagesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->messengerUser($request);
        $messages = $this->messagesForUser($user)->paginate(20);

        return response()->json($messages);
    }

    public function poll(Request $request): JsonResponse
    {
        $user = $this->messengerUser($request);

        $messages = $this->messagesForUser($user)
            ->whereDoesntHave('receipts', function (Builder $q) use ($user) {
                $q->where('user_type', get_class($user))
                    ->where('user_id', $user->getAuthIdentifier());
            })
            ->get();

        foreach ($messages as $message) {
            $user->messageReceipts()->updateOrCreate(
                ['message_id' => $message->getKey()],
                ['delivered_at' => now()],
            );
        }

        return response()->json($messages);
    }

    public function markRead(Request $request, Message $message): JsonResponse
    {
        $this->messengerUser($request)->markMessageRead($message);

        return response()->json(['message' => 'Marked as read.']);
    }

    private function messengerUser(Request $request): MessengerAuthenticatable
    {
        $user = $request->user();

        if (! $user instanceof MessengerAuthenticatable) {
            abort(403, 'User model must implement MessengerAuthenticatable.');
        }

        return $user;
    }

    /** @return Builder<Message> */
    private function messagesForUser(MessengerAuthenticatable $user): Builder
    {
        $userId = $user->getAuthIdentifier();
        $groupIds = $user->groups()->pluck('messenger_groups.id');

        return Message::query()
            ->whereNotNull('sent_at')
            ->whereHas('recipients', function (Builder $q) use ($userId, $groupIds) {
                $q->where('recipient_type', 'all')
                    ->orWhere(function (Builder $q) use ($userId) {
                        $q->where('recipient_type', 'user')
                            ->where('recipient_id', $userId);
                    })
                    ->orWhere(function (Builder $q) use ($groupIds) {
                        $q->where('recipient_type', 'group')
                            ->whereIn('recipient_id', $groupIds);
                    });
            })
            ->latest('sent_at');
    }
}
