<?php

namespace App\Observers;

use App\Models\AdminNotification;
use App\Models\Client;
use App\Models\Note;
use Illuminate\Support\Str;

class NoteObserver
{
    /**
     * Handle the Note "created" event.
     */
    public function created(Note $note): void
    {
        if ($note->type !== Note::TYPE_INCIDENT) {
            return;
        }

        $note->loadMissing(['user.employee', 'noteable']);

        if (! $note->user?->employee) {
            return;
        }

        if (! $note->noteable instanceof Client) {
            return;
        }

        AdminNotification::create([
            'type' => 'incident_note_created',
            'title' => __('notifications.incident_created_title'),
            'message' => __('notifications.incident_created_message', [
                'employee' => $note->user->name,
                'client' => $note->noteable->name,
            ]),
            'action_url' => route('clients.notes', [
                'client' => $note->noteable,
                'highlight_note' => $note->id,
            ]),
            'created_by_user_id' => $note->user_id,
            'notifiable_type' => $note->getMorphClass(),
            'notifiable_id' => $note->getKey(),
            'data' => [
                'note_id' => $note->id,
                'client_id' => $note->noteable->id,
                'client_name' => $note->noteable->name,
                'employee_name' => $note->user->name,
                'excerpt' => Str::limit(trim(strip_tags($note->content)), 120),
            ],
        ]);
    }
}
