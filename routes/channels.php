<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Updated channel authorization to match our naming
Broadcast::channel('chat.{conversationId}', function (User $user, $conversationId) {
    return $user->conversations()->where('id', $conversationId)->exists();
});
