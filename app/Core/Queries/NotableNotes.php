<?php

namespace App\Core\Queries;

use App\Core\Models\Note;
use App\Core\Support\RelativeTime;
use Illuminate\Database\Eloquent\Model;

class NotableNotes
{
    /**
     * Notes on a contact or deal, newest first.
     *
     * @return list<array{id: int, body: string, author: string, time: string}>
     */
    public function present(Model $notable): array
    {
        return array_values(Note::query()
            ->whereMorphedTo('notable', $notable)
            ->with('author:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Note $note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->author->name,
                'time' => RelativeTime::format($note->created_at ?? now()),
            ])
            ->all());
    }
}
