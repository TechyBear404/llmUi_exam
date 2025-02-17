<?php

namespace App\Policies;

use App\Models\CustomInstruction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomInstructionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CustomInstruction $customInstruction): bool
    {
        return $user->id === $customInstruction->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CustomInstruction $customInstruction): bool
    {
        return $user->id === $customInstruction->user_id;
    }

    public function delete(User $user, CustomInstruction $customInstruction): bool
    {
        return $user->id === $customInstruction->user_id;
    }
}