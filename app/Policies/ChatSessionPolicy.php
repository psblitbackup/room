<?php

namespace App\Policies;

use App\Models\ChatSession;
use App\Models\User;

class ChatSessionPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, ChatSession $session): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->chatAgent && $session->agent_id === $user->chatAgent->id;
    }

    // public function create(?User $user): bool
    // {
    //     return true;
    // }

    public function reply(User $user, ChatSession $session): bool
    {
        return $user->chatAgent
            && $session->agent_id === $user->chatAgent->id
            && $session->status === 'active';
    }

    public function end(User $user, ChatSession $session): bool
    {
        return $this->reply($user, $session);
    }
}
