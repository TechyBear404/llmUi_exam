<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the message.
     */
    public function delete(User $user, Message $message): bool
    {
        // Assurons-nous que l'utilisateur est le propriétaire de la conversation
        return $message && $message->conversation && $user->id === $message->conversation->user_id;
    }
}
