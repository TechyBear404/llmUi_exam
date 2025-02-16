<?php

namespace App\Policies;

use App\Models\CustomInstruction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomInstructionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomInstruction $customInstruction): bool
    {
        return $user->id === $customInstruction->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomInstruction $customInstruction): bool
    {
        return $user->id === $customInstruction->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomInstruction $customInstruction): bool
    {
        return $user->id === $customInstruction->user_id;
    }
}
