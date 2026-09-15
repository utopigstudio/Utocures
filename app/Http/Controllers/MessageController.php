<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\MessageStoreRequest;
use App\Models\Client;
use App\Models\Message;
use App\Models\MessageConversation;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\NewMessagePushNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function adminIndex(): Response
    {
        return $this->renderAdmin();
    }

    public function adminShow(Client $client): Response
    {
        return $this->renderAdmin($client);
    }

    public function clientIndex(Request $request): Response
    {
        $client = $request->user()->client;

        abort_unless($client, 403);

        return Inertia::render('messages/Index', [
            'conversations' => [],
            'selectedClient' => $this->serializeClient($client),
            'messages' => fn () => $this->conversationMessages($client, $request),
        ]);
    }

    public function adminStore(MessageStoreRequest $request, Client $client): RedirectResponse
    {
        $this->storeMessage($request, $client);

        return to_route('messages.show', $client);
    }

    public function clientStore(MessageStoreRequest $request): RedirectResponse
    {
        $client = $request->user()->client;

        abort_unless($client, 403);

        $this->storeMessage($request, $client);

        return to_route('client.messages.index');
    }

    public function adminRead(Client $client): RedirectResponse
    {
        $this->markIncomingAsRead($client, UserRole::CLIENT);

        return back();
    }

    public function clientRead(Request $request): RedirectResponse
    {
        $client = $request->user()->client;

        abort_unless($client, 403);

        $this->markIncomingAsRead($client, UserRole::ADMIN);

        return back();
    }

    private function renderAdmin(?Client $selectedClient = null): Response
    {
        return Inertia::render('messages/Index', [
            'conversations' => fn () => $this->adminConversations(),
            'selectedClient' => $selectedClient ? $this->serializeClient($selectedClient) : null,
            'messages' => fn () => $selectedClient
                ? $this->conversationMessages($selectedClient, request())
                : [],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function adminConversations(): array
    {
        return Client::query()
            ->select(['id', 'name', 'email', 'user_id'])
            ->with([
                'messageConversation:id,client_id,last_message_at',
                'messageConversation.latestMessage',
            ])
            ->withCount([
                'messages as unread_messages_count' => fn ($query) => $query
                    ->where('sender_role', UserRole::CLIENT)
                    ->whereNull('read_at'),
            ])
            ->get()
            ->sort(function (Client $first, Client $second): int {
                $firstTimestamp = $first->messageConversation?->last_message_at?->getTimestamp() ?? 0;
                $secondTimestamp = $second->messageConversation?->last_message_at?->getTimestamp() ?? 0;

                return $secondTimestamp <=> $firstTimestamp ?: strcasecmp($first->name, $second->name);
            })
            ->map(function (Client $client): array {
                $latestMessage = $client->messageConversation?->latestMessage;

                return [
                    ...$this->serializeClient($client),
                    'has_access' => $client->user_id !== null,
                    'unread_count' => (int) $client->unread_messages_count,
                    'last_message' => $latestMessage ? [
                        'body' => $latestMessage->body,
                        'sender_role' => $latestMessage->sender_role,
                        'created_at' => $latestMessage->created_at?->toISOString(),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function conversationMessages(Client $client, Request $request): array
    {
        $conversation = $client->messageConversation;

        if (! $conversation) {
            return [];
        }

        return $conversation->messages()
            ->with('sender:id,name')
            ->oldest()
            ->get()
            ->map(fn (Message $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_role' => $message->sender_role,
                'sender_name' => $message->sender?->name,
                'is_mine' => $message->sender_user_id === $request->user()->id,
                'is_from_current_side' => $message->sender_role === $request->user()->role,
                'read_at' => $message->read_at?->toISOString(),
                'created_at' => $message->created_at?->toISOString(),
            ])
            ->all();
    }

    private function storeMessage(MessageStoreRequest $request, Client $client): void
    {
        $message = DB::transaction(function () use ($request, $client): Message {
            $conversation = MessageConversation::query()->firstOrCreate([
                'client_id' => $client->id,
            ]);

            $message = $conversation->messages()->create([
                'sender_user_id' => $request->user()->id,
                'sender_role' => $request->user()->role,
                'body' => trim($request->validated('body')),
            ]);

            $conversation->update([
                'last_message_at' => $message->created_at,
            ]);

            return $message;
        });

        $this->sendPushNotifications($message, $client);
    }

    private function sendPushNotifications(Message $message, Client $client): void
    {
        $recipients = $message->sender_role === UserRole::CLIENT
            ? User::query()
                ->where('role', UserRole::ADMIN)
                ->where('is_active', true)
                ->whereHas('pushSubscriptions')
                ->get()
            : User::query()
                ->whereKey($client->user_id)
                ->where('is_active', true)
                ->whereHas('pushSubscriptions')
                ->get();

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new NewMessagePushNotification($message, $client));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    private function markIncomingAsRead(Client $client, string $senderRole): void
    {
        $conversation = $client->messageConversation;

        if (! $conversation) {
            return;
        }

        $conversation->messages()
            ->where('sender_role', $senderRole)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array{id: string, name: string, email: string}
     */
    private function serializeClient(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
        ];
    }
}
