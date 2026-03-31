<?php

namespace App\Notifications;

use App\Models\Note;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NoteAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Note $note,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'NOTE_ADDED',
            'note_id' => $this->note->id,
            'note_content' => $this->note->content,
            'notable_type' => $this->note->notable_type,
            'notable_id' => $this->note->notable_id,
            'created_by' => $this->note->created_by,
        ];
    }
}
